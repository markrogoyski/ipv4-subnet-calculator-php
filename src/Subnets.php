<?php

declare(strict_types=1);

namespace IPv4;

use IPv4\Internal\Assert;
use IPv4\Internal\CidrBlock;
use IPv4\Internal\IPv4;
use IPv4\Internal\RangeDecomposer;

/**
 * Collection operations on arrays of Subnet instances.
 *
 * Provides methods for working with multiple subnets:
 *  - Aggregate multiple subnets into minimal CIDR blocks
 *  - Summarize subnets into a single supernet
 *  - Intersect, subtract and compare coverage of subnet collections as address sets
 *  - Find the most specific subnet containing an address (longest-prefix match)
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
     * @return list<Subnet> Aggregated supernets, in ascending order
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

        return new Subnet(IPAddress::fromInteger($start), $prefix);
    }

    /**
     * Find the addresses present in both collections.
     *
     * Collections are treated as address sets, including network and broadcast addresses.
     * Duplicate, nested, unsorted and keyed inputs are accepted.
     *
     * @param Subnet[] $left  First collection
     * @param Subnet[] $right Second collection
     *
     * @return list<Subnet> Fewest canonical CIDR blocks covering exactly the shared addresses, in ascending order
     *
     * @throws \InvalidArgumentException If any element of either collection is not a Subnet
     */
    public static function intersection(array $left, array $right): array
    {
        Assert::allSubnets($left);
        Assert::allSubnets($right);

        $rightIntervals = self::toIntervals($right);
        $rightCount = \count($rightIntervals);
        $shared = [];
        $j = 0;
        foreach (self::toIntervals($left) as [$start, $end]) {
            // Advance past right intervals ending in this one; one extending beyond it is kept for the next
            while ($j < $rightCount && $rightIntervals[$j][0] <= $end) {
                [$rightStart, $rightEnd] = $rightIntervals[$j];
                if ($rightEnd >= $start) {
                    $shared[] = [\max($start, $rightStart), \min($end, $rightEnd)];
                }
                if ($rightEnd > $end) {
                    break;
                }
                $j++;
            }
        }

        return self::intervalsToSubnets($shared);
    }

    /**
     * Find the addresses in the first collection that are not in the second.
     *
     * Collections are treated as address sets, including network and broadcast addresses.
     * Duplicate, nested, unsorted and keyed inputs are accepted. The operation is not commutative.
     *
     * @param Subnet[] $left  Collection to subtract from
     * @param Subnet[] $right Collection of addresses to remove
     *
     * @return list<Subnet> Fewest canonical CIDR blocks covering exactly the remaining addresses, in ascending order
     *
     * @throws \InvalidArgumentException If any element of either collection is not a Subnet
     */
    public static function difference(array $left, array $right): array
    {
        Assert::allSubnets($left);
        Assert::allSubnets($right);

        return self::intervalsToSubnets(self::subtractIntervals(self::toIntervals($left), self::toIntervals($right)));
    }

    /**
     * Check whether every address in the required collection is in the covering collection.
     *
     * Several smaller blocks may together cover a larger required block. Every collection covers
     * an empty collection, and an empty collection covers no nonempty one.
     *
     * @param Subnet[] $covering Collection that must contain the addresses
     * @param Subnet[] $required Collection of addresses that must be contained
     *
     * @return bool True if difference($required, $covering) is empty
     *
     * @throws \InvalidArgumentException If any element of either collection is not a Subnet
     */
    public static function covers(array $covering, array $required): bool
    {
        Assert::allSubnets($covering);
        Assert::allSubnets($required);

        return self::subtractIntervals(self::toIntervals($required), self::toIntervals($covering)) === [];
    }

    /**
     * Find the most specific subnet that contains an IP address (longest-prefix match).
     *
     * The most specific subnet is the containing one with the largest networkSize(). When several
     * containing subnets share that prefix, the first in iteration order wins.
     *
     * The matched element is returned unchanged, not a canonical copy, so its key can be recovered
     * with \array_search($match, $subnets, true): for example, to find which VLAN a client is in.
     *
     * This is a linear O(n) scan, not a routing trie.
     *
     * @param Subnet[]         $subnets Subnets to search
     * @param string|IPAddress $ip      Address to match
     *
     * @return Subnet|null The most specific containing subnet, or null if none contains the address
     *
     * @throws \InvalidArgumentException If any element is not a Subnet or the IP address is invalid
     */
    public static function longestPrefixMatch(array $subnets, string|IPAddress $ip): ?Subnet
    {
        Assert::allSubnets($subnets);

        if (\is_string($ip)) {
            $ip = new IPAddress($ip);
        }

        $match = null;
        foreach ($subnets as $subnet) {
            if ($subnet->containsIP($ip) && ($match === null || $subnet->networkSize() > $match->networkSize())) {
                $match = $subnet;
            }
        }

        return $match;
    }

    /**
     * Convert subnets into sorted, disjoint, non-touching inclusive address intervals.
     *
     * Overlapping and adjacent subnets are merged, so each interval is a maximal run of addresses.
     *
     * @param Subnet[] $subnets
     *
     * @return list<array{int, int}>
     */
    private static function toIntervals(array $subnets): array
    {
        $ranges = [];
        foreach ($subnets as $subnet) {
            $start = $subnet->networkAddress()->asInteger();
            $ranges[] = [$start, $start + $subnet->addressCount() - 1];
        }
        \sort($ranges);

        if ($ranges === []) {
            return [];
        }

        $intervals = [];
        [$currentStart, $currentEnd] = $ranges[0];
        foreach ($ranges as [$start, $end]) {
            if ($start <= $currentEnd + 1) {
                $currentEnd = \max($currentEnd, $end);
                continue;
            }
            $intervals[] = [$currentStart, $currentEnd];
            [$currentStart, $currentEnd] = [$start, $end];
        }
        $intervals[] = [$currentStart, $currentEnd];

        return $intervals;
    }

    /**
     * Remove the right intervals from the left intervals.
     *
     * Both inputs must be sorted, disjoint and non-touching, as returned by toIntervals().
     * The result has the same properties.
     *
     * @param list<array{int, int}> $left
     * @param list<array{int, int}> $right
     *
     * @return list<array{int, int}>
     */
    private static function subtractIntervals(array $left, array $right): array
    {
        $rightCount = \count($right);
        $remaining = [];
        $j = 0;
        foreach ($left as [$start, $end]) {
            $cursor = $start;
            // Advance past right intervals ending in this one; one extending beyond it is kept for the next
            while ($j < $rightCount && $right[$j][0] <= $end) {
                [$rightStart, $rightEnd] = $right[$j];
                if ($rightStart > $cursor) {
                    $remaining[] = [$cursor, $rightStart - 1];
                }
                $cursor = \max($cursor, $rightEnd + 1);
                if ($rightEnd > $end) {
                    break;
                }
                $j++;
            }
            if ($cursor <= $end) {
                $remaining[] = [$cursor, $end];
            }
        }

        return $remaining;
    }

    /**
     * Decompose sorted, disjoint, non-touching intervals into their fewest CIDR blocks.
     *
     * @param list<array{int, int}> $intervals
     *
     * @return list<Subnet>
     */
    private static function intervalsToSubnets(array $intervals): array
    {
        $subnets = [];
        foreach ($intervals as [$start, $end]) {
            \array_push($subnets, ...RangeDecomposer::toSubnets($start, $end));
        }

        return $subnets;
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
     * @return list<CidrBlock>
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
     * @return list<CidrBlock>
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
     * @param list<CidrBlock> $blocks
     *
     * @return list<CidrBlock>
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
     * @param list<CidrBlock> $blocks
     *
     * @return list<Subnet>
     */
    private static function blocksToSubnets(array $blocks): array
    {
        return \array_map(
            fn (CidrBlock $block): Subnet => new Subnet(IPAddress::fromInteger($block->startInt()), $block->prefix()),
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
