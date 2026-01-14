<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\SubnetCalculator;
use IPv4\SubnetCalculatorFactory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SubnetCalculatorFactory
 *
 * @link https://datatracker.ietf.org/doc/html/rfc3021 RFC 3021 - Using 31-Bit Prefixes on IPv4 Point-to-Point Links
 * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - Classless Inter-domain Routing (CIDR)
 */
class SubnetCalculatorFactoryTest extends TestCase
{
    /* ************************ *
     * fromCidr() - Valid Cases
     * ************************ */

    /**
     * @test
     * @dataProvider dataProviderForFromCidrValid
     * @param string $cidr
     * @param string $expectedNetwork
     * @param int    $expectedSize
     */
    public function testFromCidrCreatesValidSubnetCalculator(
        string $cidr,
        string $expectedNetwork,
        int $expectedSize
    ): void {
        // When
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // Then
        $this->assertInstanceOf(SubnetCalculator::class, $subnet);
        $this->assertSame($expectedNetwork, $subnet->getNetworkPortion());
        $this->assertSame($expectedSize, $subnet->getNetworkSize());
    }

    /**
     * @return array[] [cidr, expectedNetwork, expectedSize]
     */
    public function dataProviderForFromCidrValid(): array
    {
        return [
            'Standard Class C'           => ['192.168.1.0/24', '192.168.1.0', 24],
            'Class A'                    => ['10.0.0.0/8', '10.0.0.0', 8],
            'Class B private'            => ['172.16.0.0/12', '172.16.0.0', 12],
            'IP not on boundary'         => ['192.168.1.100/24', '192.168.1.0', 24],
            'RFC 3021 point-to-point'    => ['10.0.0.0/31', '10.0.0.0', 31],
            'RFC 3021 odd IP'            => ['10.0.0.1/31', '10.0.0.0', 31],
            'Single host /32'            => ['192.168.1.1/32', '192.168.1.1', 32],
            '/30 subnet'                 => ['192.168.1.0/30', '192.168.1.0', 30],
            '/29 subnet'                 => ['192.168.1.0/29', '192.168.1.0', 29],
            '/16 subnet'                 => ['172.16.0.0/16', '172.16.0.0', 16],
            'IP in middle of subnet'     => ['10.1.2.50/24', '10.1.2.0', 24],
        ];
    }

    /* ************************ *
     * fromCidr() - Error Cases
     * ************************ */

    /**
     * @test
     * @dataProvider dataProviderForFromCidrInvalidFormat
     * @param string $cidr
     */
    public function testFromCidrThrowsInvalidArgumentExceptionForInvalidFormat(string $cidr): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetCalculatorFactory::fromCidr($cidr);
    }

    /**
     * @return array[] [cidr]
     */
    public function dataProviderForFromCidrInvalidFormat(): array
    {
        return [
            'Missing /prefix'     => ['192.168.1.0'],
            'Empty prefix'        => ['192.168.1.0/'],
            'Multiple slashes'    => ['192.168.1.0/24/8'],
            'Non-numeric prefix'  => ['192.168.1.0/abc'],
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForFromCidrInvalidValues
     * @param string $cidr
     */
    public function testFromCidrThrowsUnexpectedValueExceptionForInvalidValues(string $cidr): void
    {
        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        SubnetCalculatorFactory::fromCidr($cidr);
    }

    /**
     * @return array[] [cidr]
     */
    public function dataProviderForFromCidrInvalidValues(): array
    {
        return [
            'Invalid prefix > 32'   => ['192.168.1.0/33'],
            'Negative prefix'       => ['192.168.1.0/-1'],
            'Prefix zero'           => ['192.168.1.0/0'],
            'Invalid IP format'     => ['invalid/24'],
            'Octet > 255'           => ['192.168.1.256/24'],
            'Too many octets'       => ['192.168.1.1.1/24'],
            'Too few octets'        => ['192.168.1/24'],
            'Empty IP'              => ['/24'],
        ];
    }

    /* *********************** *
     * fromMask() - Valid Cases
     * *********************** */

    /**
     * @test
     * @dataProvider dataProviderForFromMaskValid
     * @param string $ipAddress
     * @param string $subnetMask
     * @param int    $expectedSize
     */
    public function testFromMaskCreatesValidSubnetCalculator(
        string $ipAddress,
        string $subnetMask,
        int $expectedSize
    ): void {
        // When
        $subnet = SubnetCalculatorFactory::fromMask($ipAddress, $subnetMask);

        // Then
        $this->assertInstanceOf(SubnetCalculator::class, $subnet);
        $this->assertSame($expectedSize, $subnet->getNetworkSize());
        $this->assertSame($subnetMask, $subnet->getSubnetMask());
    }

    /**
     * @return array[] [ipAddress, subnetMask, expectedSize]
     */
    public function dataProviderForFromMaskValid(): array
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
     * @test
     * @dataProvider dataProviderForFromMaskInvalid
     * @param string $ipAddress
     * @param string $subnetMask
     */
    public function testFromMaskThrowsExceptionForInvalidMask(
        string $ipAddress,
        string $subnetMask
    ): void {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetCalculatorFactory::fromMask($ipAddress, $subnetMask);
    }

    /**
     * @return array[] [ipAddress, subnetMask]
     */
    public function dataProviderForFromMaskInvalid(): array
    {
        return [
            'Non-contiguous mask 1'    => ['192.168.1.0', '255.255.255.1'],
            'Non-contiguous mask 2'    => ['192.168.1.0', '255.0.255.0'],
            'Non-contiguous mask 3'    => ['192.168.1.0', '255.255.0.255'],
            'Invalid mask octet > 255' => ['192.168.1.0', '256.255.255.0'],
            'Invalid mask format'      => ['192.168.1.0', '255.255.255'],
            'Non-numeric mask'         => ['192.168.1.0', 'invalid'],
            'Zero mask'                => ['192.168.1.0', '0.0.0.0'],
            'Non-numeric octet alpha'  => ['192.168.1.0', '255.abc.255.0'],
        ];
    }

    /**
     * @test
     */
    public function testFromMaskThrowsExceptionForInvalidIpAddress(): void
    {
        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        SubnetCalculatorFactory::fromMask('invalid', '255.255.255.0');
    }

    /* ************************ *
     * fromRange() - Valid Cases
     * ************************ */

    /**
     * @test
     * @dataProvider dataProviderForFromRangeValid
     * @param string $startIp
     * @param string $endIp
     * @param int    $expectedSize
     * @param string $expectedNetwork
     */
    public function testFromRangeCreatesValidSubnetCalculator(
        string $startIp,
        string $endIp,
        int $expectedSize,
        string $expectedNetwork
    ): void {
        // When
        $subnet = SubnetCalculatorFactory::fromRange($startIp, $endIp);

        // Then
        $this->assertInstanceOf(SubnetCalculator::class, $subnet);
        $this->assertSame($expectedSize, $subnet->getNetworkSize());
        $this->assertSame($expectedNetwork, $subnet->getNetworkPortion());
        $this->assertSame($startIp, $subnet->getNetworkPortion());
        $this->assertSame($endIp, $subnet->getBroadcastAddress());
    }

    /**
     * @return array[] [startIp, endIp, expectedSize, expectedNetwork]
     */
    public function dataProviderForFromRangeValid(): array
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

    /* ************************* *
     * fromRange() - Error Cases
     * ************************* */

    /**
     * @test
     * @dataProvider dataProviderForFromRangeInvalid
     * @param string $startIp
     * @param string $endIp
     */
    public function testFromRangeThrowsExceptionForInvalidRange(
        string $startIp,
        string $endIp
    ): void {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetCalculatorFactory::fromRange($startIp, $endIp);
    }

    /**
     * @return array[] [startIp, endIp]
     */
    public function dataProviderForFromRangeInvalid(): array
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

    /**
     * @test
     */
    public function testFromRangeThrowsExceptionForInvalidStartIp(): void
    {
        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        SubnetCalculatorFactory::fromRange('invalid', '192.168.1.255');
    }

    /**
     * @test
     */
    public function testFromRangeThrowsExceptionForInvalidEndIp(): void
    {
        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        SubnetCalculatorFactory::fromRange('192.168.1.0', 'invalid');
    }

    /**
     * @test Entire IPv4 space would require /0 which is invalid
     */
    public function testFromRangeThrowsExceptionForEntireIpv4Space(): void
    {
        // Given - The entire IPv4 address space (0.0.0.0 to 255.255.255.255)
        // This would require a /0 network which is not valid

        // Then
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Network size 0 not valid');

        // When
        SubnetCalculatorFactory::fromRange('0.0.0.0', '255.255.255.255');
    }

    /* *************************** *
     * fromHostCount() - Valid Cases
     * *************************** */

    /**
     * @test
     * @dataProvider dataProviderForFromHostCountValid
     * @param string $ipAddress
     * @param int    $hostCount
     * @param int    $expectedSize
     * @param int    $expectedActualHosts
     */
    public function testFromHostCountCreatesValidSubnetCalculator(
        string $ipAddress,
        int $hostCount,
        int $expectedSize,
        int $expectedActualHosts
    ): void {
        // When
        $subnet = SubnetCalculatorFactory::fromHostCount($ipAddress, $hostCount);

        // Then
        $this->assertInstanceOf(SubnetCalculator::class, $subnet);
        $this->assertSame($expectedSize, $subnet->getNetworkSize());
        $this->assertSame($expectedActualHosts, $subnet->getNumberAddressableHosts());
        $this->assertGreaterThanOrEqual($hostCount, $subnet->getNumberAddressableHosts());
    }

    /**
     * @return array[] [ipAddress, hostCount, expectedSize, expectedActualHosts]
     */
    public function dataProviderForFromHostCountValid(): array
    {
        return [
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

    /**
     * @test
     */
    public function testFromHostCountThrowsExceptionForZeroHosts(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetCalculatorFactory::fromHostCount('192.168.1.0', 0);
    }

    /**
     * @test
     */
    public function testFromHostCountThrowsExceptionForNegativeHosts(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetCalculatorFactory::fromHostCount('192.168.1.0', -1);
    }

    /**
     * @test More hosts than IPv4 can accommodate (> 2^32 - 2)
     */
    public function testFromHostCountThrowsExceptionForTooManyHosts(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetCalculatorFactory::fromHostCount('192.168.1.0', 4294967295);
    }

    /**
     * @test
     */
    public function testFromHostCountThrowsExceptionForInvalidIp(): void
    {
        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        SubnetCalculatorFactory::fromHostCount('invalid', 10);
    }

    /* ********************************** *
     * optimalPrefixForHosts() - Valid Cases
     * ********************************** */

    /**
     * @test
     * @dataProvider dataProviderForOptimalPrefixForHostsValid
     * @param int $hostCount
     * @param int $expectedPrefix
     * @param int $expectedActualHosts
     */
    public function testOptimalPrefixForHostsReturnsCorrectPrefix(
        int $hostCount,
        int $expectedPrefix,
        int $expectedActualHosts
    ): void {
        // When
        $prefix = SubnetCalculatorFactory::optimalPrefixForHosts($hostCount);

        // Then
        $this->assertSame($expectedPrefix, $prefix);

        // Verify the prefix provides at least the requested hosts
        $subnet = new SubnetCalculator('10.0.0.0', $prefix);
        $this->assertGreaterThanOrEqual($hostCount, $subnet->getNumberAddressableHosts());
        $this->assertSame($expectedActualHosts, $subnet->getNumberAddressableHosts());
    }

    /**
     * @return array[] [hostCount, expectedPrefix, expectedActualHosts]
     */
    public function dataProviderForOptimalPrefixForHostsValid(): array
    {
        return [
            // Basic cases
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

    /**
     * @test
     */
    public function testOptimalPrefixForHostsThrowsExceptionForZeroHosts(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Host count must be positive, got 0');

        // When
        SubnetCalculatorFactory::optimalPrefixForHosts(0);
    }

    /**
     * @test
     */
    public function testOptimalPrefixForHostsThrowsExceptionForNegativeHosts(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Host count must be positive, got -1');

        // When
        SubnetCalculatorFactory::optimalPrefixForHosts(-1);
    }

    /**
     * @test
     */
    public function testOptimalPrefixForHostsThrowsExceptionForOneOverMaximum(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When - 2147483647 is one over the maximum (2147483646)
        SubnetCalculatorFactory::optimalPrefixForHosts(2147483647);
    }

    /**
     * @test
     */
    public function testOptimalPrefixForHostsThrowsExceptionForTooManyHosts(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        SubnetCalculatorFactory::optimalPrefixForHosts(4294967295);
    }

    /* ****************** *
     * Integration Tests
     * ****************** */

    /**
     * @test
     * Verify that fromCidr produces identical result to direct constructor
     */
    public function testFromCidrMatchesConstructor(): void
    {
        // Given
        $ip = '192.168.1.100';
        $size = 24;

        // When
        $fromFactory = SubnetCalculatorFactory::fromCidr("{$ip}/{$size}");
        $fromConstructor = new SubnetCalculator($ip, $size);

        // Then
        $this->assertSame($fromConstructor->getNetworkPortion(), $fromFactory->getNetworkPortion());
        $this->assertSame($fromConstructor->getNetworkSize(), $fromFactory->getNetworkSize());
        $this->assertSame($fromConstructor->getSubnetMask(), $fromFactory->getSubnetMask());
        $this->assertSame($fromConstructor->getBroadcastAddress(), $fromFactory->getBroadcastAddress());
    }

    /**
     * @test
     * Verify that fromMask produces same network size as expected
     */
    public function testFromMaskNetworkCalculation(): void
    {
        // Given
        $ip = '10.1.2.3';
        $mask = '255.255.0.0';

        // When
        $subnet = SubnetCalculatorFactory::fromMask($ip, $mask);

        // Then
        $this->assertSame(16, $subnet->getNetworkSize());
        $this->assertSame('10.1.0.0', $subnet->getNetworkPortion());
    }

    /**
     * @test
     * Verify fromRange and fromCidr produce equivalent results for same network
     */
    public function testFromRangeMatchesFromCidr(): void
    {
        // Given
        $cidr = '192.168.1.0/24';
        $startIp = '192.168.1.0';
        $endIp = '192.168.1.255';

        // When
        $fromCidr = SubnetCalculatorFactory::fromCidr($cidr);
        $fromRange = SubnetCalculatorFactory::fromRange($startIp, $endIp);

        // Then
        $this->assertSame($fromCidr->getNetworkPortion(), $fromRange->getNetworkPortion());
        $this->assertSame($fromCidr->getNetworkSize(), $fromRange->getNetworkSize());
        $this->assertSame($fromCidr->getBroadcastAddress(), $fromRange->getBroadcastAddress());
    }
}
