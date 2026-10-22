<?php

declare(strict_types=1);

namespace IPv4\Internal;

use IPv4\Subnet;

/**
 * Shared input validation.
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
     * Check that a string is a plain decimal integer with no sign, whitespace or leading zeros.
     *
     * Leading zeros are rejected to match IPAddress, which refuses them as octal-ambiguous.
     *
     * @param string $value
     *
     * @return bool
     */
    public static function isDecimal(string $value): bool
    {
        return \ctype_digit($value) && ($value === '0' || $value[0] !== '0');
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
