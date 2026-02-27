<?php

declare(strict_types=1);

namespace IPv4;

/**
 * Shared formatting logic for IPv4 value objects.
 *
 * Provides methods to represent a 32-bit value (IP address or mask) in multiple
 * formats: dotted quads, array, hexadecimal, binary, and integer.
 *
 * Classes using this trait must implement the octets() method to provide
 * the four octets as integers.
 */
trait Formattable
{
    /**
     * Get the four octets as integers.
     *
     * @return int[] Four octets (0-255 each)
     */
    abstract protected function octets(): array;

    /**
     * Get as dotted quad notation.
     *
     * @return string e.g., "192.168.1.100"
     */
    public function asQuads(): string
    {
        return \implode('.', $this->octets());
    }

    /**
     * Get as array of octet strings.
     *
     * @return string[] e.g., ['192', '168', '1', '100']
     */
    public function asArray(): array
    {
        return \array_map(\strval(...), $this->octets());
    }

    /**
     * Get as hexadecimal string.
     *
     * @return string e.g., "C0A80164"
     */
    public function asHex(): string
    {
        return \implode('', \array_map(
            static fn(int $octet): string => \sprintf('%02X', $octet),
            $this->octets()
        ));
    }

    /**
     * Get as binary string.
     *
     * @return string e.g., "11000000101010000000000101100100"
     */
    public function asBinary(): string
    {
        return \implode('', \array_map(
            static fn(int $octet): string => \sprintf('%08b', $octet),
            $this->octets()
        ));
    }

    /**
     * Get as 32-bit integer.
     *
     * @return int e.g., 3232235876
     */
    public function asInteger(): int
    {
        $octets = $this->octets();

        return ($octets[0] << 24) | ($octets[1] << 16) | ($octets[2] << 8) | $octets[3];
    }

    /**
     * String representation (dotted quads).
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->asQuads();
    }
}
