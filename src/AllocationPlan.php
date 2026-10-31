<?php

declare(strict_types=1);

namespace IPv4;

/**
 * Immutable result of SubnetAllocator::allocate().
 *
 * Partitions the parent network into three disjoint sets that together cover it exactly:
 * named allocations, the normalized reservations, and the remaining free space.
 */
final class AllocationPlan
{
    /**
     * Create a plan from already computed parts.
     *
     * Obtain plans via SubnetAllocator::allocate(); the parts are not validated here.
     *
     * @internal
     *
     * @param array<string, Subnet> $allocations Allocations in the caller's requirement order
     * @param list<Subnet>          $reserved    Normalized union of reservations
     * @param list<Subnet>          $remaining   Minimal exact free space
     */
    public function __construct(
        private readonly array $allocations,
        private readonly array $reserved,
        private readonly array $remaining,
    ) {
    }

    /**
     * Get the allocated subnets keyed by requirement name, in the caller's requirement order.
     *
     * @return array<string, Subnet> name => allocated canonical subnet
     */
    public function allocations(): array
    {
        return $this->allocations;
    }

    /**
     * Get the reserved space: the union of the reservations as the fewest CIDR blocks.
     *
     * @return list<Subnet> Canonical blocks in ascending address order
     */
    public function reserved(): array
    {
        return $this->reserved;
    }

    /**
     * Get the free space left in the parent after reservations and allocations.
     *
     * @return list<Subnet> Fewest canonical CIDR blocks in ascending address order
     */
    public function remaining(): array
    {
        return $this->remaining;
    }
}
