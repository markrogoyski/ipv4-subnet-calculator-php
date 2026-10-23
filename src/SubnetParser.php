<?php

declare(strict_types=1);

namespace IPv4;

use IPv4\Internal\Assert;
use IPv4\Internal\IPv4;

/**
 * Parser for creating Subnet instances from various input formats.
 *
 * Provides methods for complex subnet creation scenarios:
 *  - From IP address and subnet mask string
 *  - From IP address range
 *  - From host count requirements
 *
 * For simple creation, use Subnet::fromCidr() or the Subnet constructor directly.
 */
final class SubnetParser
{
    /**
     * Create a Subnet from an IP address and subnet mask.
     *
     * @param string|IPAddress  $ipAddress  IP address in dotted quad notation, or an IPAddress
     * @param string|SubnetMask $subnetMask Subnet mask in dotted quad notation (e.g., "255.255.255.0"), or a SubnetMask
     *
     * @return Subnet
     *
     * @throws \InvalidArgumentException If subnet mask is invalid or non-contiguous
     */
    public static function fromMask(string|IPAddress $ipAddress, string|SubnetMask $subnetMask): Subnet
    {
        $networkSize = $subnetMask instanceof SubnetMask
            ? $subnetMask->prefix()
            : self::maskToNetworkSize($subnetMask);

        return new Subnet($ipAddress, $networkSize);
    }

    /**
     * Create a Subnet from an IP address range.
     *
     * The range must represent a valid CIDR block. The start IP must be the
     * network address and the end IP must be the broadcast address.
     *
     * @param string|IPAddress $startIp Start IP address (network address)
     * @param string|IPAddress $endIp   End IP address (broadcast address)
     *
     * @return Subnet
     *
     * @throws \InvalidArgumentException If range does not represent a valid CIDR block
     */
    public static function fromRange(string|IPAddress $startIp, string|IPAddress $endIp): Subnet
    {
        $start = $startIp instanceof IPAddress ? $startIp : new IPAddress($startIp);
        $end = $endIp instanceof IPAddress ? $endIp : new IPAddress($endIp);
        $startLong = $start->asInteger();
        $endLong = $end->asInteger();
        $startIp = $start->asQuads();
        $endIp = $end->asQuads();

        if ($startLong > $endLong) {
            throw new \InvalidArgumentException(
                "Start IP '{$startIp}' is greater than end IP '{$endIp}'"
            );
        }

        $rangeSize = $endLong - $startLong + 1;

        // Check if range size is a power of 2
        if (($rangeSize & ($rangeSize - 1)) !== 0) {
            throw new \InvalidArgumentException(
                "Range does not represent a valid CIDR block (size {$rangeSize} is not a power of 2)"
            );
        }

        $networkSize = 32 - (int) \log($rangeSize, 2);

        // Validate alignment
        $mask = (new SubnetMask($networkSize))->asInteger();
        if (($startLong & $mask) !== $startLong) {
            throw new \InvalidArgumentException(
                "Start IP '{$startIp}' is not a valid network address for a /{$networkSize} subnet"
            );
        }

        return new Subnet($start, $networkSize);
    }

    /**
     * Create a Subnet from an IP address and required host count.
     *
     * Returns the smallest subnet that can accommodate the specified number of hosts.
     *
     * @param string|IPAddress $ipAddress Base IP address
     * @param int              $hostCount Number of hosts required
     *
     * @return Subnet
     *
     * @throws \InvalidArgumentException If host count is invalid
     */
    public static function fromHostCount(string|IPAddress $ipAddress, int $hostCount): Subnet
    {
        return new Subnet($ipAddress, self::optimalPrefixForHosts($hostCount));
    }

    /**
     * Calculate the optimal CIDR prefix for a given host count.
     *
     * @param int $hostCount Number of hosts required
     *
     * @return int Optimal CIDR prefix
     *
     * @throws \InvalidArgumentException If host count is invalid
     */
    public static function optimalPrefixForHosts(int $hostCount): int
    {
        if ($hostCount <= 0) {
            throw new \InvalidArgumentException(
                "Host count must be positive, got {$hostCount}"
            );
        }

        if ($hostCount > IPv4::MAX_HOSTS) {
            throw new \InvalidArgumentException(
                "Host count {$hostCount} exceeds maximum possible hosts in IPv4"
            );
        }

        return self::calculateOptimalNetworkSize($hostCount);
    }

    /**
     * Convert a subnet mask to a network size.
     *
     * @param string $subnetMask
     *
     * @return int
     */
    private static function maskToNetworkSize(string $subnetMask): int
    {
        $quads = \explode('.', $subnetMask);
        if (\count($quads) !== 4) {
            throw new \InvalidArgumentException(
                "Invalid subnet mask format: '{$subnetMask}'"
            );
        }

        $maskInt = 0;
        foreach ($quads as $quad) {
            if (!Assert::isDecimal($quad)) {
                throw new \InvalidArgumentException(
                    "Invalid subnet mask: octet is not a decimal integer in '{$subnetMask}'"
                );
            }
            $octet = (int) $quad;
            if ($octet > 255) {
                throw new \InvalidArgumentException(
                    "Invalid subnet mask: octet out of range in '{$subnetMask}'"
                );
            }
            $maskInt = ($maskInt << 8) | $octet;
        }

        if ($maskInt === 0) {
            return 0;
        }

        // Validate contiguous mask
        $inverted = ~$maskInt & 0xFF_FF_FF_FF;
        if (($inverted & ($inverted + 1)) !== 0) {
            throw new \InvalidArgumentException(
                "Invalid subnet mask: non-contiguous mask '{$subnetMask}'"
            );
        }

        // Count 1 bits
        $networkSize = 0;
        $tempMask = $maskInt;
        while ($tempMask & 0x80000000) {
            $networkSize++;
            $tempMask <<= 1;
            $tempMask &= 0xFF_FF_FF_FF;
        }

        return $networkSize;
    }

    /**
     * Calculate optimal network size for host count.
     *
     * @param int $hostCount
     *
     * @return int
     */
    private static function calculateOptimalNetworkSize(int $hostCount): int
    {
        if ($hostCount === 1) {
            return 32;
        }

        if ($hostCount === 2) {
            return 31;
        }

        $totalAddressesNeeded = $hostCount + 2;
        $bitsNeeded = (int) \ceil(\log($totalAddressesNeeded, 2));

        return 32 - $bitsNeeded;
    }
}
