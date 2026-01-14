<?php

declare(strict_types=1);

namespace IPv4;

/**
 * Factory class for creating SubnetCalculator instances from various input formats.
 *
 * Provides flexible ways to instantiate SubnetCalculator objects, separating
 * construction concerns from the main class and improving developer experience.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc3021 RFC 3021 - Using 31-Bit Prefixes on IPv4 Point-to-Point Links
 * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - Classless Inter-domain Routing (CIDR)
 */
class SubnetCalculatorFactory
{
    /**
     * Create SubnetCalculator from CIDR notation string (e.g., "192.168.1.0/24")
     *
     * @param string $cidr CIDR notation (IP address with prefix, e.g., "192.168.1.0/24")
     *
     * @return SubnetCalculator
     *
     * @throws \InvalidArgumentException If CIDR format is invalid (missing slash, empty prefix)
     * @throws \UnexpectedValueException If IP address or network size is invalid
     */
    public static function fromCidr(string $cidr): SubnetCalculator
    {
        // Validate CIDR format
        if (\strpos($cidr, '/') === false) {
            throw new \InvalidArgumentException("Invalid CIDR notation: missing '/' prefix delimiter in '{$cidr}'");
        }

        $parts = \explode('/', $cidr);

        if (\count($parts) !== 2) {
            throw new \InvalidArgumentException("Invalid CIDR notation: multiple '/' found in '{$cidr}'");
        }

        [$ipAddress, $prefix] = $parts;

        if ($prefix === '') {
            throw new \InvalidArgumentException("Invalid CIDR notation: empty prefix in '{$cidr}'");
        }

        if (!\is_numeric($prefix)) {
            throw new \InvalidArgumentException("Invalid CIDR notation: non-numeric prefix '{$prefix}' in '{$cidr}'");
        }

        $networkSize = (int) $prefix;

        // Let SubnetCalculator validate IP address and network size
        return new SubnetCalculator($ipAddress, $networkSize);
    }

    /**
     * Create SubnetCalculator from IP address and subnet mask (e.g., "192.168.1.0", "255.255.255.0")
     *
     * @param string $ipAddress  IP address in dotted quad notation
     * @param string $subnetMask Subnet mask in dotted quad notation (e.g., "255.255.255.0")
     *
     * @return SubnetCalculator
     *
     * @throws \InvalidArgumentException If subnet mask is invalid or non-contiguous
     * @throws \UnexpectedValueException If IP address is invalid
     */
    public static function fromMask(string $ipAddress, string $subnetMask): SubnetCalculator
    {
        $networkSize = self::maskToNetworkSize($subnetMask);

        return new SubnetCalculator($ipAddress, $networkSize);
    }

    /**
     * Create SubnetCalculator from IP address range (e.g., "192.168.1.0", "192.168.1.255")
     *
     * Note: The range must represent a valid CIDR block. The start IP must be the
     * network address and the end IP must be the broadcast address of a valid subnet.
     *
     * @param string $startIp Start IP address (network address)
     * @param string $endIp   End IP address (broadcast address)
     *
     * @return SubnetCalculator
     *
     * @throws \InvalidArgumentException If range does not represent a valid CIDR block
     * @throws \UnexpectedValueException If IP addresses are invalid
     */
    public static function fromRange(string $startIp, string $endIp): SubnetCalculator
    {
        // Validate IP addresses
        $startLong = self::validateAndConvertIp($startIp);
        $endLong = self::validateAndConvertIp($endIp);

        // Start must be <= End
        if ($startLong > $endLong) {
            throw new \InvalidArgumentException("Start IP '{$startIp}' is greater than end IP '{$endIp}'");
        }

        // Calculate the number of addresses in the range
        $rangeSize = $endLong - $startLong + 1;

        // Check if range size is a power of 2
        if (($rangeSize & ($rangeSize - 1)) !== 0) {
            throw new \InvalidArgumentException(
                "Range from '{$startIp}' to '{$endIp}' does not represent a valid CIDR block (size {$rangeSize} is not a power of 2)"
            );
        }

        // Calculate network size from range size
        $networkSize = 32 - (int) \log($rangeSize, 2);

        // Validate that start IP is properly aligned for this network size
        // A properly aligned network address has all host bits set to 0
        $mask = self::calculateSubnetMaskInt($networkSize);
        if (($startLong & $mask) !== $startLong) {
            throw new \InvalidArgumentException(
                "Start IP '{$startIp}' is not a valid network address for a /{$networkSize} subnet"
            );
        }

        return new SubnetCalculator($startIp, $networkSize);
    }

    /**
     * Create SubnetCalculator from IP address and number of required hosts.
     *
     * Returns the smallest subnet that can accommodate the host count.
     *
     * @param string $ipAddress Base IP address
     * @param int    $hostCount Number of hosts required
     *
     * @return SubnetCalculator
     *
     * @throws \InvalidArgumentException If host count is invalid (zero, negative, or too large)
     * @throws \UnexpectedValueException If IP address is invalid
     */
    public static function fromHostCount(string $ipAddress, int $hostCount): SubnetCalculator
    {
        // Validate host count
        if ($hostCount <= 0) {
            throw new \InvalidArgumentException("Host count must be positive, got {$hostCount}");
        }

        // Maximum possible hosts in IPv4 (for /1 network)
        // 2^31 - 2 = 2147483646
        if ($hostCount > 2147483646) {
            throw new \InvalidArgumentException("Host count {$hostCount} exceeds maximum possible hosts in IPv4");
        }

        // Calculate optimal network size
        $networkSize = self::calculateOptimalNetworkSize($hostCount);

        return new SubnetCalculator($ipAddress, $networkSize);
    }

    /**
     * Calculate the optimal CIDR prefix for a given host count.
     *
     * Returns the smallest prefix (largest network) that can accommodate
     * the specified number of hosts.
     *
     * For standard networks (/1 to /30), usable hosts = 2^(32-prefix) - 2
     * For /31 (RFC 3021), usable hosts = 2
     * For /32, usable hosts = 1
     *
     * @param int $hostCount Number of hosts required
     *
     * @return int Optimal CIDR prefix (network size)
     *
     * @throws \InvalidArgumentException If host count is invalid (zero, negative, or too large)
     *
     * @link https://datatracker.ietf.org/doc/html/rfc3021 RFC 3021 - Using 31-Bit Prefixes on IPv4 Point-to-Point Links
     */
    public static function optimalPrefixForHosts(int $hostCount): int
    {
        // Validate host count
        if ($hostCount <= 0) {
            throw new \InvalidArgumentException("Host count must be positive, got {$hostCount}");
        }

        // Maximum possible hosts in IPv4 (for /1 network)
        // 2^31 - 2 = 2147483646
        if ($hostCount > 2147483646) {
            throw new \InvalidArgumentException("Host count {$hostCount} exceeds maximum possible hosts in IPv4");
        }

        return self::calculateOptimalNetworkSize($hostCount);
    }

    /**
     * Convert a subnet mask to a network size (CIDR prefix)
     *
     * @param string $subnetMask Subnet mask in dotted quad notation
     *
     * @return int Network size (CIDR prefix)
     *
     * @throws \InvalidArgumentException If mask is invalid or non-contiguous
     */
    private static function maskToNetworkSize(string $subnetMask): int
    {
        // Validate format
        $quads = \explode('.', $subnetMask);
        if (\count($quads) !== 4) {
            throw new \InvalidArgumentException("Invalid subnet mask format: '{$subnetMask}'");
        }

        // Validate each octet and convert to integer
        $maskInt = 0;
        foreach ($quads as $i => $quad) {
            if (!\is_numeric($quad)) {
                throw new \InvalidArgumentException("Invalid subnet mask: non-numeric octet in '{$subnetMask}'");
            }
            $octet = (int) $quad;
            if ($octet < 0 || $octet > 255) {
                throw new \InvalidArgumentException("Invalid subnet mask: octet out of range in '{$subnetMask}'");
            }
            $maskInt = ($maskInt << 8) | $octet;
        }

        // Check for zero mask (invalid - would be /0)
        if ($maskInt === 0) {
            throw new \InvalidArgumentException("Invalid subnet mask: zero mask not supported");
        }

        // Validate that mask is contiguous (all 1s followed by all 0s)
        // A valid mask in binary looks like: 11111111111111111111111100000000
        // If we invert it and add 1, we should get a power of 2
        $inverted = ~$maskInt & 0xFFFFFFFF;
        if (($inverted & ($inverted + 1)) !== 0) {
            throw new \InvalidArgumentException("Invalid subnet mask: non-contiguous mask '{$subnetMask}'");
        }

        // Count the number of 1 bits (network size)
        $networkSize = 0;
        $tempMask = $maskInt;
        while ($tempMask & 0x80000000) {
            $networkSize++;
            $tempMask <<= 1;
            $tempMask &= 0xFFFFFFFF; // Keep it as 32-bit
        }

        return $networkSize;
    }

    /**
     * Validate an IP address and convert to long integer
     *
     * @param string $ipAddress IP address to validate
     *
     * @return int IP address as integer
     *
     * @throws \UnexpectedValueException If IP address is invalid
     */
    private static function validateAndConvertIp(string $ipAddress): int
    {
        if (!\filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new \UnexpectedValueException("Invalid IP address: '{$ipAddress}'");
        }

        $long = \ip2long($ipAddress);
        if ($long === false) {
            // @codeCoverageIgnore
            throw new \UnexpectedValueException("Invalid IP address: '{$ipAddress}'");
        }

        // Handle negative values on 32-bit systems
        if ($long < 0) {
            // @codeCoverageIgnore
            $long = $long + 4294967296;
        }

        return $long;
    }

    /**
     * Calculate subnet mask as integer from network size
     *
     * @param int $networkSize Network size (1-32)
     *
     * @return int Subnet mask as integer
     */
    private static function calculateSubnetMaskInt(int $networkSize): int
    {
        if ($networkSize === 0) {
            return 0;
        }
        return (0xFFFFFFFF << (32 - $networkSize)) & 0xFFFFFFFF;
    }

    /**
     * Aggregate multiple subnets into the smallest possible supernet(s).
     *
     * Combines contiguous subnets into larger summary routes to reduce routing
     * table size. Returns an array of SubnetCalculator objects representing
     * the minimal set of CIDR blocks that cover all input subnets.
     *
     * If subnets are not fully contiguous, multiple supernets will be returned.
     * Overlapping and duplicate subnets are handled by removing redundant entries.
     *
     * @param SubnetCalculator[] $subnets Array of subnets to aggregate
     *
     * @return SubnetCalculator[] Array of aggregated supernets
     *
     * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - Classless Inter-domain Routing (CIDR)
     */
    public static function aggregate(array $subnets): array
    {
        if (empty($subnets)) {
            return [];
        }

        // Normalize all subnets to their network addresses and collect as [start, end, prefix] tuples
        $ranges = [];
        foreach ($subnets as $subnet) {
            $start = self::ipToUnsigned($subnet->getNetworkPortionInteger());
            $end = $start + $subnet->getNumberIPAddresses() - 1;
            $ranges[] = [$start, $end, $subnet->getNetworkSize()];
        }

        // Sort by start address, then by end address (larger ranges first)
        \usort($ranges, function ($a, $b) {
            if ($a[0] !== $b[0]) {
                return $a[0] <=> $b[0];
            }
            return $b[1] <=> $a[1]; // Larger ranges first when same start
        });

        // Remove subnets contained within others
        $filtered = [];
        foreach ($ranges as $range) {
            $isContained = false;
            foreach ($filtered as $existing) {
                if ($range[0] >= $existing[0] && $range[1] <= $existing[1]) {
                    $isContained = true;
                    break;
                }
            }
            if (!$isContained) {
                // Also remove any existing ranges that this one contains
                $filtered = \array_filter($filtered, function ($existing) use ($range) {
                    return !($existing[0] >= $range[0] && $existing[1] <= $range[1]);
                });
                $filtered[] = $range;
            }
        }

        // Re-sort after filtering
        \usort($filtered, function ($a, $b) {
            return $a[0] <=> $b[0];
        });

        // Convert back to [start, prefix] format for aggregation
        $blocks = [];
        foreach ($filtered as $range) {
            $blocks[] = [$range[0], $range[2]];
        }

        // Iteratively merge adjacent subnets
        $merged = true;
        while ($merged) {
            $merged = false;
            $newBlocks = [];
            $used = [];

            for ($i = 0; $i < \count($blocks); $i++) {
                if (isset($used[$i])) {
                    continue;
                }

                $current = $blocks[$i];
                $foundMerge = false;

                // Try to find a partner to merge with
                for ($j = $i + 1; $j < \count($blocks); $j++) {
                    $other = $blocks[$j];

                    // Check if they can be merged (but not below /1 since /0 is not supported)
                    if ($current[1] === $other[1] && $current[1] > 1) {
                        $prefix = $current[1];
                        $blockSize = 1 << (32 - $prefix);

                        // Check if they are adjacent
                        $currentEnd = $current[0] + $blockSize - 1;
                        if ($currentEnd + 1 === $other[0]) {
                            // Check if the merged block would be aligned
                            $newPrefix = $prefix - 1;
                            $newBlockSize = 1 << (32 - $newPrefix);
                            if (($current[0] % $newBlockSize) === 0) {
                                // Merge them
                                $newBlocks[] = [$current[0], $newPrefix];
                                $used[$i] = true;
                                $used[$j] = true;
                                $merged = true;
                                $foundMerge = true;
                                break;
                            }
                        }
                    }
                }

                if (!$foundMerge) {
                    $newBlocks[] = $current;
                    $used[$i] = true;
                }
            }

            $blocks = $newBlocks;

            // Re-sort blocks by start address
            \usort($blocks, function ($a, $b) {
                return $a[0] <=> $b[0];
            });
        }

        // Convert back to SubnetCalculator objects
        $result = [];
        foreach ($blocks as $block) {
            $ip = self::unsignedToIp($block[0]);
            $result[] = new SubnetCalculator($ip, (int) $block[1]);
        }

        return $result;
    }

    /**
     * Find the smallest single supernet that contains all given subnets.
     *
     * Returns the minimal CIDR block that encompasses all input subnets.
     * Unlike aggregate(), this always returns a single subnet, but may include
     * addresses not in any of the input subnets (gaps are filled).
     *
     * @param SubnetCalculator[] $subnets Array of subnets to summarize
     *
     * @return SubnetCalculator The smallest supernet containing all inputs
     *
     * @throws \InvalidArgumentException If the subnet array is empty
     *
     * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - Classless Inter-domain Routing (CIDR)
     */
    public static function summarize(array $subnets): SubnetCalculator
    {
        if (empty($subnets)) {
            throw new \InvalidArgumentException('Cannot summarize empty subnet array');
        }

        if (\count($subnets) === 1) {
            $subnet = $subnets[0];
            return new SubnetCalculator($subnet->getNetworkPortion(), $subnet->getNetworkSize());
        }

        // Find the minimum start IP and maximum end IP
        $minStart = null;
        $maxEnd = null;

        foreach ($subnets as $subnet) {
            $start = self::ipToUnsigned($subnet->getNetworkPortionInteger());
            $end = $start + $subnet->getNumberIPAddresses() - 1;

            if ($minStart === null || $start < $minStart) {
                $minStart = $start;
            }
            if ($maxEnd === null || $end > $maxEnd) {
                $maxEnd = $end;
            }
        }

        // Find the smallest prefix that covers this range
        // Start from /32 and work backwards until we find one that covers the range
        for ($prefix = 32; $prefix >= 1; $prefix--) {
            $blockSize = 1 << (32 - $prefix);
            // Find the network address for this prefix that contains minStart
            $networkStart = ((int) ($minStart / $blockSize)) * $blockSize;
            $networkEnd = $networkStart + $blockSize - 1;

            if ($networkStart <= $minStart && $networkEnd >= $maxEnd) {
                $ip = self::unsignedToIp((int) $networkStart);
                return new SubnetCalculator($ip, $prefix);
            }
        }

        // If we get here, the subnets span the entire IP space and would require /0
        throw new \InvalidArgumentException(
            'Cannot summarize: result would require /0 which is not a valid network size'
        );
    }

    /**
     * Convert a signed IP integer to unsigned for comparison
     *
     * @param int $ip Signed IP integer
     *
     * @return int Unsigned IP integer
     */
    private static function ipToUnsigned(int $ip): int
    {
        if ($ip < 0) {
            return $ip + 4294967296;
        }
        return $ip;
    }

    /**
     * Convert an unsigned IP integer back to a dotted-quad string
     *
     * @param int $ip Unsigned IP integer
     *
     * @return string Dotted-quad IP address
     */
    private static function unsignedToIp(int $ip): string
    {
        // Convert to signed for long2ip if needed
        if ($ip > 2147483647) {
            $ip = $ip - 4294967296;
        }
        $result = \long2ip($ip);
        if (!$result) {
            throw new \RuntimeException("Failed to convert IP integer to string: {$ip}");
        }
        return $result;
    }

    /**
     * Calculate the optimal network size for a given host count
     *
     * For standard networks (/1 to /30), usable hosts = 2^(32-prefix) - 2
     * For /31 (RFC 3021), usable hosts = 2
     * For /32, usable hosts = 1
     *
     * @param int $hostCount Number of hosts required
     *
     * @return int Optimal network size (CIDR prefix)
     */
    private static function calculateOptimalNetworkSize(int $hostCount): int
    {
        // Special case: 1 host needs /32
        if ($hostCount === 1) {
            return 32;
        }

        // Special case: 2 hosts can use /31 (RFC 3021)
        if ($hostCount === 2) {
            return 31;
        }

        // For 3+ hosts, we need to account for network and broadcast addresses
        // Usable hosts = 2^(32-prefix) - 2
        // So we need: 2^(32-prefix) >= hostCount + 2
        // Therefore: 32 - prefix >= log2(hostCount + 2)
        // prefix <= 32 - ceil(log2(hostCount + 2))

        $totalAddressesNeeded = $hostCount + 2;
        $bitsNeeded = (int) \ceil(\log($totalAddressesNeeded, 2));
        $networkSize = 32 - $bitsNeeded;

        // Ensure network size is valid
        if ($networkSize < 1) {
            $networkSize = 1;
        }

        return $networkSize;
    }
}
