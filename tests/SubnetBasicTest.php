<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\IPAddress;
use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class SubnetBasicTest extends \PHPUnit\Framework\TestCase
{
    /** @var Subnet */
    private Subnet $sub;

    /**
     * Set up test Subnet
     */
    public function setUp(): void
    {
        $this->sub = new Subnet('192.168.112.203', 23);
    }

    #[Test]
    public function testLibraryRequires64BitPhp(): void
    {
        // Given - This test documents and enforces the 64-bit requirement

        // When / Then
        $this->assertSame(8, \PHP_INT_SIZE, 'IPv4 Subnet Calculator requires 64-bit PHP');
    }

    #[Test]
    #[DataProvider('dataProviderForFromIntegerWrapping')]
    public function testFromIntegerWrapsOutOfRangeValues(int $input, string $expectedIp): void
    {
        // Given - Out-of-range integers are wrapped using modulo 2^32,
        // consistent with PHP's long2ip() behavior

        // When
        $ip = IPAddress::fromInteger($input);

        // Then
        $this->assertSame($expectedIp, $ip->asQuads());
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function dataProviderForFromIntegerWrapping(): array
    {
        return [
            'negative -1 wraps to max IP' => [-1, '255.255.255.255'],
            'overflow wraps to 0.0.0.0' => [4_294_967_296, '0.0.0.0'],
            'overflow + 1 wraps to 0.0.0.1' => [4_294_967_297, '0.0.0.1'],
            'valid min boundary' => [0, '0.0.0.0'],
            'valid max boundary' => [4_294_967_295, '255.255.255.255'],
            'valid mid-range' => [3_232_235_777, '192.168.1.1'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIpAddresses')]
    public function testGetIpAddress(string $given_ip_address, int $network_size): void
    {
        // Given
        $sub = new Subnet($given_ip_address, $network_size);

        // When
        $ip_address = $sub->ipAddress()->asQuads();

        // Then
        $this->assertSame($given_ip_address, $ip_address);
    }

    #[Test]
    #[DataProvider('dataProviderForIpAddresses')]
    public function testGetNetworkSize(string $ip_address, int $given_network_size): void
    {
        // Given
        $sub = new Subnet($ip_address, $given_network_size);

        // When
        $network_size = $sub->networkSize();

        // Then
        $this->assertSame($given_network_size, $network_size);
    }

    #[Test]
    #[DataProvider('dataProviderForIpAddresses')]
    public function testGetCidrNotation(string $ip_address, int $given_network_size, string $expectedCidrNotation): void
    {
        // Given
        $sub = new Subnet($ip_address, $given_network_size);

        // When
        $cidrNotation = $sub->cidr();

        // Then
        $this->assertSame($expectedCidrNotation, $cidrNotation);
    }

    #[Test]
    #[DataProvider('dataProviderForNetworkCidr')]
    public function testGetNetworkCidrReturnsCanonicalForm(
        string $inputIp,
        int $networkSize,
        string $expectedNetworkCidr
    ): void {
        // Given - A subnet with an arbitrary IP within the subnet
        $sub = new Subnet($inputIp, $networkSize);

        // When - Getting the canonical network CIDR notation
        $networkCidr = $sub->networkCidr();

        // Then - Should always return the network address with prefix
        $this->assertSame($expectedNetworkCidr, $networkCidr);
    }

    #[Test]
    #[DataProvider('dataProviderForNetworkCidrEquality')]
    public function testNetworkCidrProducesIdenticalStringsForEqualSubnets(
        string $ip1,
        string $ip2,
        int $networkSize
    ): void {
        // Given - Two different IPs in the same subnet
        $subnet1 = new Subnet($ip1, $networkSize);
        $subnet2 = new Subnet($ip2, $networkSize);

        // When - Getting canonical network CIDR
        $networkCidr1 = $subnet1->networkCidr();
        $networkCidr2 = $subnet2->networkCidr();

        // Then - Should be identical strings
        $this->assertSame($networkCidr1, $networkCidr2);
        $this->assertTrue($subnet1->equals($subnet2));
    }

    #[Test]
    #[DataProvider('dataProviderForNetworkCidrEquality')]
    public function testCidrPreservesInputIpWhileNetworkCidrIsCanonical(
        string $ip1,
        string $ip2,
        int $networkSize
    ): void {
        // Given - Two different IPs in the same subnet
        $subnet1 = new Subnet($ip1, $networkSize);
        $subnet2 = new Subnet($ip2, $networkSize);

        // When - Getting CIDR notation
        $cidr1 = $subnet1->cidr();
        $cidr2 = $subnet2->cidr();

        // Then - cidr() should preserve input IPs (different strings)
        $this->assertNotSame($cidr1, $cidr2);

        // But networkCidr() should be identical
        $this->assertSame($subnet1->networkCidr(), $subnet2->networkCidr());
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function dataProviderForNetworkCidr(): array
    {
        return [
            'class C with host IP' => ['192.168.1.100', 24, '192.168.1.0/24'],
            'class C with network IP' => ['192.168.1.0', 24, '192.168.1.0/24'],
            'class C with broadcast IP' => ['192.168.1.255', 24, '192.168.1.0/24'],
            'slash 16 with host IP' => ['10.20.30.40', 16, '10.20.0.0/16'],
            'slash 8 with host IP' => ['172.25.100.200', 8, '172.0.0.0/8'],
            'slash 30 with host IP' => ['203.0.113.14', 30, '203.0.113.12/30'],
            'slash 31 point-to-point first' => ['192.0.2.0', 31, '192.0.2.0/31'],
            'slash 31 point-to-point second' => ['192.0.2.1', 31, '192.0.2.0/31'],
            'slash 32 single host' => ['8.8.8.8', 32, '8.8.8.8/32'],
            'slash 0 entire IPv4 space' => ['123.45.67.89', 0, '0.0.0.0/0'],
        ];
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function dataProviderForNetworkCidrEquality(): array
    {
        return [
            'slash 24 different hosts' => ['192.168.1.100', '192.168.1.1', 24],
            'slash 16 different hosts' => ['10.20.30.40', '10.20.50.60', 16],
            'slash 30 different hosts' => ['203.0.113.13', '203.0.113.14', 30],
            'slash 31 both IPs' => ['192.0.2.0', '192.0.2.1', 31],
        ];
    }

    /**
     * @return array[] [ip_address, network_size, CIDR notation]
     */
    public static function dataProviderForIpAddresses(): array
    {
        return [
            ['10.0.0.1', 0, '10.0.0.1/0'],
            ['192.168.112.203', 1, '192.168.112.203/1'],
            ['192.168.84.233', 2, '192.168.84.233/2'],
            ['10.10.122.113', 3, '10.10.122.113/3'],
            ['255.255.255.255', 4, '255.255.255.255/4'],
            ['192.168.112.207', 5, '192.168.112.207/5'],
            ['192.128.0.1', 6, '192.128.0.1/6'],
            ['128.0.0.0', 7, '128.0.0.0/7'],
            ['235.90.125.222', 8, '235.90.125.222/8'],
            ['208.153.158.185', 9, '208.153.158.185/9'],
            ['99.107.189.17', 10, '99.107.189.17/10'],
            ['233.126.142.167', 11, '233.126.142.167/11'],
            ['205.39.43.86', 12, '205.39.43.86/12'],
            ['158.114.74.115', 13, '158.114.74.115/13'],
            ['127.132.3.128', 14, '127.132.3.128/14'],
            ['243.73.87.101', 15, '243.73.87.101/15'],
            ['176.103.67.129', 16, '176.103.67.129/16'],
            ['190.113.28.0', 17, '190.113.28.0/17'],
            ['204.243.103.224', 18, '204.243.103.224/18'],
            ['203.247.20.148', 19, '203.247.20.148/19'],
            ['15.254.55.4', 20, '15.254.55.4/20'],
            ['96.245.55.29', 21, '96.245.55.29/21'],
            ['88.102.195.7', 22, '88.102.195.7/22'],
            ['144.60.195.68', 23, '144.60.195.68/23'],
            ['189.191.237.105', 24, '189.191.237.105/24'],
            ['98.79.29.150', 25, '98.79.29.150/25'],
            ['56.5.145.126', 26, '56.5.145.126/26'],
            ['80.170.127.173', 27, '80.170.127.173/27'],
            ['92.123.10.117', 28, '92.123.10.117/28'],
            ['88.52.155.198', 29, '88.52.155.198/29'],
            ['230.233.123.40', 30, '230.233.123.40/30'],
            ['254.17.211.42', 31, '254.17.211.42/31'],
            ['57.51.231.108', 32, '57.51.231.108/32'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForBadIpAddresses')]
    public function testConstructorExceptionOnBadIPAddress(string $ip_address): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $sub = new Subnet($ip_address, 24);
    }

    /**
     * @return string[][] [ip_address]
     */
    public static function dataProviderForBadIpAddresses(): array
    {
        return [
            ['-1.168.3.4'],
            ['256.168.3.4'],
            ['555.444.333.222'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForBadNetworkSize')]
    public function testConstructorExceptionOnBadNetworkSize(int $network_size): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $sub = new Subnet('192.168.112.203', $network_size);
    }

    /**
     * @return int[][] [network_size]
     */
    public static function dataProviderForBadNetworkSize(): array
    {
        return [
            [-2],
            [-1],
            [33],
            [34],
            [89394839],
        ];
    }
}
