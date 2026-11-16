<?php

declare(strict_types=1);

namespace IPv4;

use IPv4\Internal\Assert;

/**
 * Stateless, deterministic variable-size subnet allocation (VLSM) within one parent network.
 *
 * Each named requirement receives one CIDR block sized for its usable host count or given CIDR
 * prefix. Larger blocks are placed first, equal sizes in bytewise name order, each at the lowest
 * free address where it fits aligned. The result is complete or an exception is thrown.
 */
final class SubnetAllocator
{
    /**
     * Allocate one subnet per requirement from a parent network, avoiding reserved space.
     *
     * Sizing follows SubnetParser::optimalPrefixForHosts(): one host gets a /32, two get a /31,
     * and larger counts include the network and broadcast addresses. The parent's host bits are
     * ignored. Reservations may duplicate, nest or overlap one another. All input is validated
     * before anything is placed.
     *
     * PHP collapses duplicate array keys before the call, so callers must reject duplicate names
     * while building the requirement map. Numeric-string keys such as '10' become integers and
     * are rejected.
     *
     * @param Subnet             $parent       Network to allocate from
     * @param array<string, int> $requirements name => required usable hosts
     * @param Subnet[]           $reserved     Subnets wholly inside the parent to keep free of allocations
     *
     * @return AllocationPlan
     *
     * @throws \InvalidArgumentException If a name is not a nonempty string, a host count is not a valid
     *                                   positive int, or a reservation is not a Subnet inside the parent
     * @throws \RuntimeException         If a requirement cannot be placed in the free space left
     */
    public static function allocate(Subnet $parent, array $requirements, array $reserved = []): AllocationPlan
    {
        return self::place($parent, self::requirementPrefixes($requirements), $reserved);
    }

    /**
     * Allocate one subnet per named CIDR prefix from a parent network, avoiding reserved space.
     *
     * Use this when a block's size is known as a prefix rather than a host count. Host-count
     * sizing maps two hosts to a /31 and three to a /29, so a /30 link can only be requested
     * here. Mix both sizings by converting host counts with SubnetParser::optimalPrefixForHosts():
     * ['lan' => SubnetParser::optimalPrefixForHosts(50), 'wan' => 30].
     *
     * Placement matches allocate(): larger blocks (smaller prefixes) first, equal prefixes in
     * bytewise name order, each at the lowest free aligned address; the plan lists allocations in
     * input order. The parent's host bits are ignored. Reservations may duplicate, nest or overlap
     * one another. All input is validated before anything is placed, and the plan is complete or
     * an exception is thrown. A prefix shorter than the parent's is valid input that cannot fit.
     *
     * PHP collapses duplicate array keys before the call, so callers must reject duplicate names
     * while building the prefix map. Numeric-string keys such as '10' become integers and are
     * rejected.
     *
     * @param Subnet             $parent   Network to allocate from
     * @param array<string, int> $prefixes name => CIDR prefix (0 to 32)
     * @param Subnet[]           $reserved Subnets wholly inside the parent to keep free of allocations
     *
     * @return AllocationPlan
     *
     * @throws \InvalidArgumentException If a name is not a nonempty string, a prefix is not an int from
     *                                   0 to 32, or a reservation is not a Subnet inside the parent
     * @throws \RuntimeException         If a block cannot be placed in the free space left, including a
     *                                   prefix shorter than the parent's
     */
    public static function allocateByPrefix(Subnet $parent, array $prefixes, array $reserved = []): AllocationPlan
    {
        return self::place($parent, self::validatedPrefixes($prefixes), $reserved);
    }

    /**
     * Place validated prefixes in the parent's free space, after validating the reservations.
     *
     * @param Subnet             $parent   Network to allocate from
     * @param array<string, int> $prefixes name => prefix, in input order
     * @param Subnet[]           $reserved Subnets wholly inside the parent to keep free of allocations
     *
     * @return AllocationPlan
     *
     * @throws \InvalidArgumentException If a reservation is not a Subnet inside the parent
     * @throws \RuntimeException         If a block cannot be placed in the free space left
     */
    private static function place(Subnet $parent, array $prefixes, array $reserved): AllocationPlan
    {
        $parent = new Subnet($parent->networkAddress(), $parent->networkSize());
        self::assertReservations($parent, $reserved);

        // Larger blocks (smaller prefixes) first, then bytewise name order
        $order = \array_keys($prefixes);
        \usort(
            $order,
            static fn (string $a, string $b): int => ($prefixes[$a] <=> $prefixes[$b]) ?: \strcmp($a, $b)
        );

        $reservedUnion = Subnets::aggregate($reserved);
        $free = Subnets::difference([$parent], $reservedUnion);

        // The free list is held as a gap buffer at a scan cursor: $before holds the blocks left of
        // the cursor in ascending order and $after the rest in descending order, so the free list
        // is $before followed by \array_reverse($after) and the block at the cursor is $after's
        // last element. Requests come in non-decreasing prefix order, and for one prefix the first
        // fit never moves left (the block left at the cursor after a placement is exactly the
        // placed size), so the cursor rewinds to the start only when the prefix changes.
        $before = [];
        $after = \array_reverse($free);
        $cursorPrefix = null;
        $placed = [];
        foreach ($order as $name) {
            $prefix = $prefixes[$name];
            if ($prefix !== $cursorPrefix) {
                $after = \array_merge($after, \array_reverse($before));
                $before = [];
                $cursorPrefix = $prefix;
            }

            $block = self::firstFit($before, $after, $name, $prefix);
            $placed[$name] = new Subnet($block->networkAddress(), $prefix);

            // Replace the block by its remainder: the buddies /P+1 at a+B/2 down to /prefix at a+s,
            // pushed largest address first so the lowest lands at the cursor. This is the minimal
            // ascending decomposition of the block minus the placed subnet at its start.
            $start = $block->networkAddress()->asInteger();
            for ($buddyPrefix = $block->networkSize() + 1; $buddyPrefix <= $prefix; $buddyPrefix++) {
                $after[] = new Subnet(IPAddress::fromInteger($start + (1 << (32 - $buddyPrefix))), $buddyPrefix);
            }
        }
        $free = \array_merge($before, \array_reverse($after));

        $allocations = [];
        foreach (\array_keys($prefixes) as $name) {
            $allocations[$name] = $placed[$name];
        }

        return new AllocationPlan($allocations, $reservedUnion, $free);
    }

    /**
     * Assert that a requirement name is a nonempty string.
     *
     * @param int|string $name
     *
     * @psalm-assert non-empty-string $name
     *
     * @throws \InvalidArgumentException If the name is an integer key or an empty string
     */
    private static function assertName(int|string $name): void
    {
        if (!\is_string($name) || $name === '') {
            throw new \InvalidArgumentException(
                'Requirement name must be a nonempty string, got '
                . (\is_int($name) ? "integer key {$name}" : 'empty string')
            );
        }
    }

    /**
     * Validate the names and prefixes of a prefix map.
     *
     * @param array<mixed> $prefixes
     *
     * @return array<string, int> name => prefix, in input order
     *
     * @throws \InvalidArgumentException If a name or prefix is invalid
     */
    private static function validatedPrefixes(array $prefixes): array
    {
        $validated = [];
        foreach ($prefixes as $name => $prefix) {
            self::assertName($name);
            if (!\is_int($prefix)) {
                throw new \InvalidArgumentException(
                    "Requirement '{$name}' prefix must be an int, got " . \get_debug_type($prefix)
                );
            }
            if ($prefix < 0 || $prefix > 32) {
                throw new \InvalidArgumentException(
                    "Requirement '{$name}' prefix must be between 0 and 32, got {$prefix}"
                );
            }

            $validated[$name] = $prefix;
        }

        return $validated;
    }

    /**
     * Validate the requirements and convert each host count to its prefix.
     *
     * @param array<mixed> $requirements
     *
     * @return array<string, int> name => prefix, in input order
     *
     * @throws \InvalidArgumentException If a name or host count is invalid
     */
    private static function requirementPrefixes(array $requirements): array
    {
        $prefixes = [];
        foreach ($requirements as $name => $hostCount) {
            self::assertName($name);
            if (!\is_int($hostCount)) {
                throw new \InvalidArgumentException(
                    "Requirement '{$name}' host count must be an int, got " . \get_debug_type($hostCount)
                );
            }

            try {
                $prefixes[$name] = SubnetParser::optimalPrefixForHosts($hostCount);
            } catch (\InvalidArgumentException $e) {
                throw new \InvalidArgumentException("Requirement '{$name}': {$e->getMessage()}", 0, $e);
            }
        }

        return $prefixes;
    }

    /**
     * Assert that every reservation is a Subnet wholly contained in the parent.
     *
     * @param Subnet       $parent
     * @param array<mixed> $reserved
     *
     * @throws \InvalidArgumentException If a reservation is not a Subnet or lies outside the parent
     */
    private static function assertReservations(Subnet $parent, array $reserved): void
    {
        Assert::allSubnets($reserved);

        /** @var Subnet[] $reserved */
        foreach ($reserved as $reservation) {
            if (!$parent->contains($reservation)) {
                throw new \InvalidArgumentException(
                    "Reservation {$reservation->networkCidr()} is not contained in parent {$parent->networkCidr()}"
                );
            }
        }
    }

    /**
     * Advance the scan cursor to the first free block that can hold the given prefix and remove it.
     *
     * Free blocks are minimal and ascending, so any aligned fit lies inside one block at least
     * as large, and the first such block's network address is the lowest fit. The free list is
     * $before followed by \array_reverse($after), with the cursor between them; blocks too small
     * for the prefix move from $after to $before as the cursor passes them. The caller must have
     * no fitting block in $before, which holds while the prefix is unchanged since the last rewind.
     *
     * @param list<Subnet> $before Free blocks left of the cursor, ascending
     * @param list<Subnet> $after  Free blocks from the cursor on, descending
     * @param string       $name   Requirement name, for the failure message
     * @param int          $prefix Required prefix
     *
     * @param-out list<Subnet> $before
     * @param-out list<Subnet> $after
     *
     * @return Subnet The free block at the cursor, no longer in either list
     *
     * @throws \RuntimeException If no free block is large enough
     */
    private static function firstFit(array &$before, array &$after, string $name, int $prefix): Subnet
    {
        while (($block = \array_pop($after)) !== null) {
            if ($block->networkSize() <= $prefix) {
                return $block;
            }
            $before[] = $block;
        }

        // The cursor passed every block, so $before is the whole free list
        $free = $before;
        if ($free === []) {
            throw new \RuntimeException("Cannot allocate '{$name}' as a /{$prefix}: no free space remains");
        }

        $largest = \min(\array_map(static fn (Subnet $block): int => $block->networkSize(), $free));
        $freeAddresses = \array_sum(\array_map(static fn (Subnet $block): int => $block->addressCount(), $free));
        $blockCount = \count($free);
        throw new \RuntimeException(\sprintf(
            "Cannot allocate '%s' as a /%d: largest free block is a /%d (%d free %s in %d %s)",
            $name,
            $prefix,
            $largest,
            $freeAddresses,
            $freeAddresses === 1 ? 'address' : 'addresses',
            $blockCount,
            $blockCount === 1 ? 'block' : 'blocks'
        ));
    }

    /**
     * Prevent instantiation.
     * @psalm-suppress UnusedConstructor
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
