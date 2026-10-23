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

    #[Test]
    #[DataProvider('dataProviderForEquals')]
    public function testEqualsComparesNetworkAddressAndPrefix(string $cidr1, string $cidr2, bool $expected): void
    {
        // Given
        $subnet1 = Subnet::fromCidr($cidr1);
        $subnet2 = Subnet::fromCidr($cidr2);

        // When
        $equals = $subnet1->equals($subnet2);
        $reverseEquals = $subnet2->equals($subnet1);

        // Then - Result is as expected and symmetric
        $this->assertSame($expected, $equals);
        $this->assertSame($expected, $reverseEquals);
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
     * @return array<string, array{string, string, bool}>
     */
    public static function dataProviderForEquals(): array
    {
        return [
            'identical'                                     => ['192.168.1.0/24', '192.168.1.0/24', true],
            'different input IPs normalize to same network' => ['192.168.1.100/24', '192.168.1.200/24', true],
            'same network different prefix'                 => ['10.0.0.0/8', '10.0.0.0/16', false],
            'same prefix different network'                 => ['192.168.1.0/24', '192.168.2.0/24', false],
            'different network and prefix'                  => ['192.168.1.0/24', '172.16.0.0/12', false],
            'slash 31 both IPs normalize to same network'   => ['192.0.2.0/31', '192.0.2.1/31', true],
            'slash 31 adjacent networks'                    => ['192.0.2.0/31', '192.0.2.2/31', false],
            'slash 32 same host'                            => ['8.8.8.8/32', '8.8.8.8/32', true],
            'slash 32 different hosts'                      => ['8.8.8.8/32', '8.8.8.9/32', false],
            'slash 32 versus slash 31 same network address' => ['192.0.2.0/32', '192.0.2.0/31', false],
            'slash 0 any input IP'                          => ['0.0.0.0/0', '255.255.255.255/0', true],
            'slash 0 versus slash 1'                        => ['0.0.0.0/0', '0.0.0.0/1', false],
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

    #[Test]
    #[DataProvider('dataProviderForIpAddresses')]
    public function testConstructorFromIPAddressMatchesConstructorFromString(
        string $ipAddress,
        int $networkSize,
        string $cidr
    ): void {
        // Given
        $fromString = new Subnet($ipAddress, $networkSize);

        // When
        $fromIPAddress = new Subnet(new IPAddress($ipAddress), $networkSize);

        // Then - The input IP is preserved and the subnets are equal
        $this->assertSame($cidr, $fromIPAddress->cidr());
        $this->assertSame($fromString->networkCidr(), $fromIPAddress->networkCidr());
        $this->assertTrue($fromIPAddress->equals($fromString));
    }

    /* ************************** *
     * fromCidr() validation tests
     * ************************** */

    #[Test]
    public function testFromCidrThrowsExceptionWhenSlashIsMissing(): void
    {
        // Given - A CIDR string with no slash
        $cidr = '192.168.1.0';

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("missing '/'");

        // When
        Subnet::fromCidr($cidr);
    }

    #[Test]
    public function testFromCidrThrowsExceptionWhenMultipleSlashesPresent(): void
    {
        // Given - A CIDR string with multiple slashes
        $cidr = '192.168.1.0/24/extra';

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("multiple '/'");

        // When
        Subnet::fromCidr($cidr);
    }

    #[Test]
    public function testFromCidrThrowsExceptionWhenPrefixIsNotAnInteger(): void
    {
        // Given - A CIDR string with non-integer prefix
        $cidr = '192.168.1.0/abc';

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('prefix must be an integer');

        // When
        Subnet::fromCidr($cidr);
    }

    #[Test]
    public function testFromCidrThrowsExceptionWhenPrefixIsEmpty(): void
    {
        // Given - A CIDR string with empty prefix
        $cidr = '192.168.1.0/';

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('prefix must be an integer');

        // When
        Subnet::fromCidr($cidr);
    }

    /**
     * @param string $cidr
     */
    #[Test]
    #[DataProvider('dataProviderForLeadingZeroPrefix')]
    public function testFromCidrThrowsExceptionWhenPrefixHasLeadingZeros(string $cidr): void
    {
        // Given - A CIDR string whose prefix has leading zeros, matching IPAddress's rejection of them

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('prefix must be an integer');

        // When
        Subnet::fromCidr($cidr);
    }

    /**
     * @return array[] [cidr]
     */
    public static function dataProviderForLeadingZeroPrefix(): array
    {
        return [
            'zero-padded /24' => ['192.168.1.0/024'],
            'zero-padded /0'  => ['0.0.0.0/00'],
            'zero-padded /8'  => ['10.0.0.0/08'],
            'zero-padded /31' => ['10.0.0.0/031'],
            'zero-padded /32' => ['10.0.0.1/032'],
        ];
    }

    /**
     * @param string $cidr
     */
    #[Test]
    #[DataProvider('dataProviderForOversizedPrefix')]
    public function testFromCidrThrowsExceptionReportingInputWhenPrefixIsOutOfRange(string $cidr): void
    {
        // Given - A CIDR string whose prefix is negative or exceeds 32, including one too large to fit in an int

        // Then - The message quotes the input as typed rather than a clamped integer
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid CIDR notation: prefix must be between 0 and 32 in '{$cidr}'");

        // When
        Subnet::fromCidr($cidr);
    }

    /**
     * @return array[] [cidr]
     */
    public static function dataProviderForOversizedPrefix(): array
    {
        return [
            '/33'                => ['192.168.1.0/33'],
            '/100'               => ['192.168.1.0/100'],
            '/-1'                => ['192.168.1.0/-1'],
            '/-24'               => ['192.168.1.0/-24'],
            'beyond PHP_INT_MAX' => ['192.168.1.0/99999999999999999999'],
        ];
    }

    /**
     * @param string $cidr
     * @param int    $expectedPrefix
     */
    #[Test]
    #[DataProvider('dataProviderForCanonicalPrefix')]
    public function testFromCidrAcceptsCanonicalPrefix(string $cidr, int $expectedPrefix): void
    {
        // Given - A CIDR string with a canonical decimal prefix

        // When
        $subnet = Subnet::fromCidr($cidr);

        // Then
        $this->assertSame($expectedPrefix, $subnet->networkSize());
    }

    /**
     * @return array[] [cidr, expectedPrefix]
     */
    public static function dataProviderForCanonicalPrefix(): array
    {
        return [
            '/0'  => ['0.0.0.0/0', 0],
            '/8'  => ['10.0.0.0/8', 8],
            '/10' => ['100.64.0.0/10', 10],
            '/20' => ['10.0.0.0/20', 20],
            '/30' => ['10.0.0.0/30', 30],
            '/31' => ['10.0.0.0/31', 31],
            '/32' => ['10.0.0.1/32', 32],
        ];
    }
}
