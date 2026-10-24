<?php

declare(strict_types=1);

namespace IPv4;

use IPv4\Internal\Assert;
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
     * @param string|IPAddress $ipAddress   IP address in dotted quad notation, or an IPAddress
     * @param int              $networkSize CIDR prefix (0-32)
     *
     * @throws \InvalidArgumentException If IP address or network size is invalid
     * @throws \RuntimeException If running on 32-bit PHP
     */
    public function __construct(
        string|IPAddress $ipAddress,
        private readonly int $networkSize,
    ) {
        Platform::ensure64Bit();

        if ($networkSize < 0 || $networkSize > 32) {
            throw new \InvalidArgumentException(
                "Network size must be between 0 and 32, got {$networkSize}"
            );
        }

        $this->ip = $ipAddress instanceof IPAddress ? $ipAddress : new IPAddress($ipAddress);
        $this->subnetMaskBits = (new SubnetMask($networkSize))->asInteger();
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

        $isNegative = \strpos($prefix, '-') === 0 && Assert::isDecimal(\substr($prefix, 1));

        if (!$isNegative && !Assert::isDecimal($prefix)) {
            throw new \InvalidArgumentException(
                "Invalid CIDR notation: prefix must be an integer in '{$cidr}'"
            );
        }

        // Range-check here so an oversized prefix, which (int) clamps to PHP_INT_MAX, is reported as typed
        if ($isNegative || (int) $prefix > 32) {
            throw new \InvalidArgumentException(
                "Invalid CIDR notation: prefix must be between 0 and 32 in '{$cidr}'"
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
     * string comparisons, use networkCidr() or a string cast instead.
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
        return \long2ip($this->networkAddressInt()) . '/' . $this->networkSize;
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
        return IPAddress::fromInteger($this->networkAddressInt());
    }

    /**
     * Get the broadcast address (last IP in subnet).
     *
     * @return IPAddress
     */
    public function broadcastAddress(): IPAddress
    {
        return IPAddress::fromInteger($this->broadcastAddressInt());
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

        return IPAddress::fromInteger($this->networkAddressInt() + 1);
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

        return IPAddress::fromInteger($this->broadcastAddressInt() - 1);
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
        return 1 << (32 - $this->networkSize);
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
     *
     * These classify the input IP address only, not the whole block. A block that
     * straddles a range boundary is classified by whichever side the input IP is on:
     * 10.0.0.0/7 is private, but it also spans the public 11.0.0.0/8. To check a
     * whole block, test it against the range with isContainedIn() or overlaps().
     * ==================== */

    /**
     * Check if the IP address is in a private range.
     *
     * Classifies the input IP only; see the note above on blocks that straddle ranges.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->ip->isPrivate();
    }

    /**
     * Check if the IP address is public: not in any of the special-purpose ranges recognized by this library.
     *
     * Classifies the input IP only; see the note above on blocks that straddle ranges.
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
     * Check if the IP address is in an IANA Special-Purpose Address Registry range.
     *
     * @return bool
     */
    public function isIanaReserved(): bool
    {
        return $this->ip->isIanaReserved();
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
     * Returns 2^(newPrefix - prefix) subnets; use splitIterator() for large splits.
     *
     * @param int $newPrefix New CIDR prefix (must be larger than current)
     *
     * @return self[]
     *
     * @throws \InvalidArgumentException If newPrefix is invalid
     */
    public function split(int $newPrefix): array
    {
        return \iterator_to_array($this->splitIterator($newPrefix), false);
    }

    /**
     * Lazily split the subnet into smaller subnets, one at a time.
     *
     * @param int $newPrefix New CIDR prefix (must be larger than current)
     *
     * @return \Generator<int, self>
     *
     * @throws \InvalidArgumentException If newPrefix is invalid (thrown on call, not on first iteration)
     */
    public function splitIterator(int $newPrefix): \Generator
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

        return $this->generateSplit($newPrefix);
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
        return $this->excludeAll([$exclude]);
    }

    /**
     * Exclude multiple subnets from this subnet.
     *
     * Exclusions may overlap, repeat, or extend beyond this subnet. The remaining space is
     * returned in ascending address order as the fewest aligned CIDR blocks that cover it.
     *
     * @param self[] $excludes Subnets to exclude
     *
     * @return self[] Remaining subnets
     *
     * @throws \InvalidArgumentException If any element is not a Subnet
     */
    public function excludeAll(array $excludes): array
    {
        Assert::allSubnets($excludes);

        $start = $this->networkAddressInt();
        $end = $this->broadcastAddressInt();

        // Clip exclusions to this subnet, then sweep the gaps between them in address order
        $ranges = [];
        foreach ($excludes as $exclude) {
            $excludeStart = $exclude->networkAddressInt();
            $excludeEnd = $exclude->broadcastAddressInt();
            if ($excludeEnd < $start || $excludeStart > $end) {
                continue;
            }
            $ranges[] = [\max($excludeStart, $start), \min($excludeEnd, $end)];
        }
        \sort($ranges);

        $remaining = [];
        $cursor = $start;
        foreach ($ranges as [$rangeStart, $rangeEnd]) {
            if ($rangeStart > $cursor) {
                \array_push($remaining, ...self::rangeToSubnets($cursor, $rangeStart - 1));
            }
            $cursor = \max($cursor, $rangeEnd + 1);
        }
        if ($cursor <= $end) {
            \array_push($remaining, ...self::rangeToSubnets($cursor, $end));
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
        if ($nextStart + $addressCount - 1 > $maxValid) {
            throw new \RuntimeException(
                'Next subnet would exceed valid IPv4 address range'
            );
        }

        return new self(new IPAddress($nextStart), $this->networkSize);
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
        $currentStart = $this->networkAddressInt();

        if ($currentStart < $addressCount) {
            throw new \RuntimeException(
                'Previous subnet would be below valid IPv4 address range'
            );
        }

        $previousStart = $currentStart - $addressCount;

        return new self(new IPAddress($previousStart), $this->networkSize);
    }

    /**
     * Get multiple adjacent subnets.
     *
     * @param int $count Number of subnets (positive = forward, negative = backward)
     *
     * @return self[]
     *
     * @throws \RuntimeException If the requested subnets exceed the IPv4 range
     */
    public function adjacent(int $count): array
    {
        if ($count === 0) {
            return [];
        }

        // Check bounds before allocating anything, so an impossible count fails fast
        // instead of exhausting memory on the way to the boundary.
        $addressCount = $this->addressCount();
        if ($count > 0) {
            $available = \intdiv(IPv4::MAX_ADDRESS - $this->broadcastAddressInt(), $addressCount);
            if ($count > $available) {
                throw new \RuntimeException(
                    'Next subnet would exceed valid IPv4 address range'
                );
            }
        } else {
            $available = \intdiv($this->networkAddressInt(), $addressCount);
            if ($count < -$available) {
                throw new \RuntimeException(
                    'Previous subnet would be below valid IPv4 address range'
                );
            }
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
     *
     * @throws \InvalidArgumentException If $ip is a string that is not a valid IPv4 address
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
        return $this->networkAddressInt() === $other->networkAddressInt()
            && $this->networkSize === $other->networkSize;
    }

    /* ==================== *
     * OUTPUT
     * ==================== */

    /**
     * Get the reverse DNS (ARPA) domain for the input IP address.
     *
     * For the network address's ARPA domain, use networkAddress()->arpaDomain().
     *
     * @return string e.g., "203.112.168.192.in-addr.arpa" for 192.168.112.203/23
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
     * String representation (canonical CIDR notation using the network address).
     *
     * Equal subnets have identical string forms, matching equals() and the reports.
     * Use cidr() for the input IP with prefix.
     *
     * @return string e.g., "192.168.1.0/24"
     */
    public function __toString(): string
    {
        return $this->networkCidr();
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
     * Generate the subnets of a split whose prefix has already been validated.
     *
     * @param int $newPrefix
     *
     * @return \Generator<int, self>
     */
    private function generateSplit(int $newPrefix): \Generator
    {
        $networkInt = $this->networkAddressInt();
        $addressCount = 1 << (32 - $newPrefix);
        $numSubnets = 1 << ($newPrefix - $this->networkSize);

        for ($i = 0; $i < $numSubnets; $i++) {
            yield new self(new IPAddress($networkInt + ($i * $addressCount)), $newPrefix);
        }
    }

    /**
     * Get the network address as an integer for comparison.
     *
     * @return int
     */
    private function networkAddressInt(): int
    {
        return $this->ip->asInteger() & $this->subnetMaskBits;
    }

    /**
     * Get the broadcast address as an integer for comparison.
     *
     * @return int
     */
    private function broadcastAddressInt(): int
    {
        return $this->networkAddressInt() | (~$this->subnetMaskBits & 0xFFFFFFFF);
    }

    /**
     * Cover an address range with the fewest aligned CIDR blocks.
     *
     * Each block is the largest one aligned at the current address that stays within the range.
     *
     * @param int $start First address of the range
     * @param int $end   Last address of the range
     *
     * @return self[] Blocks in ascending address order
     */
    private static function rangeToSubnets(int $start, int $end): array
    {
        $subnets = [];
        while ($start <= $end) {
            $prefix = 32;
            while ($prefix > 0) {
                $parentSize = 1 << (33 - $prefix);
                if ($start % $parentSize !== 0 || $start + $parentSize - 1 > $end) {
                    break;
                }
                $prefix--;
            }

            $subnets[] = new self(new IPAddress($start), $prefix);
            $start += 1 << (32 - $prefix);
        }

        return $subnets;
    }
}
