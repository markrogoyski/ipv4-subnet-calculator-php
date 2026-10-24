<?php

declare(strict_types=1);

namespace IPv4;

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
            : SubnetMask::fromString($subnetMask)->prefix();

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

        if ($startLong > $endLong) {
            throw new \InvalidArgumentException(
                "Start IP '{$start->asQuads()}' is greater than end IP '{$end->asQuads()}'"
            );
        }

        $rangeSize = $endLong - $startLong + 1;

        // Check if range size is a power of 2
        if (($rangeSize & ($rangeSize - 1)) !== 0) {
            throw new \InvalidArgumentException(
                "Range does not represent a valid CIDR block (size {$rangeSize} is not a power of 2)"
            );
        }

        $networkSize = 33 - \strlen(\decbin($rangeSize));

        // Validate alignment: no bits of the start address may be set inside the block
        if (($startLong & ($rangeSize - 1)) !== 0) {
            throw new \InvalidArgumentException(
                "Start IP '{$start->asQuads()}' is not a valid network address for a /{$networkSize} subnet"
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

        if ($hostCount === 1) {
            return 32;
        }

        if ($hostCount === 2) {
            return 31;
        }

        // Host bits needed for hostCount + 2 addresses: ceil(log2(hostCount + 2)) == bit length of (hostCount + 1)
        return 32 - \strlen(\decbin($hostCount + 1));
    }
}
