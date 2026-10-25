<?php

declare(strict_types=1);

namespace IPv4;

/**
 * Immutable value object representing an IPv4 subnet mask.
 *
 * A subnet mask defines the network portion of an IP address. It consists of
 * contiguous 1-bits from the left (network bits) followed by 0-bits (host bits).
 *
 * Examples:
 *   /24 → 255.255.255.0 (24 network bits, 8 host bits)
 *   /16 → 255.255.0.0   (16 network bits, 16 host bits)
 *   /8  → 255.0.0.0     (8 network bits, 24 host bits)
 */
final class SubnetMask implements Mask
{
    use Formattable;

    private readonly int $maskBits;

    /**
     * Create a subnet mask from a CIDR prefix.
     *
     * @param int $prefix CIDR prefix length (0-32)
     *
     * @throws \InvalidArgumentException If prefix is not 0-32
     */
    public function __construct(private readonly int $prefix)
    {
        if ($prefix < 0 || $prefix > 32) {
            throw new \InvalidArgumentException(
                "Prefix must be between 0 and 32, got {$prefix}"
            );
        }

        $this->maskBits = (0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF;
    }

    /**
     * Create a subnet mask from dotted-quad notation.
     *
     * @param string $subnetMask Subnet mask in dotted quad notation (e.g., "255.255.255.0")
     *
     * @return self
     *
     * @throws \InvalidArgumentException If the mask is not a dotted quad or is non-contiguous
     */
    public static function fromString(string $subnetMask): self
    {
        try {
            $maskInt = (new IPAddress($subnetMask))->asInteger();
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(
                "Invalid subnet mask format: '{$subnetMask}'",
                0,
                $e
            );
        }

        $inverted = ~$maskInt & 0xFFFFFFFF;
        if (($inverted & ($inverted + 1)) !== 0) {
            throw new \InvalidArgumentException(
                "Invalid subnet mask: non-contiguous mask '{$subnetMask}'"
            );
        }

        return new self(\substr_count(\decbin($maskInt), '1'));
    }

    /**
     * Get the CIDR prefix length.
     *
     * @return int Prefix (0-32)
     */
    public function prefix(): int
    {
        return $this->prefix;
    }

    /**
     * Get the corresponding wildcard mask.
     *
     * @return WildcardMask
     */
    public function wildcardMask(): WildcardMask
    {
        return new WildcardMask($this->prefix);
    }

    /**
     * Check equality with another subnet mask.
     *
     * @param self $other
     *
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->prefix === $other->prefix;
    }

    /**
     * Get as 32-bit integer.
     *
     * @return int e.g., 4294967040
     */
    public function asInteger(): int
    {
        return $this->maskBits;
    }
}
