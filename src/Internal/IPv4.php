<?php

declare(strict_types=1);

namespace IPv4\Internal;

/**
 * IPv4 protocol constants.
 *
 * Provides fundamental IPv4 constants used throughout the library for address
 * calculations and validations.
 *
 * @internal This class is not part of the public API
 */
final class IPv4
{
    /**
     * Maximum valid IPv4 address value (255.255.255.255).
     */
    public const MAX_ADDRESS = 4_294_967_295; // 0xFFFFFFFF

    /**
     * Total IPv4 address space (2^32).
     */
    public const ADDRESS_SPACE = 4_294_967_296;

    /**
     * Maximum usable hosts in the entire IPv4 space (/0 network).
     * Excludes network (0.0.0.0) and broadcast (255.255.255.255) addresses.
     */
    public const MAX_HOSTS = 4_294_967_294; // 2^32 - 2

    /**
     * Prevent instantiation.
     * @psalm-suppress UnusedConstructor
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
