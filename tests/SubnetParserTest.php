<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\Subnet;
use IPv4\SubnetParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SubnetParser
 *
 * @link https://datatracker.ietf.org/doc/html/rfc3021 RFC 3021 - Using 31-Bit Prefixes on IPv4 Point-to-Point Links
 * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - Classless Inter-domain Routing (CIDR)
 */
class SubnetParserTest extends TestCase
{
    /* *********************** *
     * fromMask() - Valid Cases
     * *********************** */

    /**
     * @param string $ipAddress
     * @param string $subnetMask
     * @param int    $expectedSize
     */
    #[Test]
    #[DataProvider('dataProviderForFromMaskValid')]
    public function testFromMaskCreatesValidSubnetCalculator(
        string $ipAddress,
        string $subnetMask,
        int $expectedSize
    ): void {
        // When
        $subnet = SubnetParser::fromMask($ipAddress, $subnetMask);

        // Then
        $this->assertInstanceOf(Subnet::class, $subnet);
        $this->assertSame($expectedSize, $subnet->networkSize());
        $this->assertSame($subnetMask, $subnet->mask()->asQuads());
    }

    /**
     * @return array[] [ipAddress, subnetMask, expectedSize]
     */
    public static function dataProviderForFromMaskValid(): array
    {
        return [
            'Standard /24'    => ['192.168.1.0', '255.255.255.0', 24],
            'Standard /8'     => ['10.0.0.0', '255.0.0.0', 8],
            '/12 mask'        => ['172.16.0.0', '255.240.0.0', 12],
            '/30 mask'        => ['192.168.1.0', '255.255.255.252', 30],
            '/31 RFC 3021'    => ['10.0.0.0', '255.255.255.254', 31],
            '/32 single host' => ['10.0.0.1', '255.255.255.255', 32],
            '/16 mask'        => ['172.16.0.0', '255.255.0.0', 16],
            '/25 mask'        => ['192.168.1.0', '255.255.255.128', 25],
            '/26 mask'        => ['192.168.1.0', '255.255.255.192', 26],
            '/27 mask'        => ['192.168.1.0', '255.255.255.224', 27],
            '/28 mask'        => ['192.168.1.0', '255.255.255.240', 28],
            '/29 mask'        => ['192.168.1.0', '255.255.255.248', 29],
        ];
    }

    /* ************************ *
     * fromMask() - Error Cases
     * ************************ */

    /**
     * @param string $ipAddress
     * @param string $subnetMask
     */
    #[Test]
    #[DataProvider('dataProviderForFromMaskInvalid')]
    public function testFromMaskThrowsExceptionForInvalidMask(
        string $ipAddress,
        string $subnetMask
    ): void {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetParser::fromMask($ipAddress, $subnetMask);
    }

    /**
     * @return array[] [ipAddress, subnetMask]
     */
    public static function dataProviderForFromMaskInvalid(): array
    {
        return [
            'Non-contiguous mask 1'    => ['192.168.1.0', '255.255.255.1'],
            'Non-contiguous mask 2'    => ['192.168.1.0', '255.0.255.0'],
            'Non-contiguous mask 3'    => ['192.168.1.0', '255.255.0.255'],
            'Invalid mask octet > 255' => ['192.168.1.0', '256.255.255.0'],
            'Invalid mask format'      => ['192.168.1.0', '255.255.255'],
            'Non-numeric mask'         => ['192.168.1.0', 'invalid'],
            'Non-numeric octet alpha'  => ['192.168.1.0', '255.abc.255.0'],
            'Numeric exponent octet'   => ['192.168.1.0', '0e0.0.0.0'],
            'Plus-signed octet'        => ['192.168.1.0', '+255.255.255.0'],
            'Whitespace octet'         => ['192.168.1.0', ' 255.255.255.0'],
        ];
    }

    #[Test]
    public function testFromMaskThrowsExceptionForInvalidIpAddress(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetParser::fromMask('invalid', '255.255.255.0');
    }

    /* ************************ *
     * fromRange() - Valid Cases
     * ************************ */

    /**
     * @param string $startIp
     * @param string $endIp
     * @param int    $expectedSize
     * @param string $expectedNetwork
     */
    #[Test]
    #[DataProvider('dataProviderForFromRangeValid')]
    public function testFromRangeCreatesValidSubnetCalculator(
        string $startIp,
        string $endIp,
        int $expectedSize,
        string $expectedNetwork
    ): void {
        // When
        $subnet = SubnetParser::fromRange($startIp, $endIp);

        // Then
        $this->assertInstanceOf(Subnet::class, $subnet);
        $this->assertSame($expectedSize, $subnet->networkSize());
        $this->assertSame($expectedNetwork, $subnet->networkPortion()->asQuads());
        $this->assertSame($startIp, $subnet->networkPortion()->asQuads());
        $this->assertSame($endIp, $subnet->broadcastAddress()->asQuads());
    }

    /**
     * @return array[] [startIp, endIp, expectedSize, expectedNetwork]
     */
    public static function dataProviderForFromRangeValid(): array
    {
        return [
            'Full /24'          => ['192.168.1.0', '192.168.1.255', 24, '192.168.1.0'],
            'Full /8'           => ['10.0.0.0', '10.255.255.255', 8, '10.0.0.0'],
            '/30 subnet'        => ['192.168.1.0', '192.168.1.3', 30, '192.168.1.0'],
            '/31 RFC 3021'      => ['10.0.0.0', '10.0.0.1', 31, '10.0.0.0'],
            'Single host /32'   => ['192.168.1.1', '192.168.1.1', 32, '192.168.1.1'],
            '/25 subnet first'  => ['192.168.1.0', '192.168.1.127', 25, '192.168.1.0'],
            '/25 subnet second' => ['192.168.1.128', '192.168.1.255', 25, '192.168.1.128'],
            '/23 subnet'        => ['192.168.0.0', '192.168.1.255', 23, '192.168.0.0'],
            '/16 subnet'        => ['172.16.0.0', '172.16.255.255', 16, '172.16.0.0'],
        ];
    }

    /**
     * Regression test for floating-point precision in CIDR prefix calculation.
     *
     * The fromRange() method uses log($rangeSize, 2) to calculate the CIDR prefix.
     * This test verifies that floating-point precision does not cause incorrect
     * results for any valid IPv4 subnet size.
     *
     * @link https://en.wikipedia.org/wiki/IEEE_754 IEEE 754 floating-point
     *
     * @param int $prefix
     */
    #[Test]
    #[DataProvider('dataProviderForFromRangeAllPrefixes')]
    public function testFromRangeCalculatesCorrectPrefixForAllValidSizes(int $prefix): void
    {
        // Given a subnet with a known prefix
        $subnet = new Subnet('10.0.0.0', $prefix);
        $startIp = (string) $subnet->networkAddress();
        $endIp = (string) $subnet->broadcastAddress();

        // When we create a subnet from its range
        $fromRange = SubnetParser::fromRange($startIp, $endIp);

        // Then the calculated prefix matches the original
        $this->assertSame($prefix, $fromRange->networkSize());
    }

    /**
     * @return array[] [prefix]
     */
    public static function dataProviderForFromRangeAllPrefixes(): array
    {
        $cases = [];
        for ($prefix = 0; $prefix <= 32; $prefix++) {
            $cases["/{$prefix}"] = [$prefix];
        }
        return $cases;
    }

    /* ************************* *
     * fromRange() - Error Cases
     * ************************* */

    /**
     * @param string $startIp
     * @param string $endIp
     */
    #[Test]
    #[DataProvider('dataProviderForFromRangeInvalid')]
    public function testFromRangeThrowsExceptionForInvalidRange(
        string $startIp,
        string $endIp
    ): void {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetParser::fromRange($startIp, $endIp);
    }

    /**
     * @return array[] [startIp, endIp]
     */
    public static function dataProviderForFromRangeInvalid(): array
    {
        return [
            'Not valid CIDR block'     => ['192.168.1.0', '192.168.1.100'],
            'Start > End'              => ['192.168.1.100', '192.168.1.0'],
            'Crosses CIDR boundary'    => ['192.168.1.0', '192.168.2.0'],
            'Off-by-one range'         => ['192.168.1.0', '192.168.1.254'],
            'Partial /24'              => ['192.168.1.1', '192.168.1.255'],
            'Not aligned start'        => ['192.168.1.1', '192.168.1.2'],
        ];
    }

    #[Test]
    public function testFromRangeThrowsExceptionForInvalidStartIp(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetParser::fromRange('invalid', '192.168.1.255');
    }

    #[Test]
    public function testFromRangeThrowsExceptionForInvalidEndIp(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetParser::fromRange('192.168.1.0', 'invalid');
    }

    #[Test]
    public function testFromRangeAcceptsEntireIpv4Space(): void
    {
        // Given - entire IPv4 space (0.0.0.0 to 255.255.255.255)
        $startIp = '0.0.0.0';
        $endIp = '255.255.255.255';

        // When
        $subnet = SubnetParser::fromRange($startIp, $endIp);

        // Then
        $this->assertSame(0, $subnet->networkSize());
        $this->assertSame('0.0.0.0/0', $subnet->cidr());
    }

    #[Test]
    public function testFromMaskWithZeroMask(): void
    {
        // Given - use 0.0.0.0 as IP since cidr() preserves input IP
        $ip = '0.0.0.0';
        $mask = '0.0.0.0';

        // When
        $subnet = SubnetParser::fromMask($ip, $mask);

        // Then
        $this->assertSame(0, $subnet->networkSize());
        $this->assertSame('0.0.0.0/0', $subnet->cidr());
    }

    #[Test]
    public function testFromMaskWithZeroMaskPreservesInputIp(): void
    {
        // Given - cidr() preserves the input IP, not the network address
        $ip = '10.0.0.0';
        $mask = '0.0.0.0';

        // When
        $subnet = SubnetParser::fromMask($ip, $mask);

        // Then
        $this->assertSame(0, $subnet->networkSize());
        $this->assertSame('10.0.0.0/0', $subnet->cidr());
        // Network address is still 0.0.0.0
        $this->assertSame('0.0.0.0', $subnet->networkAddress()->asQuads());
    }

    /* *************************** *
     * fromHostCount() - Valid Cases
     * *************************** */

    /**
     * @param string $ipAddress
     * @param int    $hostCount
     * @param int    $expectedSize
     * @param int    $expectedActualHosts
     */
    #[Test]
    #[DataProvider('dataProviderForFromHostCountValid')]
    public function testFromHostCountCreatesValidSubnetCalculator(
        string $ipAddress,
        int $hostCount,
        int $expectedSize,
        int $expectedActualHosts
    ): void {
        // When
        $subnet = SubnetParser::fromHostCount($ipAddress, $hostCount);

        // Then
        $this->assertInstanceOf(Subnet::class, $subnet);
        $this->assertSame($expectedSize, $subnet->networkSize());
        $this->assertSame($expectedActualHosts, $subnet->hostCount());
        $this->assertGreaterThanOrEqual($hostCount, $subnet->hostCount());
    }

    /**
     * @return array[] [ipAddress, hostCount, expectedSize, expectedActualHosts]
     */
    public static function dataProviderForFromHostCountValid(): array
    {
        return [
            'Full /0 hosts'         => ['0.0.0.0', 4_294_967_294, 0, 4_294_967_294],
            'Single host /32'       => ['192.168.1.0', 1, 32, 1],
            'RFC 3021 /31'          => ['192.168.1.0', 2, 31, 2],
            'Need /29 for 3 hosts'  => ['192.168.1.0', 3, 29, 6],
            'Need /29 for 6 hosts'  => ['192.168.1.0', 6, 29, 6],
            'Need /28 for 7 hosts'  => ['192.168.1.0', 7, 28, 14],
            'Common office 50'      => ['192.168.1.0', 50, 26, 62],
            'Needs 100 hosts'       => ['192.168.1.0', 100, 25, 126],
            'Full /24 = 254 hosts'  => ['192.168.1.0', 254, 24, 254],
            'Need /23 for 255'      => ['192.168.1.0', 255, 23, 510],
            'Full /16 hosts'        => ['10.0.0.0', 65534, 16, 65534],
            'Need /15 for 65535'    => ['10.0.0.0', 65535, 15, 131070],
        ];
    }

    /* ***************************** *
     * fromHostCount() - Error Cases
     * ***************************** */

    #[Test]
    public function testFromHostCountThrowsExceptionForZeroHosts(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetParser::fromHostCount('192.168.1.0', 0);
    }

    #[Test]
    public function testFromHostCountThrowsExceptionForNegativeHosts(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetParser::fromHostCount('192.168.1.0', -1);
    }

    /**
     * More hosts than IPv4 can accommodate (> 2^32 - 2)
     */
    #[Test]
    public function testFromHostCountThrowsExceptionForTooManyHosts(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When - 4294967295 exceeds maximum of 4294967294 (usable hosts in /0)
        SubnetParser::fromHostCount('192.168.1.0', 4_294_967_295);
    }

    #[Test]
    public function testFromHostCountThrowsExceptionForInvalidIp(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetParser::fromHostCount('invalid', 10);
    }

    /* ********************************** *
     * optimalPrefixForHosts() - Valid Cases
     * ********************************** */

    /**
     * @param int $hostCount
     * @param int $expectedPrefix
     * @param int $expectedActualHosts
     */
    #[Test]
    #[DataProvider('dataProviderForOptimalPrefixForHostsValid')]
    public function testOptimalPrefixForHostsReturnsCorrectPrefix(
        int $hostCount,
        int $expectedPrefix,
        int $expectedActualHosts
    ): void {
        // When
        $prefix = SubnetParser::optimalPrefixForHosts($hostCount);

        // Then
        $this->assertSame($expectedPrefix, $prefix);

        // Verify the prefix provides at least the requested hosts
        $subnet = new Subnet('10.0.0.0', $prefix);
        $this->assertGreaterThanOrEqual($hostCount, $subnet->hostCount());
        $this->assertSame($expectedActualHosts, $subnet->hostCount());
    }

    /**
     * @return array[] [hostCount, expectedPrefix, expectedActualHosts]
     */
    public static function dataProviderForOptimalPrefixForHostsValid(): array
    {
        return [
            // Basic cases
            'Full /0 hosts'          => [4_294_967_294, 0, 4_294_967_294],
            'Single host /32'        => [1, 32, 1],
            'RFC 3021 /31 for 2'     => [2, 31, 2],
            'Need /29 for 3 hosts'   => [3, 29, 6],
            'Exact fit /29 for 6'    => [6, 29, 6],
            'Need /28 for 7 hosts'   => [7, 28, 14],
            'Exact fit /28 for 14'   => [14, 28, 14],
            'Common office 50'       => [50, 26, 62],
            'Need /25 for 100'       => [100, 25, 126],
            'Full /24 = 254 hosts'   => [254, 24, 254],
            'Need /23 for 255'       => [255, 23, 510],
            'Exact fit /23 for 510'  => [510, 23, 510],
            'Full /16 hosts'         => [65534, 16, 65534],

            // +1 boundary cases (ceiling logic validation)
            'Need /27 for 15 hosts'  => [15, 27, 30],
            'Need /26 for 31 hosts'  => [31, 26, 62],
            'Need /25 for 63 hosts'  => [63, 25, 126],
            'Need /24 for 127 hosts' => [127, 24, 254],
            'Need /22 for 511 hosts' => [511, 22, 1022],

            // Large power-of-2 boundaries
            'Exact fit /22'          => [1022, 22, 1022],
            'Need /21 for 1023'      => [1023, 21, 2046],
            'Exact fit /17'          => [32766, 17, 32766],

            // Mid-range values
            'Mid-range 500'          => [500, 23, 510],
            'Mid-range 1000'         => [1000, 22, 1022],
            'Mid-range 10000'        => [10000, 18, 16382],

            // Maximum valid boundaries
            'Just under maximum'     => [2147483645, 1, 2147483646],
            'Maximum /1 network'     => [2147483646, 1, 2147483646],
        ];
    }

    /* ************************************ *
     * optimalPrefixForHosts() - Error Cases
     * ************************************ */

    #[Test]
    public function testOptimalPrefixForHostsThrowsExceptionForZeroHosts(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Host count must be positive, got 0');

        // When
        SubnetParser::optimalPrefixForHosts(0);
    }

    #[Test]
    public function testOptimalPrefixForHostsThrowsExceptionForNegativeHosts(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Host count must be positive, got -1');

        // When
        SubnetParser::optimalPrefixForHosts(-1);
    }

    #[Test]
    public function testOptimalPrefixForHostsThrowsExceptionForOneOverMaximum(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When - 4294967295 is one over the maximum (4294967294)
        SubnetParser::optimalPrefixForHosts(4_294_967_295);
    }

    #[Test]
    public function testOptimalPrefixForHostsThrowsExceptionForTooManyHosts(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetParser::optimalPrefixForHosts(4294967295);
    }

    /* ****************** *
     * Integration Tests
     * ****************** */

    /**
     * Verify that fromMask produces same network size as expected
     */
    #[Test]
    public function testFromMaskNetworkCalculation(): void
    {
        // Given
        $ip = '10.1.2.3';
        $mask = '255.255.0.0';

        // When
        $subnet = SubnetParser::fromMask($ip, $mask);

        // Then
        $this->assertSame(16, $subnet->networkSize());
        $this->assertSame('10.1.0.0', $subnet->networkPortion()->asQuads());
    }

    /**
     * Verify fromRange and fromCidr produce equivalent results for same network
     */
    #[Test]
    public function testFromRangeMatchesFromCidr(): void
    {
        // Given
        $cidr = '192.168.1.0/24';
        $startIp = '192.168.1.0';
        $endIp = '192.168.1.255';

        // When
        $fromCidr = Subnet::fromCidr($cidr);
        $fromRange = SubnetParser::fromRange($startIp, $endIp);

        // Then
        $this->assertSame($fromCidr->networkPortion()->asQuads(), $fromRange->networkPortion()->asQuads());
        $this->assertSame($fromCidr->networkSize(), $fromRange->networkSize());
        $this->assertSame($fromCidr->broadcastAddress()->asQuads(), $fromRange->broadcastAddress()->asQuads());
    }
}
