<?php

declare(strict_types=1);

namespace IPv4;

use IPv4\Internal\Assert;
use IPv4\Internal\CidrBlock;
use IPv4\Internal\IPv4;

/**
 * Collection operations on arrays of Subnet instances.
 *
 * Provides methods for working with multiple subnets:
 *  - Aggregate multiple subnets into minimal CIDR blocks
 *  - Summarize subnets into a single supernet
 *
 * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - CIDR
 */
final class Subnets
{
    /**
     * Aggregate multiple subnets into the smallest possible supernet(s).
     *
     * Combines contiguous subnets into larger summary routes.
     * Overlapping and duplicate subnets are handled by removing redundant entries.
     *
     * @param Subnet[] $subnets Subnets to aggregate
     *
     * @return Subnet[] Aggregated supernets
     *
     * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - CIDR
     */
    public static function aggregate(array $subnets): array
    {
        if (empty($subnets)) {
            return [];
        }

        Assert::allSubnets($subnets);

        $blocks = self::collectBlocks($subnets);
        $blocks = self::removeContainedBlocks($blocks);
        $blocks = self::mergeAdjacentBlocks($blocks);

        return self::blocksToSubnets($blocks);
    }

    /**
     * Find the smallest single supernet that contains all given subnets.
     *
     * Unlike aggregate(), this always returns a single subnet but may include
     * addresses not in any of the input subnets.
     *
     * @param Subnet[] $subnets Subnets to summarize
     *
     * @return Subnet The smallest supernet containing all inputs
     *
     * @throws \InvalidArgumentException If the subnet array is empty
     *
     * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - CIDR
     */
    public static function summarize(array $subnets): Subnet
    {
        self::assertNonEmpty($subnets);
        Assert::allSubnets($subnets);

        if ($single = self::summarizeSingle($subnets)) {
            return $single;
        }

        $span = self::findAddressSpan($subnets);
        $prefix = self::findSmallestCoveringPrefix($span['min'], $span['max']);
        $start = self::alignToPrefix($span['min'], $prefix);

        return self::subnetFromStartAndPrefix($start, $prefix);
    }

    /**
     * Assert that the subnet array is not empty.
     *
     * @param Subnet[] $subnets
     *
     * @throws \InvalidArgumentException If the array is empty
     */
    private static function assertNonEmpty(array $subnets): void
    {
        if (empty($subnets)) {
            throw new \InvalidArgumentException(
                'Cannot summarize empty subnet array'
            );
        }
    }

    /**
     * Return a normalized subnet if the array contains exactly one element.
     *
     * @param Subnet[] $subnets
     *
     * @return Subnet|null The single subnet, or null if multiple subnets
     */
    private static function summarizeSingle(array $subnets): ?Subnet
    {
        if (\count($subnets) !== 1) {
            return null;
        }

        $subnet = $subnets[\array_key_first($subnets)];

        return new Subnet((string) $subnet->networkAddress(), $subnet->networkSize());
    }

    /**
     * Find the minimum and maximum addresses spanned by the subnets.
     *
     * @param Subnet[] $subnets Non-empty array of subnets
     *
     * @return array{min: int, max: int}
     */
    private static function findAddressSpan(array $subnets): array
    {
        $first = true;
        $min = 0;
        $max = 0;

        foreach ($subnets as $subnet) {
            $start = $subnet->networkAddress()->asInteger();
            $end = $start + $subnet->addressCount() - 1;

            if ($first) {
                $min = $start;
                $max = $end;
                $first = false;
            } else {
                if ($start < $min) {
                    $min = $start;
                }
                if ($end > $max) {
                    $max = $end;
                }
            }
        }

        return ['min' => $min, 'max' => $max];
    }

    /**
     * Find the smallest CIDR prefix that can cover the address span.
     *
     * @param int $min Minimum address in the span
     * @param int $max Maximum address in the span
     *
     * @return int The CIDR prefix (0-32)
     */
    private static function findSmallestCoveringPrefix(int $min, int $max): int
    {
        $prefix = 32;

        while ($prefix > 0) {
            $blockSize = 1 << (32 - $prefix);
            $networkStart = ((int) ($min / $blockSize)) * $blockSize;
            $networkEnd = $networkStart + $blockSize - 1;

            if ($networkStart <= $min && $networkEnd >= $max) {
                return $prefix;
            }

            $prefix--;
        }

        // /0 covers the entire IPv4 address space
        return 0;
    }

    /**
     * Align an address to a CIDR prefix boundary.
     *
     * @param int $address The address to align
     * @param int $prefix  The CIDR prefix
     *
     * @return int The aligned network start address
     */
    private static function alignToPrefix(int $address, int $prefix): int
    {
        $blockSize = $prefix === 0 ? IPv4::ADDRESS_SPACE : (1 << (32 - $prefix));

        return ((int) ($address / $blockSize)) * $blockSize;
    }

    /**
     * Create a subnet from a start address and prefix.
     *
     * @param int $start  Network start address as integer
     * @param int $prefix CIDR prefix (0-32)
     *
     * @return Subnet
     */
    private static function subnetFromStartAndPrefix(int $start, int $prefix): Subnet
    {
        return new Subnet(self::unsignedToIp($start), $prefix);
    }

    /**
     * Convert subnets into sorted CIDR blocks.
     *
     * @param Subnet[] $subnets
     *
     * @return CidrBlock[]
     */
    private static function collectBlocks(array $subnets): array
    {
        $blocks = [];
        foreach ($subnets as $subnet) {
            $start = $subnet->networkAddress()->asInteger();
            $blocks[] = new CidrBlock($start, $subnet->networkSize());
        }

        \usort($blocks, self::compareByStart(...));

        return $blocks;
    }

    /**
     * Remove blocks fully contained by another block.
     *
     * Blocks must be sorted by start ascending, then end descending. CIDR blocks are either
     * nested or disjoint, so a block is redundant exactly when the last kept block covers its end.
     *
     * @param CidrBlock[] $blocks
     *
     * @return CidrBlock[]
     */
    private static function removeContainedBlocks(array $blocks): array
    {
        $filtered = [];
        $lastEnd = -1;
        foreach ($blocks as $block) {
            if ($block->endInt() <= $lastEnd) {
                continue;
            }
            $filtered[] = $block;
            $lastEnd = $block->endInt();
        }

        return $filtered;
    }

    /**
     * Merge adjacent blocks with identical prefixes.
     *
     * Blocks must be sorted and disjoint. A newly merged block can only pair with the block
     * immediately before it, so a stack collapses each incoming block with its predecessors.
     *
     * @param CidrBlock[] $blocks
     *
     * @return CidrBlock[]
     */
    private static function mergeAdjacentBlocks(array $blocks): array
    {
        $stack = [];
        foreach ($blocks as $block) {
            while ($stack !== []) {
                $merged = self::mergeSiblings($stack[\count($stack) - 1], $block);
                if ($merged === null) {
                    break;
                }
                \array_pop($stack);
                $block = $merged;
            }
            $stack[] = $block;
        }

        return $stack;
    }

    /**
     * Merge two blocks into their parent block if they are its two halves.
     *
     * @param CidrBlock $left  Lower block
     * @param CidrBlock $right Block immediately following $left
     *
     * @return CidrBlock|null The parent block, or null if the blocks are not siblings
     */
    private static function mergeSiblings(CidrBlock $left, CidrBlock $right): ?CidrBlock
    {
        $prefix = $left->prefix();
        if ($prefix === 0 || $right->prefix() !== $prefix || $left->endInt() + 1 !== $right->startInt()) {
            return null;
        }

        $parentPrefix = $prefix - 1;
        if (self::alignToPrefix($left->startInt(), $parentPrefix) !== $left->startInt()) {
            return null;
        }

        return new CidrBlock($left->startInt(), $parentPrefix);
    }

    /**
     * Convert blocks into Subnet objects.
     *
     * @param CidrBlock[] $blocks
     *
     * @return Subnet[]
     */
    private static function blocksToSubnets(array $blocks): array
    {
        $result = [];
        foreach ($blocks as $block) {
            $ip = self::unsignedToIp($block->startInt());
            $result[] = new Subnet($ip, $block->prefix());
        }

        return $result;
    }

    /**
     * Sort blocks by start ascending, then by end descending.
     */
    private static function compareByStart(CidrBlock $a, CidrBlock $b): int
    {
        $startComparison = $a->startInt() <=> $b->startInt();
        if ($startComparison !== 0) {
            return $startComparison;
        }

        return $b->endInt() <=> $a->endInt();
    }

    /**
     * Convert IP integer to dotted quad.
     *
     * @param int $ip
     *
     * @return string
     */
    private static function unsignedToIp(int $ip): string
    {
        if ($ip < 0 || $ip > IPv4::MAX_ADDRESS) {
            throw new \InvalidArgumentException(
                "IP integer out of range: {$ip}"
            );
        }

        return \long2ip($ip);
    }
}
