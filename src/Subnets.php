<?php

declare(strict_types=1);

namespace IPv4;

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

        self::assertAllSubnets($subnets);

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
        self::assertAllSubnets($subnets);

        if ($single = self::summarizeSingle($subnets)) {
            return $single;
        }

        $span = self::findAddressSpan($subnets);
        $prefix = self::findSmallestCoveringPrefix($span['min'], $span['max']);
        $start = self::alignToPrefix($span['min'], $prefix);

        return self::subnetFromStartAndPrefix($start, $prefix);
    }

    /**
     * Assert that all elements are Subnet instances.
     *
     * @param array<mixed> $subnets
     *
     * @throws \InvalidArgumentException If any element is not a Subnet
     */
    private static function assertAllSubnets(array $subnets): void
    {
        foreach ($subnets as $i => $subnet) {
            if (!$subnet instanceof Subnet) {
                throw new \InvalidArgumentException(
                    \sprintf('Expected Subnet at index %s, got %s', $i, \get_debug_type($subnet))
                );
            }
        }
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

        $subnet = $subnets[0];

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
     * @param CidrBlock[] $blocks
     *
     * @return CidrBlock[]
     */
    private static function removeContainedBlocks(array $blocks): array
    {
        $filtered = [];
        foreach ($blocks as $block) {
            $isContained = false;
            foreach ($filtered as $existing) {
                if ($block->startInt() >= $existing->startInt() && $block->endInt() <= $existing->endInt()) {
                    $isContained = true;
                    break;
                }
            }
            if (!$isContained) {
                $filtered = \array_filter(
                    $filtered,
                    fn(CidrBlock $existing) => !(
                        $existing->startInt() >= $block->startInt()
                        && $existing->endInt() <= $block->endInt()
                    )
                );
                $filtered[] = $block;
            }
        }

        \usort($filtered, self::compareByStart(...));

        return $filtered;
    }

    /**
     * Merge adjacent blocks with identical prefixes.
     *
     * @param CidrBlock[] $blocks
     *
     * @return CidrBlock[]
     */
    private static function mergeAdjacentBlocks(array $blocks): array
    {
        $merged = true;
        while ($merged) {
            $merged = false;
            $newBlocks = [];
            $used = [];

            for ($i = 0; $i < \count($blocks); $i++) {
                if (isset($used[$i])) {
                    continue;
                }

                $current = $blocks[$i];
                $foundMerge = false;

                for ($j = $i + 1; $j < \count($blocks); $j++) {
                    $other = $blocks[$j];

                    if ($current->prefix() === $other->prefix() && $current->prefix() >= 1) {
                        $prefix = $current->prefix();
                        $currentEnd = $current->endInt();

                        if ($currentEnd + 1 === $other->startInt()) {
                            $newPrefix = $prefix - 1;
                            $newBlockSize = $newPrefix === 0 ? IPv4::ADDRESS_SPACE : (1 << (32 - $newPrefix));

                            if (($current->startInt() % $newBlockSize) === 0) {
                                $newBlocks[] = new CidrBlock($current->startInt(), $newPrefix);
                                $used[$i] = true;
                                $used[$j] = true;
                                $merged = true;
                                $foundMerge = true;
                                break;
                            }
                        }
                    }
                }

                if (!$foundMerge) {
                    $newBlocks[] = $current;
                    $used[$i] = true;
                }
            }

            $blocks = $newBlocks;
            \usort($blocks, self::compareByStart(...));
        }

        return $blocks;
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
