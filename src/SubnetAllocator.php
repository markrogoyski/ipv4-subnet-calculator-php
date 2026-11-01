<?php

declare(strict_types=1);

namespace IPv4;

use IPv4\Internal\Assert;

/**
 * Stateless, deterministic variable-size subnet allocation (VLSM) within one parent network.
 *
 * Each named requirement receives one CIDR block sized for its usable host count. Larger
 * blocks are placed first, equal sizes in bytewise name order, each at the lowest free
 * address where it fits aligned. The result is complete or an exception is thrown.
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
        $parent = new Subnet($parent->networkAddress(), $parent->networkSize());
        $prefixes = self::requirementPrefixes($requirements);
        self::assertReservations($parent, $reserved);

        // Larger blocks (smaller prefixes) first, then bytewise name order
        $order = \array_keys($prefixes);
        \usort(
            $order,
            static fn (string $a, string $b): int => ($prefixes[$a] <=> $prefixes[$b]) ?: \strcmp($a, $b)
        );

        $reservedUnion = Subnets::aggregate($reserved);
        $free = Subnets::difference([$parent], $reservedUnion);
        $placed = [];
        foreach ($order as $name) {
            $placed[$name] = self::firstFit($free, $name, $prefixes[$name]);
            $free = Subnets::difference($free, [$placed[$name]]);
        }

        $allocations = [];
        foreach (\array_keys($prefixes) as $name) {
            $allocations[$name] = $placed[$name];
        }

        return new AllocationPlan($allocations, $reservedUnion, $free);
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
            if (!\is_string($name) || $name === '') {
                throw new \InvalidArgumentException(
                    'Requirement name must be a nonempty string, got '
                    . (\is_int($name) ? "integer key {$name}" : 'empty string')
                );
            }
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
     * Find the lowest-address aligned block of the given prefix in the free space.
     *
     * Free blocks are minimal and ascending, so any aligned fit lies inside one block at least
     * as large, and the first such block's network address is the lowest fit.
     *
     * @param list<Subnet> $free   Minimal free blocks in ascending order
     * @param string       $name   Requirement name, for the failure message
     * @param int          $prefix Required prefix
     *
     * @return Subnet
     *
     * @throws \RuntimeException If no free block is large enough
     */
    private static function firstFit(array $free, string $name, int $prefix): Subnet
    {
        foreach ($free as $block) {
            if ($block->networkSize() <= $prefix) {
                return new Subnet($block->networkAddress(), $prefix);
            }
        }

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
