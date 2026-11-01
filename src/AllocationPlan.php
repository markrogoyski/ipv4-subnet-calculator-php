<?php

declare(strict_types=1);

namespace IPv4;

use IPv4\Internal\Assert;

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
     * Obtain plans via SubnetAllocator::allocate(). Only the parts' shape is checked here, not that
     * they partition a parent network.
     *
     * @internal
     *
     * @param array<string, Subnet> $allocations Allocations in the caller's requirement order
     * @param list<Subnet>          $reserved    Normalized union of reservations
     * @param list<Subnet>          $remaining   Minimal exact free space
     *
     * @throws \InvalidArgumentException If an element is not a Subnet, an allocation key is not a string,
     *                                   or reserved or remaining is not a list
     */
    public function __construct(
        private readonly array $allocations,
        private readonly array $reserved,
        private readonly array $remaining,
    ) {
        self::assertParts($allocations, $reserved, $remaining);
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

    /**
     * Assert that the parts have the documented shape.
     *
     * @param array<mixed> $allocations
     * @param array<mixed> $reserved
     * @param array<mixed> $remaining
     *
     * @throws \InvalidArgumentException If an element is not a Subnet, an allocation key is not a string,
     *                                   or reserved or remaining is not a list
     */
    private static function assertParts(array $allocations, array $reserved, array $remaining): void
    {
        foreach (\array_keys($allocations) as $name) {
            if (!\is_string($name)) {
                throw new \InvalidArgumentException("Allocation name must be a string, got integer key {$name}");
            }
        }
        if (!\array_is_list($reserved) || !\array_is_list($remaining)) {
            throw new \InvalidArgumentException('Reserved and remaining subnets must be lists');
        }
        Assert::allSubnets($allocations);
        Assert::allSubnets($reserved);
        Assert::allSubnets($remaining);
    }
}
