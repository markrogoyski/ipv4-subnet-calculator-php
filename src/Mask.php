<?php

declare(strict_types=1);

namespace IPv4;

/**
 * Common interface for subnet masks and wildcard masks.
 *
 * Both SubnetMask and WildcardMask represent 32-bit values that can be
 * formatted in multiple ways. This interface defines the shared contract.
 */
interface Mask extends \Stringable
{
    /**
     * Get the CIDR prefix length.
     *
     * @return int Prefix (0-32)
     */
    public function prefix(): int;

    /**
     * Get as dotted quad notation.
     *
     * @return string e.g., "255.255.255.0"
     */
    public function asQuads(): string;

    /**
     * Get as array of octet strings.
     *
     * @return string[] e.g., ['255', '255', '255', '0']
     */
    public function asArray(): array;

    /**
     * Get as hexadecimal string.
     *
     * @return string e.g., "FFFFFF00"
     */
    public function asHex(): string;

    /**
     * Get as binary string.
     *
     * @return string e.g., "11111111111111111111111100000000"
     */
    public function asBinary(): string;

    /**
     * Get as 32-bit integer.
     *
     * @return int e.g., 4294967040
     */
    public function asInteger(): int;
}
