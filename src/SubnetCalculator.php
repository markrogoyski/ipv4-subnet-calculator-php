<?php

declare(strict_types=1);

namespace IPv4;

/**
 * Network calculator for subnet mask and other classless (CIDR) network information.
 *
 * Given an IP address and CIDR network size, it calculates the following information:
 *   - IP address network subnet masks, network and host portions, and provides aggregated reports.
 *   - Subnet mask
 *   - Network portion
 *   - Host portion
 *   - Number of IP addresses in the network
 *   - Number of addressable hosts in the network
 *   - IP address range
 *   - Broadcast address
 *   - Min and max host
 *   - All IP addresses
 *   - IPv4 ARPA Domain
 * Provides each data in dotted quads, hexadecimal, and binary formats, as well as array of quads.
 *
 * Aggregated network calculation reports:
 *  - Associative array
 *  - JSON
 *  - String
 *  - Printed to STDOUT
 *
 * Special handling for /31 and /32 networks:
 *  - /32: Single host network. Min and max host are the same as the IP address.
 *  - /31: Point-to-point link per RFC 3021. Both addresses are usable hosts (no reserved
 *         network or broadcast addresses). Min host is the lower IP, max host is the higher IP.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc3021 RFC 3021 - Using 31-Bit Prefixes on IPv4 Point-to-Point Links
 */
class SubnetCalculator implements \JsonSerializable
{
    /** @var string IP address as dotted quads: xxx.xxx.xxx.xxx */
    private $ipAddress;

    /** @var int CIDR network size */
    private $networkSize;

    /** @var string[] of four elements containing the four quads of the IP address */
    private $quads = [];

    /** @var int Subnet mask in format used for subnet calculations */
    private $subnetMask;

    /** @var SubnetReportInterface */
    private $report;

    private const FORMAT_QUADS  = '%d';
    private const FORMAT_HEX    = '%02X';
    private const FORMAT_BINARY = '%08b';

    /**
     * Constructor - Takes IP address and network size, validates inputs, and assigns class attributes.
     * For example: 192.168.1.120/24 would be $ip = 192.168.1.120 $network_size = 24
     *
     * @param string                     $ipAddress   IP address in dotted quad notation.
     * @param int                        $networkSize CIDR network size.
     * @param SubnetReportInterface|null $report
     */
    public function __construct(string $ipAddress, int $networkSize, ?SubnetReportInterface $report = null)
    {
        $this->validateInputs($ipAddress, $networkSize);

        $this->ipAddress   = $ipAddress;
        $this->networkSize = $networkSize;
        $this->quads       = \explode('.', $ipAddress);
        $this->subnetMask  = $this->calculateSubnetMask($networkSize);
        $this->report      = $report ?? new SubnetReport();
    }

    /* **************** *
     * PUBLIC INTERFACE
     * **************** */

    /**
     * Get IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string IP address as dotted quads.
     */
    public function getIPAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * Get IP address as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[]
     */
    public function getIPAddressQuads(): array
    {
        return $this->quads;
    }

    /**
     * Get IP address as hexadecimal
     *
     * @return string IP address in hex
     */
    public function getIPAddressHex(): string
    {
        return $this->ipAddressCalculation(self::FORMAT_HEX);
    }

    /**
     * Get IP address as binary
     *
     * @return string IP address in binary
     */
    public function getIPAddressBinary(): string
    {
        return $this->ipAddressCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get the IP address as an integer
     *
     * @return int
     */
    public function getIPAddressInteger(): int
    {
        return $this->convertIpToInt($this->ipAddress);
    }

    /**
     * Get network size
     *
     * @return int network size
     */
    public function getNetworkSize(): int
    {
        return $this->networkSize;
    }

    /**
     * Get the CIDR notation of the subnet: IP Address/Network size.
     * Example: 192.168.0.15/24
     *
     * @return string
     */
    public function getCidrNotation(): string
    {
        return $this->ipAddress . '/' . $this->networkSize;
    }

    /**
     * Get the number of IP addresses in the network
     *
     * @return int Number of IP addresses
     */
    public function getNumberIPAddresses(): int
    {
        return $this->getNumberIPAddressesOfNetworkSize($this->networkSize);
    }

    /**
     * Get the number of addressable hosts in the network
     *
     * For most networks, this is the total IP count minus 2 (network and broadcast addresses).
     * Special cases per RFC 3021:
     *  - /32: Returns 1 (single host network)
     *  - /31: Returns 2 (point-to-point link where both addresses are usable)
     *
     * @return int Number of IP addresses that are addressable
     */
    public function getNumberAddressableHosts(): int
    {
        if ($this->networkSize == 32) {
            return 1;
        }
        if ($this->networkSize == 31) {
            return 2;
        }

        return ($this->getNumberIPAddresses() - 2);
    }

    /**
     * Get range of IP addresses in the network
     *
     * @return string[] containing start and end of IP address range. IP addresses in dotted quad notation.
     */
    public function getIPAddressRange(): array
    {
        return [$this->getNetworkPortion(), $this->getBroadcastAddress()];
    }

    /**
     * Get range of IP addresses in the network
     *
     * @return string[] containing start and end of IP address range. IP addresses in dotted quad notation.
     */
    public function getAddressableHostRange(): array
    {
        return [$this->getMinHost(), $this->getMaxHost()];
    }

    /**
     * Get the broadcast IP address
     *
     * @return string IP address as dotted quads
     */
    public function getBroadcastAddress(): string
    {
        $network_quads       = $this->getNetworkPortionQuads();
        $number_ip_addresses = $this->getNumberIPAddresses();

        $network_range_quads = [
            \sprintf(self::FORMAT_QUADS, ((int) $network_quads[0] & ($this->subnetMask >> 24)) + ((($number_ip_addresses - 1) >> 24) & 0xFF)),
            \sprintf(self::FORMAT_QUADS, ((int) $network_quads[1] & ($this->subnetMask >> 16)) + ((($number_ip_addresses - 1) >> 16) & 0xFF)),
            \sprintf(self::FORMAT_QUADS, ((int) $network_quads[2] & ($this->subnetMask >>  8)) + ((($number_ip_addresses - 1) >>  8) & 0xFF)),
            \sprintf(self::FORMAT_QUADS, ((int) $network_quads[3] & ($this->subnetMask >>  0)) + ((($number_ip_addresses - 1) >>  0) & 0xFF)),
        ];

        return \implode('.', $network_range_quads);
    }

    /**
     * Get minimum host IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * For most networks, this is the network address + 1.
     * Special cases:
     *  - /32: Returns the IP address itself (single host)
     *  - /31: Returns the network portion (lower IP of the point-to-point pair per RFC 3021)
     *
     * @return string min host as dotted quads
     */
    public function getMinHost(): string
    {
        if ($this->networkSize === 32) {
            return $this->ipAddress;
        }
        if ($this->networkSize === 31) {
            return $this->getNetworkPortion();
        }
        return $this->minHostCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get minimum host IP address as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[] min host portion as dotted quads.
     */
    public function getMinHostQuads(): array
    {
        if ($this->networkSize === 32) {
            return $this->quads;
        }
        if ($this->networkSize === 31) {
            return $this->getNetworkPortionQuads();
        }
        return \explode('.', $this->minHostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get minimum host IP address as hex
     *
     * @return string min host portion as hex
     */
    public function getMinHostHex(): string
    {
        if ($this->networkSize === 32) {
            return \implode('', \array_map(
                function ($quad) {
                    return \sprintf(self::FORMAT_HEX, $quad);
                },
                $this->quads
            ));
        }
        if ($this->networkSize === 31) {
            return $this->getNetworkPortionHex();
        }
        return $this->minHostCalculation(self::FORMAT_HEX);
    }

    /**
     * Get minimum host IP address as binary
     *
     * @return string min host portion as binary
     */
    public function getMinHostBinary(): string
    {
        if ($this->networkSize === 32) {
            return \implode('', \array_map(
                function ($quad) {
                    return \sprintf(self::FORMAT_BINARY, $quad);
                },
                $this->quads
            ));
        }
        if ($this->networkSize === 31) {
            return $this->getNetworkPortionBinary();
        }
        return $this->minHostCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get minimum host IP address as an Integer
     *
     * @return int min host portion as integer
     */
    public function getMinHostInteger(): int
    {
        if ($this->networkSize === 32) {
            return $this->convertIpToInt(\implode('.', $this->quads));
        }
        if ($this->networkSize === 31) {
            return $this->getNetworkPortionInteger();
        }
        return $this->convertIpToInt($this->minHostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get maximum host IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * For most networks, this is the broadcast address - 1.
     * Special cases:
     *  - /32: Returns the IP address itself (single host)
     *  - /31: Returns the broadcast address (higher IP of the point-to-point pair per RFC 3021)
     *
     * @return string max host as dotted quads.
     */
    public function getMaxHost(): string
    {
        if ($this->networkSize === 32) {
            return $this->ipAddress;
        }
        if ($this->networkSize === 31) {
            return $this->getBroadcastAddress();
        }
        return $this->maxHostCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get maximum host IP address as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[] max host portion as dotted quads
     */
    public function getMaxHostQuads(): array
    {
        if ($this->networkSize === 32) {
            return $this->quads;
        }
        if ($this->networkSize === 31) {
            return \explode('.', $this->getBroadcastAddress());
        }
        return \explode('.', $this->maxHostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get maximum host IP address as hex
     *
     * @return string max host portion as hex
     */
    public function getMaxHostHex(): string
    {
        if ($this->networkSize === 32) {
            return \implode('', \array_map(
                function ($quad) {
                    return \sprintf(self::FORMAT_HEX, $quad);
                },
                $this->quads
            ));
        }
        if ($this->networkSize === 31) {
            return \implode('', \array_map(
                function ($quad) {
                    return \sprintf(self::FORMAT_HEX, $quad);
                },
                \explode('.', $this->getBroadcastAddress())
            ));
        }
        return $this->maxHostCalculation(self::FORMAT_HEX);
    }

    /**
     * Get maximum host IP address as binary
     *
     * @return string max host portion as binary
     */
    public function getMaxHostBinary(): string
    {
        if ($this->networkSize === 32) {
            return \implode('', \array_map(
                function ($quad) {
                    return \sprintf(self::FORMAT_BINARY, $quad);
                },
                $this->quads
            ));
        }
        if ($this->networkSize === 31) {
            return \implode('', \array_map(
                function ($quad) {
                    return \sprintf(self::FORMAT_BINARY, $quad);
                },
                \explode('.', $this->getBroadcastAddress())
            ));
        }
        return $this->maxHostCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get maximum host IP address as an Integer
     *
     * @return int max host portion as integer
     */
    public function getMaxHostInteger(): int
    {
        if ($this->networkSize === 32) {
            return $this->convertIpToInt(\implode('.', $this->quads));
        }
        if ($this->networkSize === 31) {
            return $this->convertIpToInt($this->getBroadcastAddress());
        }
        return $this->convertIpToInt($this->maxHostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get subnet mask as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string subnet mask as dotted quads
     */
    public function getSubnetMask(): string
    {
        return $this->subnetCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get subnet mask as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[] of four elements containing the four quads of the subnet mask.
     */
    public function getSubnetMaskQuads(): array
    {
        return \explode('.', $this->subnetCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get subnet mask as hexadecimal
     *
     * @return string subnet mask in hex
     */
    public function getSubnetMaskHex(): string
    {
        return $this->subnetCalculation(self::FORMAT_HEX);
    }

    /**
     * Get subnet mask as binary
     *
     * @return string subnet mask in binary
     */
    public function getSubnetMaskBinary(): string
    {
        return $this->subnetCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get subnet mask as an integer
     *
     * @return int
     */
    public function getSubnetMaskInteger(): int
    {
        return $this->convertIpToInt($this->subnetCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get wildcard mask as dotted quads: xxx.xxx.xxx.xxx
     *
     * The wildcard mask is the inverse of the subnet mask, commonly used in
     * Cisco ACLs (Access Control Lists) and OSPF network statements.
     * For a /24 (subnet mask 255.255.255.0), the wildcard mask is 0.0.0.255.
     *
     * @see https://www.cisco.com/c/en/us/support/docs/security/ios-firewall/23602-confaccesslists.html Cisco IOS ACL Configuration Guide
     *
     * @return string wildcard mask as dotted quads
     */
    public function getWildcardMask(): string
    {
        return $this->wildcardCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get wildcard mask as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[] of four elements containing the four quads of the wildcard mask.
     */
    public function getWildcardMaskQuads(): array
    {
        return \explode('.', $this->wildcardCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get wildcard mask as hexadecimal
     *
     * @return string wildcard mask in hex
     */
    public function getWildcardMaskHex(): string
    {
        return $this->wildcardCalculation(self::FORMAT_HEX);
    }

    /**
     * Get wildcard mask as binary
     *
     * @return string wildcard mask in binary
     */
    public function getWildcardMaskBinary(): string
    {
        return $this->wildcardCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get wildcard mask as an integer
     *
     * @return int
     */
    public function getWildcardMaskInteger(): int
    {
        return $this->convertIpToInt($this->wildcardCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Split the network into smaller networks
     *
     * @param int $networkSize
     * @return SubnetCalculator[]
     */
    public function split(int $networkSize): array
    {
        if ($networkSize <= $this->networkSize) {
            throw new \RuntimeException('New networkSize must be larger than the base networkSize.');
        }

        if ($networkSize > 32) {
            throw new \RuntimeException('New networkSize must be smaller than the maximum networkSize.');
        }

        [$startIp, $endIp] = $this->getIPAddressRangeAsInts();

        $addressCount = $this->getNumberIPAddressesOfNetworkSize($networkSize);

        $ranges = [];
        for ($ip = $startIp; $ip <= $endIp; $ip += $addressCount) {
            $ranges[] = new SubnetCalculator($this->convertIpToDottedQuad($ip), $networkSize);
        }

        return $ranges;
    }

    /**
     * Get network portion of IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string network portion as dotted quads
     */
    public function getNetworkPortion(): string
    {
        return $this->networkCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get network portion as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[] of four elements containing the four quads of the network portion
     */
    public function getNetworkPortionQuads(): array
    {
        return \explode('.', $this->networkCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get network portion of IP address as hexadecimal
     *
     * @return string network portion in hex
     */
    public function getNetworkPortionHex(): string
    {
        return $this->networkCalculation(self::FORMAT_HEX);
    }

    /**
     * Get network portion of IP address as binary
     *
     * @return string network portion in binary
     */
    public function getNetworkPortionBinary(): string
    {
        return $this->networkCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get network portion of IP address as an integer
     *
     * @return int
     */
    public function getNetworkPortionInteger(): int
    {
        return $this->convertIpToInt($this->networkCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get host portion of IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string host portion as dotted quads
     */
    public function getHostPortion(): string
    {
        return $this->hostCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get host portion as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[] of four elements containing the four quads of the host portion
     */
    public function getHostPortionQuads(): array
    {
        return \explode('.', $this->hostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get host portion of IP address as hexadecimal
     *
     * @return string host portion in hex
     */
    public function getHostPortionHex(): string
    {
        return $this->hostCalculation(self::FORMAT_HEX);
    }

    /**
     * Get host portion of IP address as binary
     *
     * @return string host portion in binary
     */
    public function getHostPortionBinary(): string
    {
        return $this->hostCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get host portion of IP address as an integer
     *
     * @return int
     */
    public function getHostPortionInteger(): int
    {
        return $this->convertIpToInt($this->hostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get all IP addresses
     *
     * @return \Generator|string[]|false[]
     */
    public function getAllIPAddresses(): \Generator
    {
        [$startIp, $endIp] = $this->getIPAddressRangeAsInts();

        for ($ip = $startIp; $ip <= $endIp; $ip++) {
            yield $this->convertIpToDottedQuad($ip);
        }
    }

    /**
     * Get all host IP addresses
     * Removes broadcast and network address if they exist.
     *
     * @return \Generator|string[]|false[]
     *
     * @throws \RuntimeException if there is an error in the IP address range calculation
     */
    public function getAllHostIPAddresses(): \Generator
    {
        [$startIp, $endIp] = $this->getIPAddressRangeAsInts();

        if ($this->getNetworkSize() < 31) {
            $startIp += 1;
            $endIp   -= 1;
        }

        for ($ip = $startIp; $ip <= $endIp; $ip++) {
            yield $this->convertIpToDottedQuad($ip);
        }
    }

    /**
     * Is the IP address in the subnet?
     *
     * @param string $ipAddressString
     *
     * @return bool
     */
    public function isIPAddressInSubnet(string $ipAddressString): bool
    {
        $ipAddress = \ip2long($ipAddressString);
        [$startIp, $endIp] = $this->getIPAddressRangeAsInts();

        return $ipAddress >= $startIp && $ipAddress <= $endIp;
    }

    /**
     * Check if this subnet overlaps with another subnet.
     *
     * Two subnets overlap if they share any IP addresses.
     * This is useful for network planning and conflict prevention,
     * including firewall rule validation and routing table conflict detection.
     *
     * @param SubnetCalculator $other The other subnet to compare against
     *
     * @return bool True if the subnets share any IP addresses
     */
    public function overlaps(SubnetCalculator $other): bool
    {
        [$thisStart, $thisEnd] = $this->getIPAddressRangeAsInts();
        [$otherStart, $otherEnd] = $other->getIPAddressRangeAsInts();

        // Two ranges overlap if one starts before the other ends and vice versa
        return $thisStart <= $otherEnd && $otherStart <= $thisEnd;
    }

    /**
     * Check if this subnet fully contains another subnet.
     *
     * A subnet contains another if all IP addresses in the contained subnet
     * are also within this subnet's range.
     *
     * @param SubnetCalculator $other The subnet to check for containment
     *
     * @return bool True if this subnet fully contains the other subnet
     */
    public function contains(SubnetCalculator $other): bool
    {
        [$thisStart, $thisEnd] = $this->getIPAddressRangeAsInts();
        [$otherStart, $otherEnd] = $other->getIPAddressRangeAsInts();

        // This subnet contains other if other's entire range is within this range
        return $thisStart <= $otherStart && $thisEnd >= $otherEnd;
    }

    /**
     * Check if this subnet is fully contained within another subnet.
     *
     * This is the inverse of contains(): $a->isContainedIn($b) === $b->contains($a)
     *
     * @param SubnetCalculator $other The subnet to check if this subnet is within
     *
     * @return bool True if this subnet is fully contained within the other subnet
     */
    public function isContainedIn(SubnetCalculator $other): bool
    {
        return $other->contains($this);
    }

    /* ****************************** *
     * EXCLUDE/DIFFERENCE OPERATIONS
     * ****************************** */

    /**
     * Exclude a subnet from this subnet.
     *
     * Returns an array of subnets representing the remainder after removing
     * the excluded subnet. Useful for carving out reserved ranges.
     *
     * The result is the minimal set of CIDR blocks covering the remaining space.
     * If the subnets don't overlap, returns this subnet unchanged.
     * If the excluded subnet fully contains this subnet, returns an empty array.
     *
     * @param SubnetCalculator $exclude Subnet to exclude
     *
     * @return SubnetCalculator[] Remaining subnets (empty if fully excluded)
     */
    public function exclude(SubnetCalculator $exclude): array
    {
        // If no overlap, return this subnet unchanged
        if (!$this->overlaps($exclude)) {
            return [new SubnetCalculator($this->getNetworkPortion(), $this->networkSize)];
        }

        // If exclude fully contains this subnet, nothing remains
        if ($exclude->contains($this)) {
            return [];
        }

        // If this subnet fully contains exclude, we need to split
        // Recursively split this subnet in half until we isolate the excluded portion
        return $this->excludeRecursive($exclude);
    }

    /**
     * Exclude multiple subnets from this subnet.
     *
     * Applies multiple exclusions sequentially. The result is the set of subnets
     * remaining after all exclusions are applied.
     *
     * @param SubnetCalculator[] $excludes Subnets to exclude
     *
     * @return SubnetCalculator[] Remaining subnets
     */
    public function excludeAll(array $excludes): array
    {
        if (empty($excludes)) {
            return [new SubnetCalculator($this->getNetworkPortion(), $this->networkSize)];
        }

        // Start with this subnet
        $remaining = [new SubnetCalculator($this->getNetworkPortion(), $this->networkSize)];

        // Apply each exclusion to all remaining subnets
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

    /**
     * Recursively exclude a subnet by splitting into halves.
     *
     * This method splits the current subnet into two halves and recursively
     * processes each half. Halves that don't overlap with the exclusion are
     * kept as-is. Halves that are fully contained by the exclusion are discarded.
     * Halves that partially overlap are split further.
     *
     * @param SubnetCalculator $exclude Subnet to exclude
     *
     * @return SubnetCalculator[] Remaining subnets
     */
    private function excludeRecursive(SubnetCalculator $exclude): array
    {
        // Can't split smaller than /32
        if ($this->networkSize >= 32) {
            // This /32 overlaps with exclude, so it's excluded
            return [];
        }

        // Split into two halves
        $newPrefix = $this->networkSize + 1;
        $firstHalf = new SubnetCalculator($this->getNetworkPortion(), $newPrefix);
        $secondHalfStart = $this->getNetworkPortionInteger() + ($this->getNumberIPAddresses() / 2);
        // Handle signed/unsigned conversion
        $secondHalfIp = $this->convertIpToDottedQuad((int) $secondHalfStart);
        $secondHalf = new SubnetCalculator($secondHalfIp, $newPrefix);

        $result = [];

        // Process first half
        if (!$firstHalf->overlaps($exclude)) {
            // No overlap, keep the whole half
            $result[] = $firstHalf;
        } elseif ($exclude->contains($firstHalf)) {
            // Fully excluded, discard
        } else {
            // Partial overlap, recurse
            $subResult = $firstHalf->excludeRecursive($exclude);
            foreach ($subResult as $s) {
                $result[] = $s;
            }
        }

        // Process second half
        if (!$secondHalf->overlaps($exclude)) {
            // No overlap, keep the whole half
            $result[] = $secondHalf;
        } elseif ($exclude->contains($secondHalf)) {
            // Fully excluded, discard
        } else {
            // Partial overlap, recurse
            $subResult = $secondHalf->excludeRecursive($exclude);
            foreach ($subResult as $s) {
                $result[] = $s;
            }
        }

        return $result;
    }

    /* ****************************** *
     * ADJACENT SUBNET NAVIGATION
     * ****************************** */

    /**
     * Get the next subnet of the same size.
     *
     * Returns a new SubnetCalculator representing the subnet immediately following
     * this one in the IP address space, using the same network size (CIDR prefix).
     *
     * Useful for sequential IP allocation and network expansion planning.
     *
     * @return SubnetCalculator The next adjacent subnet
     *
     * @throws \RuntimeException If the next subnet would exceed the valid IPv4 range (255.255.255.255)
     */
    public function getNextSubnet(): SubnetCalculator
    {
        $addressCount = $this->getNumberIPAddresses();
        $currentNetworkStart = $this->getNetworkPortionInteger();

        // Calculate next subnet start as unsigned integer
        $nextNetworkStartUnsigned = (int) \sprintf('%u', $currentNetworkStart) + $addressCount;

        // Check if we would exceed the valid IPv4 range (max is 4294967295 = 255.255.255.255)
        // The next subnet start must be within valid range, AND the entire subnet must fit
        $maxValidIp = 4294967295; // 0xFFFFFFFF
        if ($nextNetworkStartUnsigned > $maxValidIp || ($nextNetworkStartUnsigned + $addressCount - 1) > $maxValidIp) {
            throw new \RuntimeException('Next subnet would exceed valid IPv4 address range.');
        }

        // Convert back to signed int for long2ip
        $nextNetworkStart = $nextNetworkStartUnsigned > 2147483647
            ? $nextNetworkStartUnsigned - 4294967296
            : $nextNetworkStartUnsigned;

        $nextIp = $this->convertIpToDottedQuad((int) $nextNetworkStart);

        return new SubnetCalculator($nextIp, $this->networkSize);
    }

    /**
     * Get the previous subnet of the same size.
     *
     * Returns a new SubnetCalculator representing the subnet immediately preceding
     * this one in the IP address space, using the same network size (CIDR prefix).
     *
     * Useful for navigating backward through allocated IP ranges.
     *
     * @return SubnetCalculator The previous adjacent subnet
     *
     * @throws \RuntimeException If the previous subnet would be below 0.0.0.0
     */
    public function getPreviousSubnet(): SubnetCalculator
    {
        $addressCount = $this->getNumberIPAddresses();
        $currentNetworkStart = $this->getNetworkPortionInteger();

        // Convert to unsigned for calculation
        $currentNetworkStartUnsigned = (int) \sprintf('%u', $currentNetworkStart);

        // Check if we would go below 0.0.0.0
        if ($currentNetworkStartUnsigned < $addressCount) {
            throw new \RuntimeException('Previous subnet would be below valid IPv4 address range (0.0.0.0).');
        }

        $previousNetworkStartUnsigned = $currentNetworkStartUnsigned - $addressCount;

        // Convert back to signed int for long2ip
        $previousNetworkStart = $previousNetworkStartUnsigned > 2147483647
            ? $previousNetworkStartUnsigned - 4294967296
            : $previousNetworkStartUnsigned;

        $previousIp = $this->convertIpToDottedQuad((int) $previousNetworkStart);

        return new SubnetCalculator($previousIp, $this->networkSize);
    }

    /**
     * Get multiple adjacent subnets.
     *
     * Returns an array of SubnetCalculator objects representing adjacent subnets
     * of the same size. Positive count returns subnets forward (higher IPs),
     * negative count returns subnets backward (lower IPs).
     *
     * Useful for bulk allocation planning or viewing a range of subnets.
     *
     * @param int $count Number of subnets to return (positive = forward, negative = backward)
     *
     * @return SubnetCalculator[] Array of adjacent subnets
     *
     * @throws \RuntimeException If any requested subnet would exceed valid IPv4 range
     */
    public function getAdjacentSubnets(int $count): array
    {
        if ($count === 0) {
            return [];
        }

        $subnets = [];
        $current = $this;

        if ($count > 0) {
            // Forward direction
            for ($i = 0; $i < $count; $i++) {
                $current = $current->getNextSubnet();
                $subnets[] = $current;
            }
        } else {
            // Backward direction
            for ($i = 0; $i > $count; $i--) {
                $current = $current->getPreviousSubnet();
                $subnets[] = $current;
            }
        }

        return $subnets;
    }

    /* ******************************************* *
     * PRIVATE/RESERVED IP RANGE DETECTION METHODS
     * ******************************************* */

    /**
     * Check if the IP address is in a private range (RFC 1918).
     *
     * Private address ranges:
     *   - 10.0.0.0/8     (10.0.0.0 - 10.255.255.255)
     *   - 172.16.0.0/12  (172.16.0.0 - 172.31.255.255)
     *   - 192.168.0.0/16 (192.168.0.0 - 192.168.255.255)
     *
     * @link https://datatracker.ietf.org/doc/html/rfc1918 RFC 1918 - Address Allocation for Private Internets
     *
     * @return bool True if the IP address is in a private range
     */
    public function isPrivate(): bool
    {
        $ip = $this->getIPAddressInteger();

        // 10.0.0.0/8: 10.0.0.0 - 10.255.255.255
        if ($this->isInRange($ip, 0x0A000000, 0x0AFFFFFF)) {
            return true;
        }

        // 172.16.0.0/12: 172.16.0.0 - 172.31.255.255
        if ($this->isInRange($ip, 0xAC100000, 0xAC1FFFFF)) {
            return true;
        }

        // 192.168.0.0/16: 192.168.0.0 - 192.168.255.255
        if ($this->isInRange($ip, 0xC0A80000, 0xC0A8FFFF)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the IP address is publicly routable.
     *
     * An IP is public if it is not in any of the special-purpose address ranges:
     * private, loopback, link-local, multicast, CGN, documentation, benchmarking,
     * reserved, limited broadcast, or "this" network.
     *
     * @return bool True if the IP address is publicly routable
     */
    public function isPublic(): bool
    {
        return !$this->isPrivate()
            && !$this->isLoopback()
            && !$this->isLinkLocal()
            && !$this->isMulticast()
            && !$this->isCarrierGradeNat()
            && !$this->isDocumentation()
            && !$this->isBenchmarking()
            && !$this->isReserved()
            && !$this->isThisNetwork();
    }

    /**
     * Check if the IP address is in the loopback range (127.0.0.0/8).
     *
     * @link https://datatracker.ietf.org/doc/html/rfc1122 RFC 1122 - Requirements for Internet Hosts
     *
     * @return bool True if the IP address is in the loopback range
     */
    public function isLoopback(): bool
    {
        $ip = $this->getIPAddressInteger();

        // 127.0.0.0/8: 127.0.0.0 - 127.255.255.255
        return $this->isInRange($ip, 0x7F000000, 0x7FFFFFFF);
    }

    /**
     * Check if the IP address is link-local (169.254.0.0/16).
     *
     * Link-local addresses are used for automatic private IP addressing (APIPA)
     * when DHCP is not available.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc3927 RFC 3927 - Dynamic Configuration of IPv4 Link-Local Addresses
     *
     * @return bool True if the IP address is link-local
     */
    public function isLinkLocal(): bool
    {
        $ip = $this->getIPAddressInteger();

        // 169.254.0.0/16: 169.254.0.0 - 169.254.255.255
        return $this->isInRange($ip, 0xA9FE0000, 0xA9FEFFFF);
    }

    /**
     * Check if the IP address is multicast (224.0.0.0/4).
     *
     * @link https://datatracker.ietf.org/doc/html/rfc5771 RFC 5771 - IANA Guidelines for IPv4 Multicast Address Assignments
     *
     * @return bool True if the IP address is multicast
     */
    public function isMulticast(): bool
    {
        $ip = $this->getIPAddressInteger();

        // 224.0.0.0/4: 224.0.0.0 - 239.255.255.255
        return $this->isInRange($ip, 0xE0000000, 0xEFFFFFFF);
    }

    /**
     * Check if the IP address is in Carrier-Grade NAT range (100.64.0.0/10).
     *
     * Also known as Shared Address Space, used by ISPs for CGN deployments.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc6598 RFC 6598 - IANA-Reserved IPv4 Prefix for Shared Address Space
     *
     * @return bool True if the IP address is in the CGN range
     */
    public function isCarrierGradeNat(): bool
    {
        $ip = $this->getIPAddressInteger();

        // 100.64.0.0/10: 100.64.0.0 - 100.127.255.255
        return $this->isInRange($ip, 0x64400000, 0x647FFFFF);
    }

    /**
     * Check if the IP address is reserved for documentation (RFC 5737).
     *
     * Documentation ranges (TEST-NET-1, TEST-NET-2, TEST-NET-3):
     *   - 192.0.2.0/24   (TEST-NET-1)
     *   - 198.51.100.0/24 (TEST-NET-2)
     *   - 203.0.113.0/24  (TEST-NET-3)
     *
     * @link https://datatracker.ietf.org/doc/html/rfc5737 RFC 5737 - IPv4 Address Blocks Reserved for Documentation
     *
     * @return bool True if the IP address is reserved for documentation
     */
    public function isDocumentation(): bool
    {
        $ip = $this->getIPAddressInteger();

        // 192.0.2.0/24 (TEST-NET-1): 192.0.2.0 - 192.0.2.255
        if ($this->isInRange($ip, 0xC0000200, 0xC00002FF)) {
            return true;
        }

        // 198.51.100.0/24 (TEST-NET-2): 198.51.100.0 - 198.51.100.255
        if ($this->isInRange($ip, 0xC6336400, 0xC63364FF)) {
            return true;
        }

        // 203.0.113.0/24 (TEST-NET-3): 203.0.113.0 - 203.0.113.255
        if ($this->isInRange($ip, 0xCB007100, 0xCB0071FF)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the IP address is reserved for benchmarking (198.18.0.0/15).
     *
     * @link https://datatracker.ietf.org/doc/html/rfc2544 RFC 2544 - Benchmarking Methodology for Network Interconnect Devices
     *
     * @return bool True if the IP address is reserved for benchmarking
     */
    public function isBenchmarking(): bool
    {
        $ip = $this->getIPAddressInteger();

        // 198.18.0.0/15: 198.18.0.0 - 198.19.255.255
        return $this->isInRange($ip, 0xC6120000, 0xC613FFFF);
    }

    /**
     * Check if the IP address is reserved for future use (240.0.0.0/4).
     *
     * Note: This includes 255.255.255.255 (limited broadcast), which can be
     * separately identified using isLimitedBroadcast().
     *
     * @link https://datatracker.ietf.org/doc/html/rfc1112 RFC 1112 - Host Extensions for IP Multicasting
     *
     * @return bool True if the IP address is reserved for future use
     */
    public function isReserved(): bool
    {
        $ip = $this->getIPAddressInteger();

        // 240.0.0.0/4: 240.0.0.0 - 255.255.255.255
        return $this->isInRange($ip, 0xF0000000, 0xFFFFFFFF);
    }

    /**
     * Check if the IP address is the broadcast address (255.255.255.255/32).
     *
     * @link https://datatracker.ietf.org/doc/html/rfc919 RFC 919 - Broadcasting Internet Datagrams
     *
     * @return bool True if the IP address is the limited broadcast address
     */
    public function isLimitedBroadcast(): bool
    {
        return $this->ipAddress === '255.255.255.255';
    }

    /**
     * Check if the IP address is in the "this" network range (0.0.0.0/8).
     *
     * Addresses in this range represent "this host on this network" and are
     * only valid as source addresses.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc1122 RFC 1122 - Requirements for Internet Hosts
     *
     * @return bool True if the IP address is in the "this" network range
     */
    public function isThisNetwork(): bool
    {
        $ip = $this->getIPAddressInteger();

        // 0.0.0.0/8: 0.0.0.0 - 0.255.255.255
        return $this->isInRange($ip, 0x00000000, 0x00FFFFFF);
    }

    /**
     * Get the address type classification.
     *
     * Returns a string identifying the type of address. The order of checks matters:
     * more specific types (like limited-broadcast) are checked before broader types
     * (like reserved) that would also match.
     *
     * @return string Address type: 'private', 'public', 'loopback', 'link-local',
     *                'multicast', 'carrier-grade-nat', 'documentation', 'benchmarking',
     *                'reserved', 'limited-broadcast', 'this-network'
     */
    public function getAddressType(): string
    {
        // Check specific types first, then broader types
        if ($this->isThisNetwork()) {
            return 'this-network';
        }
        if ($this->isPrivate()) {
            return 'private';
        }
        if ($this->isLoopback()) {
            return 'loopback';
        }
        if ($this->isLinkLocal()) {
            return 'link-local';
        }
        if ($this->isCarrierGradeNat()) {
            return 'carrier-grade-nat';
        }
        if ($this->isDocumentation()) {
            return 'documentation';
        }
        if ($this->isBenchmarking()) {
            return 'benchmarking';
        }
        if ($this->isMulticast()) {
            return 'multicast';
        }
        // Check limited broadcast before reserved (since it's a subset)
        if ($this->isLimitedBroadcast()) {
            return 'limited-broadcast';
        }
        if ($this->isReserved()) {
            return 'reserved';
        }

        return 'public';
    }

    /**
     * Check if an IP integer is within a given range (inclusive).
     *
     * @param int $ip    IP address as integer
     * @param int $start Start of range as integer
     * @param int $end   End of range as integer
     *
     * @return bool True if the IP is within the range
     */
    private function isInRange(int $ip, int $start, int $end): bool
    {
        // Handle PHP's signed integer representation for high IP addresses
        // Convert to unsigned for comparison using sprintf
        $ipUnsigned    = \sprintf('%u', $ip);
        $startUnsigned = \sprintf('%u', $start);
        $endUnsigned   = \sprintf('%u', $end);

        return $ipUnsigned >= $startUnsigned && $ipUnsigned <= $endUnsigned;
    }

    /**
     * Get the IPv4 Arpa Domain
     *
     * Reverse DNS lookups for IPv4 addresses use the special domain in-addr.arpa.
     * In this domain, an IPv4 address is represented as a concatenated sequence of four decimal numbers,
     * separated by dots, to which is appended the second level domain suffix .in-addr.arpa.
     *
     * The four decimal numbers are obtained by splitting the 32-bit IPv4 address into four octets and converting
     * each octet into a decimal number. These decimal numbers are then concatenated in the order:
     * least significant octet first (leftmost), to most significant octet last (rightmost).
     * It is important to note that this is the reverse order to the usual dotted-decimal convention for writing
     * IPv4 addresses in textual form.
     *
     * Ex: to do a reverse lookup of the IP address 8.8.4.4 the PTR record for the domain name 4.4.8.8.in-addr.arpa would be looked up.
     *
     * @link https://en.wikipedia.org/wiki/Reverse_DNS_lookup
     *
     * @return string
     */
    public function getIPv4ArpaDomain(): string
    {
        $reverseQuads = \implode('.', \array_reverse($this->quads));
        return $reverseQuads . '.in-addr.arpa';
    }

    /**
     * Get subnet calculations as an associated array
     *
     * @return mixed[] of subnet calculations
     */
    public function getSubnetArrayReport(): array
    {
        return $this->report->createArrayReport($this);
    }

    /**
     * Get subnet calculations as a JSON string
     *
     * @return string JSON string of subnet calculations
     *
     * @throws \RuntimeException if there is a JSON encode error
     */
    public function getSubnetJsonReport(): string
    {
        $json = $this->report->createJsonReport($this);

        if ($json === false) {
            throw new \RuntimeException('JSON report failure: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Print a report of subnet calculations
     */
    public function printSubnetReport(): void
    {
        $this->report->printReport($this);
    }

    /**
     * Print a report of subnet calculations
     *
     * @return string Subnet Calculator report
     */
    public function getPrintableReport(): string
    {
        return $this->report->createPrintableReport($this);
    }

    /**
     * String representation of a report of subnet calculations
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->report->createPrintableReport($this);
    }

    /* ************** *
     * PHP INTERFACES
     * ************** */

    /**
     * \JsonSerializable interface
     *
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return $this->report->createArrayReport($this);
    }

    /* ********************** *
     * PRIVATE IMPLEMENTATION
     * ********************** */

    /**
     * Calculate subnet mask
     *
     * @param  int $networkSize
     *
     * @return int
     */
    private function calculateSubnetMask(int $networkSize): int
    {
        return 0xFFFFFFFF << (32 - $networkSize);
    }

    /**
     * Calculate IP address for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted IP address
     */
    private function ipAddressCalculation(string $format, string $separator = ''): string
    {
        return \implode($separator, array_map(
            function ($quad) use ($format) {
                return \sprintf($format, $quad);
            },
            $this->quads
        ));
    }

    /**
     * Subnet calculation
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string subnet
     */
    private function subnetCalculation(string $format, string $separator = ''): string
    {
        $maskQuads = [
            \sprintf($format, ($this->subnetMask >> 24) & 0xFF),
            \sprintf($format, ($this->subnetMask >> 16) & 0xFF),
            \sprintf($format, ($this->subnetMask >>  8) & 0xFF),
            \sprintf($format, ($this->subnetMask >>  0) & 0xFF),
        ];

        return implode($separator, $maskQuads);
    }

    /**
     * Wildcard mask calculation
     *
     * The wildcard mask is the bitwise inverse of the subnet mask.
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string wildcard mask
     */
    private function wildcardCalculation(string $format, string $separator = ''): string
    {
        $wildcardMask = ~$this->subnetMask;
        $maskQuads = [
            \sprintf($format, ($wildcardMask >> 24) & 0xFF),
            \sprintf($format, ($wildcardMask >> 16) & 0xFF),
            \sprintf($format, ($wildcardMask >>  8) & 0xFF),
            \sprintf($format, ($wildcardMask >>  0) & 0xFF),
        ];

        return implode($separator, $maskQuads);
    }

    /**
     * Calculate network portion for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted subnet mask
     */
    private function networkCalculation(string $format, string $separator = ''): string
    {
        $networkQuads = [
            \sprintf($format, (int) $this->quads[0] & ($this->subnetMask >> 24)),
            \sprintf($format, (int) $this->quads[1] & ($this->subnetMask >> 16)),
            \sprintf($format, (int) $this->quads[2] & ($this->subnetMask >>  8)),
            \sprintf($format, (int) $this->quads[3] & ($this->subnetMask >>  0)),
        ];

        return implode($separator, $networkQuads);
    }

    /**
     * Calculate host portion for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted subnet mask
     */
    private function hostCalculation(string $format, string $separator = ''): string
    {
        $networkQuads = [
            \sprintf($format, (int) $this->quads[0] & ~($this->subnetMask >> 24)),
            \sprintf($format, (int) $this->quads[1] & ~($this->subnetMask >> 16)),
            \sprintf($format, (int) $this->quads[2] & ~($this->subnetMask >>  8)),
            \sprintf($format, (int) $this->quads[3] & ~($this->subnetMask >>  0)),
        ];

        return implode($separator, $networkQuads);
    }

    /**
     * Calculate min host for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted min host
     */
    private function minHostCalculation(string $format, string $separator = ''): string
    {
        $networkQuads = [
            \sprintf($format, (int) $this->quads[0] & ($this->subnetMask >> 24)),
            \sprintf($format, (int) $this->quads[1] & ($this->subnetMask >> 16)),
            \sprintf($format, (int) $this->quads[2] & ($this->subnetMask >>  8)),
            \sprintf($format, ((int) $this->quads[3] & ($this->subnetMask >> 0)) + 1),
        ];

        return implode($separator, $networkQuads);
    }

    /**
     * Calculate max host for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted max host
     */
    private function maxHostCalculation(string $format, string $separator = ''): string
    {
        $networkQuads      = $this->getNetworkPortionQuads();
        $numberIpAddresses = $this->getNumberIPAddresses();

        $network_range_quads = [
            \sprintf($format, ((int) $networkQuads[0] & ($this->subnetMask >> 24)) + ((($numberIpAddresses - 1) >> 24) & 0xFF)),
            \sprintf($format, ((int) $networkQuads[1] & ($this->subnetMask >> 16)) + ((($numberIpAddresses - 1) >> 16) & 0xFF)),
            \sprintf($format, ((int) $networkQuads[2] & ($this->subnetMask >>  8)) + ((($numberIpAddresses - 1) >>  8) & 0xFF)),
            \sprintf($format, ((int) $networkQuads[3] & ($this->subnetMask >>  0)) + ((($numberIpAddresses - 1) >>  0) & 0xFE)),
        ];

        return implode($separator, $network_range_quads);
    }

    /**
     * Validate IP address and network
     *
     * @param string $ipAddress   IP address in dotted quads format
     * @param int    $networkSize Network size
     *
     * @throws \UnexpectedValueException IP or network size not valid
     */
    private function validateInputs(string $ipAddress, int $networkSize): void
    {
        if (!\filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new \UnexpectedValueException("IP address $ipAddress not valid.");
        }
        if (($networkSize < 1) || ($networkSize > 32)) {
            throw new \UnexpectedValueException("Network size $networkSize not valid.");
        }
    }

    /**
     * Get the start and end of the IP address range as ints
     *
     * @return int[] [start IP, end IP]
     */
    private function getIPAddressRangeAsInts(): array
    {
        [$startIp, $endIp] = $this->getIPAddressRange();
        $startIp = $this->convertIpToInt($startIp);
        $endIp   = $this->convertIpToInt($endIp);

        return [$startIp, $endIp];
    }

    /**
     * Get the number of IP addresses in the given network size
     *
     * @param int $networkSize
     *
     * @return int Number of IP addresses
     */
    private function getNumberIPAddressesOfNetworkSize($networkSize): int
    {
        return \pow(2, (32 - $networkSize));
    }


    /**
     * Convert a dotted-quad IP address to an integer
     *
     * @param string $ipAddress Dotted-quad IP address
     *
     * @return int Integer representation of an IP address
     */
    private function convertIpToInt(string $ipAddress): int
    {
        $ipAsInt = \ip2long($ipAddress);
        if ($ipAsInt === false) {
            throw new \RuntimeException('Invalid IP address string. Could not convert dotted-quad string address to an integer: ' . $ipAddress);
        }
        return $ipAsInt;
    }

    /**
     * Convert an integer IP address to a dotted-quad IP string
     *
     * @param int $ipAsInt Integer representation of an IP address
     *
     * @return string Dotted-quad IP address
     */
    private function convertIpToDottedQuad(int $ipAsInt): string
    {
        $ipDottedQuad = \long2ip($ipAsInt);
        if ($ipDottedQuad == false) {
            throw new \RuntimeException('Invalid IP address integer. Could not convert integer address to dotted-quad string: ' . $ipAsInt);
        }
        return $ipDottedQuad;
    }
}
