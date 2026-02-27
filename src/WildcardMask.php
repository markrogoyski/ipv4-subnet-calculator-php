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

        $subnetMask = $prefix === 0 ? 0 : (0xFFFFFFFF << (32 - $prefix));
        $this->maskBits = ~$subnetMask & 0xFFFFFFFF;
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
     * Get the four octets as integers.
     *
     * @return int[]
     */
    protected function octets(): array
    {
        return [
            ($this->maskBits >> 24) & 0xFF,
            ($this->maskBits >> 16) & 0xFF,
            ($this->maskBits >> 8) & 0xFF,
            $this->maskBits & 0xFF,
        ];
    }
}
