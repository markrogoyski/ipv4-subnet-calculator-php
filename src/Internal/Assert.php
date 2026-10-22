<?php

declare(strict_types=1);

namespace IPv4\Internal;

use IPv4\Subnet;

/**
 * Shared input validation for methods that accept arrays of subnets.
 *
 * @internal This class is not part of the public API
 */
final class Assert
{
    /**
     * Assert that all elements are Subnet instances.
     *
     * @param array<mixed> $subnets
     *
     * @throws \InvalidArgumentException If any element is not a Subnet
     */
    public static function allSubnets(array $subnets): void
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
     * Prevent instantiation.
     * @psalm-suppress UnusedConstructor
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
