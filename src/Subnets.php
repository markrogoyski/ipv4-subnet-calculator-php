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
     * @throws \InvalidArgumentException If any element is not a Subnet
     *
     * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - CIDR
     */
    public static function aggregate(array $subnets): array
    {
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
     * @throws \InvalidArgumentException If the subnet array is empty or any element is not a Subnet
     *
     * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - CIDR
     */
    public static function summarize(array $subnets): Subnet
    {
        if (empty($subnets)) {
            throw new \InvalidArgumentException(
                'Cannot summarize empty subnet array'
            );
        }

        Assert::allSubnets($subnets);

        $span = self::findAddressSpan($subnets);
        $prefix = self::findSmallestCoveringPrefix($span['min'], $span['max']);
        $start = self::alignToPrefix($span['min'], $prefix);

        return new Subnet(new IPAddress($start), $prefix);
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
        $min = IPv4::MAX_ADDRESS;
        $max = 0;

        foreach ($subnets as $subnet) {
            $start = $subnet->networkAddress()->asInteger();
            $end = $start + $subnet->addressCount() - 1;

            $min = \min($min, $start);
            $max = \max($max, $end);
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
        // The prefix is the number of leading bits min and max share
        $differingBits = $min ^ $max;

        return $differingBits === 0 ? 32 : 32 - \strlen(\decbin($differingBits));
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
        $blockSize = 1 << (32 - $prefix);

        return \intdiv($address, $blockSize) * $blockSize;
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
        return \array_map(
            fn (CidrBlock $block): Subnet => new Subnet(new IPAddress($block->startInt()), $block->prefix()),
            $blocks
        );
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
}
