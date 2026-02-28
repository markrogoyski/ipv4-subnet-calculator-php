<?php

declare(strict_types=1);

namespace IPv4;

use IPv4\Internal\IPv4;
use IPv4\Internal\Platform;
use IPv4\Internal\SubnetReport;

/**
 * Immutable representation of an IPv4 subnet.
 *
 * Given an IP address and CIDR prefix (e.g., 192.168.1.100/24), provides comprehensive
 * subnet information including masks, network addresses, IP ranges, and classification.
 *
 * Key capabilities:
 *  - Network calculations (subnet mask, wildcard, broadcast, usable ranges)
 *  - IP address type detection (private, public, loopback, multicast, etc.)
 *  - Network analysis (overlap detection, containment checking)
 *  - Subnet operations (splitting, navigation, exclusion)
 *
 * Special handling per RFC 3021:
 *  - /32: Single host network (min/max host same as IP address)
 *  - /31: Point-to-point link (both addresses usable, no broadcast/network overhead)
 *
 * @link https://datatracker.ietf.org/doc/html/rfc3021
 */
final class Subnet implements \JsonSerializable, \Stringable
{
    private readonly IPAddress $ip;
    private readonly int $subnetMaskBits;

    /**
     * Create a subnet from an IP address and CIDR prefix.
     *
     * @param string $ipAddress   IP address in dotted quad notation
     * @param int    $networkSize CIDR prefix (0-32)
     *
     * @throws \InvalidArgumentException If IP address or network size is invalid
     * @throws \RuntimeException If running on 32-bit PHP
     */
    public function __construct(
        string $ipAddress,
        private readonly int $networkSize,
    ) {
        Platform::ensure64Bit();

        if ($networkSize < 0 || $networkSize > 32) {
            throw new \InvalidArgumentException(
                "Network size must be between 0 and 32, got {$networkSize}"
            );
        }

        $this->ip = new IPAddress($ipAddress);
        $this->subnetMaskBits = $networkSize === 0 ? 0 : (0xFFFFFFFF << (32 - $networkSize));
    }

    /**
     * Create a subnet from CIDR notation.
     *
     * @param string $cidr CIDR notation (e.g., "192.168.1.100/24")
     *
     * @return self
     *
     * @throws \InvalidArgumentException If CIDR format is invalid
     */
    public static function fromCidr(string $cidr): self
    {
        if (\strpos($cidr, '/') === false) {
            throw new \InvalidArgumentException(
                "Invalid CIDR notation: missing '/' in '{$cidr}'"
            );
        }

        $parts = \explode('/', $cidr);

        if (\count($parts) !== 2) {
            throw new \InvalidArgumentException(
                "Invalid CIDR notation: multiple '/' found in '{$cidr}'"
            );
        }

        [$ipAddress, $prefix] = $parts;

        if ($prefix === '' || !\ctype_digit($prefix)) {
            throw new \InvalidArgumentException(
                "Invalid CIDR notation: prefix must be an integer in '{$cidr}'"
            );
        }

        return new self($ipAddress, (int) $prefix);
    }

    /* ==================== *
     * CORE IDENTITY
     * ==================== */

    /**
     * Get the input IP address.
     *
     * @return IPAddress
     */
    public function ipAddress(): IPAddress
    {
        return $this->ip;
    }

    /**
     * Get the CIDR prefix (network size).
     *
     * @return int
     */
    public function networkSize(): int
    {
        return $this->networkSize;
    }

    /**
     * Get the CIDR notation.
     *
     * Note: Returns the input IP with prefix. For canonical form suitable for
     * string comparisons, use networkCidr() instead.
     *
     * @return string e.g., "192.168.1.100/24"
     */
    public function cidr(): string
    {
        return (string) $this->ip . '/' . $this->networkSize;
    }

    /**
     * Get the canonical CIDR notation using the network address.
     *
     * This always returns the network address with prefix, making it suitable
     * for string comparisons and serialization where equal subnets should have
     * identical string representations.
     *
     * @return string e.g., "192.168.1.0/24"
     */
    public function networkCidr(): string
    {
        return (string) $this->networkAddress() . '/' . $this->networkSize;
    }

    /* ==================== *
     * NETWORK ADDRESSES
     * ==================== */

    /**
     * Get the network address (first IP in subnet).
     *
     * @return IPAddress
     */
    public function networkAddress(): IPAddress
    {
        $networkInt = $this->ip->asInteger() & $this->subnetMaskBits;

        return IPAddress::fromInteger($networkInt);
    }

    /**
     * Get the broadcast address (last IP in subnet).
     *
     * @return IPAddress
     */
    public function broadcastAddress(): IPAddress
    {
        $networkInt = $this->ip->asInteger() & $this->subnetMaskBits;
        $hostBits = ~$this->subnetMaskBits & 0xFFFFFFFF;
        $broadcastInt = $networkInt | $hostBits;

        return IPAddress::fromInteger($broadcastInt);
    }

    /**
     * Get the minimum host address.
     *
     * For /32: Returns the IP itself (single host).
     * For /31: Returns the network address (RFC 3021 point-to-point).
     * For others: Returns network address + 1.
     *
     * @return IPAddress
     */
    public function minHost(): IPAddress
    {
        if ($this->networkSize === 32) {
            return $this->ip;
        }

        if ($this->networkSize === 31) {
            return $this->networkAddress();
        }

        $networkInt = $this->ip->asInteger() & $this->subnetMaskBits;

        return IPAddress::fromInteger($networkInt + 1);
    }

    /**
     * Get the maximum host address.
     *
     * For /32: Returns the IP itself (single host).
     * For /31: Returns the broadcast address (RFC 3021 point-to-point).
     * For others: Returns broadcast address - 1.
     *
     * @return IPAddress
     */
    public function maxHost(): IPAddress
    {
        if ($this->networkSize === 32) {
            return $this->ip;
        }

        if ($this->networkSize === 31) {
            return $this->broadcastAddress();
        }

        $broadcastInt = $this->broadcastAddress()->asInteger();

        return IPAddress::fromInteger($broadcastInt - 1);
    }

    /* ==================== *
     * MASKS
     * ==================== */

    /**
     * Get the subnet mask.
     *
     * @return SubnetMask
     */
    public function mask(): SubnetMask
    {
        return new SubnetMask($this->networkSize);
    }

    /**
     * Get the wildcard mask.
     *
     * @return WildcardMask
     */
    public function wildcardMask(): WildcardMask
    {
        return new WildcardMask($this->networkSize);
    }

    /* ==================== *
     * PORTIONS
     * ==================== */

    /**
     * Get the network portion of the input IP.
     *
     * @return IPAddress
     */
    public function networkPortion(): IPAddress
    {
        return $this->networkAddress();
    }

    /**
     * Get the host portion of the input IP.
     *
     * @return IPAddress
     */
    public function hostPortion(): IPAddress
    {
        $hostBits = $this->ip->asInteger() & (~$this->subnetMaskBits & 0xFFFFFFFF);

        return IPAddress::fromInteger($hostBits);
    }

    /* ==================== *
     * RANGES
     * ==================== */

    /**
     * Get the full address range (network to broadcast).
     *
     * @return IPRange
     */
    public function addressRange(): IPRange
    {
        return new IPRange($this->networkAddress(), $this->broadcastAddress());
    }

    /**
     * Get the usable host range.
     *
     * @return IPRange
     */
    public function hostRange(): IPRange
    {
        return new IPRange($this->minHost(), $this->maxHost());
    }

    /* ==================== *
     * COUNTS
     * ==================== */

    /**
     * Get the total number of IP addresses in the subnet.
     *
     * @return int
     */
    public function addressCount(): int
    {
        return $this->networkSize === 0 ? IPv4::ADDRESS_SPACE : (1 << (32 - $this->networkSize));
    }

    /**
     * Get the number of usable host addresses.
     *
     * For /32: Returns 1 (single host).
     * For /31: Returns 2 (RFC 3021 point-to-point).
     * For others: Returns total - 2 (excluding network and broadcast).
     *
     * @return int
     */
    public function hostCount(): int
    {
        if ($this->networkSize === 32) {
            return 1;
        }

        if ($this->networkSize === 31) {
            return 2;
        }

        return $this->addressCount() - 2;
    }

    /* ==================== *
     * CLASSIFICATION
     * ==================== */

    /**
     * Check if the IP address is in a private range.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->ip->isPrivate();
    }

    /**
     * Check if the IP address is publicly routable.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->ip->isPublic();
    }

    /**
     * Check if the IP address is loopback.
     *
     * @return bool
     */
    public function isLoopback(): bool
    {
        return $this->ip->isLoopback();
    }

    /**
     * Check if the IP address is link-local.
     *
     * @return bool
     */
    public function isLinkLocal(): bool
    {
        return $this->ip->isLinkLocal();
    }

    /**
     * Check if the IP address is multicast.
     *
     * @return bool
     */
    public function isMulticast(): bool
    {
        return $this->ip->isMulticast();
    }

    /**
     * Check if the IP address is Carrier-Grade NAT.
     *
     * @return bool
     */
    public function isCarrierGradeNat(): bool
    {
        return $this->ip->isCarrierGradeNat();
    }

    /**
     * Check if the IP address is reserved for documentation.
     *
     * @return bool
     */
    public function isDocumentation(): bool
    {
        return $this->ip->isDocumentation();
    }

    /**
     * Check if the IP address is reserved for benchmarking.
     *
     * @return bool
     */
    public function isBenchmarking(): bool
    {
        return $this->ip->isBenchmarking();
    }

    /**
     * Check if the IP address is reserved.
     *
     * @return bool
     */
    public function isReserved(): bool
    {
        return $this->ip->isReserved();
    }

    /**
     * Check if the IP address is the limited broadcast.
     *
     * @return bool
     */
    public function isLimitedBroadcast(): bool
    {
        return $this->ip->isLimitedBroadcast();
    }

    /**
     * Check if the IP address is in the "this" network.
     *
     * @return bool
     */
    public function isThisNetwork(): bool
    {
        return $this->ip->isThisNetwork();
    }

    /**
     * Check if the IP address is reserved for IETF protocol assignments.
     *
     * @return bool
     */
    public function isIetfProtocol(): bool
    {
        return $this->ip->isIetfProtocol();
    }

    /**
     * Check if the IP address is in the deprecated 6to4 relay range.
     *
     * @return bool
     */
    public function is6to4Relay(): bool
    {
        return $this->ip->is6to4Relay();
    }

    /**
     * Get the address type classification.
     *
     * @return AddressType
     */
    public function addressType(): AddressType
    {
        return $this->ip->addressType();
    }

    /* ==================== *
     * NETWORK CLASS
     * ==================== */

    /**
     * Get the legacy network class.
     *
     * @return NetworkClass
     */
    public function networkClass(): NetworkClass
    {
        return $this->ip->networkClass();
    }

    /**
     * Get the default classful mask for this IP's class.
     *
     * @return string|null Dotted quad mask, or null for Class D/E
     */
    public function defaultClassMask(): ?string
    {
        return $this->ip->networkClass()->getDefaultMask();
    }

    /**
     * Get the default classful prefix for this IP's class.
     *
     * @return int|null CIDR prefix, or null for Class D/E
     */
    public function defaultClassPrefix(): ?int
    {
        return $this->ip->networkClass()->getDefaultPrefix();
    }

    /**
     * Check if the subnet uses the classful default mask.
     *
     * @return bool
     */
    public function isClassful(): bool
    {
        $defaultPrefix = $this->defaultClassPrefix();

        return $defaultPrefix !== null && $this->networkSize === $defaultPrefix;
    }

    /* ==================== *
     * UTILIZATION
     * ==================== */

    /**
     * Get the percentage of addresses that are usable hosts.
     *
     * @return float Percentage (0.0 to 100.0)
     */
    public function usableHostPercentage(): float
    {
        return ((float) $this->hostCount() / (float) $this->addressCount()) * 100.0;
    }

    /**
     * Get the number of addresses not usable as hosts.
     *
     * @return int
     */
    public function unusableAddressCount(): int
    {
        return $this->addressCount() - $this->hostCount();
    }

    /**
     * Calculate utilization for a given host requirement.
     *
     * @param int $requiredHosts Number of hosts needed
     *
     * @return float Percentage (can exceed 100% if insufficient)
     *
     * @throws \InvalidArgumentException If requiredHosts is negative
     */
    public function utilizationFor(int $requiredHosts): float
    {
        if ($requiredHosts < 0) {
            throw new \InvalidArgumentException(
                'Required hosts cannot be negative'
            );
        }

        if ($requiredHosts === 0) {
            return 0.0;
        }

        return ((float) $requiredHosts / (float) $this->hostCount()) * 100.0;
    }

    /**
     * Get wasted addresses for a given host requirement.
     *
     * @param int $requiredHosts Number of hosts needed
     *
     * @return int Unused addresses (negative if insufficient)
     *
     * @throws \InvalidArgumentException If requiredHosts is negative
     */
    public function wastedAddressesFor(int $requiredHosts): int
    {
        if ($requiredHosts < 0) {
            throw new \InvalidArgumentException(
                'Required hosts cannot be negative'
            );
        }

        return $this->hostCount() - $requiredHosts;
    }

    /* ==================== *
     * OPERATIONS
     * ==================== */

    /**
     * Split the subnet into smaller subnets.
     *
     * @param int $newPrefix New CIDR prefix (must be larger than current)
     *
     * @return self[]
     *
     * @throws \InvalidArgumentException If newPrefix is invalid
     */
    public function split(int $newPrefix): array
    {
        if ($newPrefix <= $this->networkSize) {
            throw new \InvalidArgumentException(
                "New prefix must be larger than current prefix ({$this->networkSize})"
            );
        }

        if ($newPrefix > 32) {
            throw new \InvalidArgumentException(
                'New prefix cannot exceed 32'
            );
        }

        $networkInt = $this->networkAddress()->asInteger();
        $addressCount = 1 << (32 - $newPrefix);
        $numSubnets = 1 << ($newPrefix - $this->networkSize);

        $subnets = [];
        for ($i = 0; $i < $numSubnets; $i++) {
            $subnetStart = $networkInt + ($i * $addressCount);
            $subnets[] = new self(\long2ip($subnetStart), $newPrefix);
        }

        return $subnets;
    }

    /**
     * Check if this subnet fully contains another subnet.
     *
     * @param self $other
     *
     * @return bool
     */
    public function contains(self $other): bool
    {
        $thisStart = $this->networkAddressInt();
        $thisEnd = $this->broadcastAddressInt();
        $otherStart = $other->networkAddressInt();
        $otherEnd = $other->broadcastAddressInt();

        return $thisStart <= $otherStart && $thisEnd >= $otherEnd;
    }

    /**
     * Check if this subnet is fully contained within another subnet.
     *
     * @param self $other
     *
     * @return bool
     */
    public function isContainedIn(self $other): bool
    {
        return $other->contains($this);
    }

    /**
     * Check if this subnet overlaps with another subnet.
     *
     * @param self $other
     *
     * @return bool
     */
    public function overlaps(self $other): bool
    {
        $thisStart = $this->networkAddressInt();
        $thisEnd = $this->broadcastAddressInt();
        $otherStart = $other->networkAddressInt();
        $otherEnd = $other->broadcastAddressInt();

        return $thisStart <= $otherEnd && $otherStart <= $thisEnd;
    }

    /**
     * Exclude a subnet from this subnet.
     *
     * @param self $exclude Subnet to exclude
     *
     * @return self[] Remaining subnets
     */
    public function exclude(self $exclude): array
    {
        if (!$this->overlaps($exclude)) {
            return [new self((string) $this->networkAddress(), $this->networkSize)];
        }

        if ($exclude->contains($this)) {
            return [];
        }

        return $this->excludeRecursive($exclude);
    }

    /**
     * Exclude multiple subnets from this subnet.
     *
     * @param self[] $excludes Subnets to exclude
     *
     * @return self[] Remaining subnets
     */
    public function excludeAll(array $excludes): array
    {
        if (empty($excludes)) {
            return [new self((string) $this->networkAddress(), $this->networkSize)];
        }

        $remaining = [new self((string) $this->networkAddress(), $this->networkSize)];

        foreach ($excludes as $exclude) {
            $newRemaining = [];
            foreach ($remaining as $subnet) {
                $afterExclude = $subnet->exclude($exclude);
                foreach ($afterExclude as $s) {
                    $newRemaining[] = $s;
                }
            }
            $remaining = $newRemaining;
        }

        return $remaining;
    }

    /* ==================== *
     * NAVIGATION
     * ==================== */

    /**
     * Get the next subnet of the same size.
     *
     * @return self
     *
     * @throws \RuntimeException If next subnet exceeds IPv4 range
     */
    public function next(): self
    {
        $addressCount = $this->addressCount();
        $currentStart = $this->networkAddressInt();
        $nextStart = $currentStart + $addressCount;

        $maxValid = IPv4::MAX_ADDRESS;
        if ($nextStart > $maxValid || ($nextStart + $addressCount - 1) > $maxValid) {
            throw new \RuntimeException(
                'Next subnet would exceed valid IPv4 address range'
            );
        }

        return new self(\long2ip($nextStart), $this->networkSize);
    }

    /**
     * Get the previous subnet of the same size.
     *
     * @return self
     *
     * @throws \RuntimeException If previous subnet is below 0.0.0.0
     */
    public function previous(): self
    {
        $addressCount = $this->addressCount();
        $currentStart = $this->networkAddress()->asInteger();

        if ($currentStart < $addressCount) {
            throw new \RuntimeException(
                'Previous subnet would be below valid IPv4 address range'
            );
        }

        $previousStart = $currentStart - $addressCount;

        return new self(\long2ip($previousStart), $this->networkSize);
    }

    /**
     * Get multiple adjacent subnets.
     *
     * @param int $count Number of subnets (positive = forward, negative = backward)
     *
     * @return self[]
     */
    public function adjacent(int $count): array
    {
        if ($count === 0) {
            return [];
        }

        $subnets = [];
        $current = $this;

        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $current = $current->next();
                $subnets[] = $current;
            }
        } else {
            for ($i = 0; $i > $count; $i--) {
                $current = $current->previous();
                $subnets[] = $current;
            }
        }

        return $subnets;
    }

    /* ==================== *
     * MEMBERSHIP
     * ==================== */

    /**
     * Check if an IP address is within this subnet.
     *
     * @param string|IPAddress $ip
     *
     * @return bool
     */
    public function containsIP(string|IPAddress $ip): bool
    {
        return $this->addressRange()->contains($ip);
    }

    /* ==================== *
     * COMPARISON
     * ==================== */

    /**
     * Check equality with another subnet.
     *
     * Two subnets are equal if they have the same network address and prefix.
     *
     * @param self $other
     *
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->networkAddress()->equals($other->networkAddress())
            && $this->networkSize === $other->networkSize;
    }

    /* ==================== *
     * OUTPUT
     * ==================== */

    /**
     * Get the reverse DNS (ARPA) domain.
     *
     * @return string
     */
    public function arpaDomain(): string
    {
        return $this->ip->arpaDomain();
    }

    /**
     * Get subnet data as an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return SubnetReport::createArray($this);
    }

    /**
     * Get subnet data as JSON.
     *
     * @return string
     *
     * @throws \JsonException
     */
    public function toJson(): string
    {
        return SubnetReport::createJson($this);
    }

    /**
     * Get a printable report.
     *
     * @return string
     */
    public function toPrintable(): string
    {
        return SubnetReport::createPrintable($this);
    }

    /**
     * String representation (CIDR notation).
     *
     * @return string e.g., "192.168.1.100/24"
     */
    public function __toString(): string
    {
        return $this->cidr();
    }

    /**
     * JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /* ==================== *
     * PRIVATE HELPERS
     * ==================== */

    /**
     * Get the network address as an integer for comparison.
     *
     * @return int
     */
    private function networkAddressInt(): int
    {
        return $this->networkAddress()->asInteger();
    }

    /**
     * Get the broadcast address as an integer for comparison.
     *
     * @return int
     */
    private function broadcastAddressInt(): int
    {
        return $this->broadcastAddress()->asInteger();
    }

    /**
     * Recursively exclude a subnet by splitting into halves.
     *
     * @param self $exclude
     *
     * @return self[]
     */
    private function excludeRecursive(self $exclude): array
    {
        // @codeCoverageIgnoreStart
        if ($this->networkSize >= 32) {
            return [];
        }
        // @codeCoverageIgnoreEnd

        $newPrefix = $this->networkSize + 1;
        $networkInt = $this->networkAddressInt();
        $halfSize = \intdiv($this->addressCount(), 2);

        $firstHalf = new self(\long2ip($networkInt), $newPrefix);

        $secondHalfStart = $networkInt + $halfSize;
        $secondHalf = new self(\long2ip($secondHalfStart), $newPrefix);

        $result = [];

        // Process first half
        if (!$firstHalf->overlaps($exclude)) {
            $result[] = $firstHalf;
        } elseif (!$exclude->contains($firstHalf)) {
            $subResult = $firstHalf->excludeRecursive($exclude);
            foreach ($subResult as $s) {
                $result[] = $s;
            }
        }

        // Process second half
        if (!$secondHalf->overlaps($exclude)) {
            $result[] = $secondHalf;
        } elseif (!$exclude->contains($secondHalf)) {
            $subResult = $secondHalf->excludeRecursive($exclude);
            foreach ($subResult as $s) {
                $result[] = $s;
            }
        }

        return $result;
    }
}
