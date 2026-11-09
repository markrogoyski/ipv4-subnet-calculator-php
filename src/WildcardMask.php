<?php

declare(strict_types=1);

namespace IPv4;

/**
 * Immutable value object representing an IPv4 wildcard mask.
 *
 * A wildcard mask is the bitwise inverse of a subnet mask. It's commonly used
 * in Cisco ACLs (Access Control Lists) and OSPF network statements.
 *
 * Examples:
 *   /24 → 0.0.0.255     (matches last 8 bits)
 *   /16 → 0.0.255.255   (matches last 16 bits)
 *   /8  → 0.255.255.255 (matches last 24 bits)
 *
 * Only contiguous low-order wildcards are representable. ACLs also allow
 * non-contiguous wildcards such as 0.0.255.0, but no prefix expresses them,
 * so they are rejected.
 *
 * @link https://www.cisco.com/c/en/us/support/docs/security/ios-firewall/23602-confaccesslists.html
 */
final class WildcardMask implements Mask
{
    use Formattable;

    private readonly int $maskBits;

    /**
     * Create a wildcard mask from a CIDR prefix.
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

        $this->maskBits = (1 << (32 - $prefix)) - 1;
    }

    /**
     * Create a wildcard mask from dotted-quad notation.
     *
     * @param string $wildcardMask Wildcard mask in dotted quad notation (e.g., "0.0.0.255")
     *
     * @return self
     *
     * @throws \InvalidArgumentException If the mask is not a dotted quad or is non-contiguous
     */
    public static function fromString(string $wildcardMask): self
    {
        try {
            $maskInt = (new IPAddress($wildcardMask))->asInteger();
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(
                "Invalid wildcard mask format: '{$wildcardMask}'",
                0,
                $e
            );
        }

        if (($maskInt & ($maskInt + 1)) !== 0) {
            throw new \InvalidArgumentException(
                "Invalid wildcard mask: non-contiguous mask '{$wildcardMask}'"
            );
        }

        return new self(32 - \substr_count(\decbin($maskInt), '1'));
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
     * Get the corresponding subnet mask.
     *
     * @return SubnetMask
     */
    public function subnetMask(): SubnetMask
    {
        return new SubnetMask($this->prefix);
    }

    /**
     * Check equality with another wildcard mask.
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
     * @return int e.g., 255
     */
    public function asInteger(): int
    {
        return $this->maskBits;
    }
}
