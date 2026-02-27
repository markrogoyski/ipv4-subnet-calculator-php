<?php

declare(strict_types=1);

namespace IPv4\Internal;

/**
 * Platform compatibility checks.
 *
 * @internal This class is not part of the public API
 */
final class Platform
{
    private static bool $platformChecked = false;

    /**
     * Ensure the platform supports 64-bit integers.
     *
     * @throws \RuntimeException If running on 32-bit PHP
     */
    public static function ensure64Bit(): void
    {
        if (self::$platformChecked) {
            return;
        }

        if (\PHP_INT_SIZE < 8) {
            throw new \RuntimeException(
                'IPv4 Subnet Calculator requires 64-bit PHP. ' .
                'Current PHP installation is ' . (\PHP_INT_SIZE * 8) . '-bit. ' .
                'Please use a 64-bit PHP build.'
            );
        }

        self::$platformChecked = true;
    }
}
