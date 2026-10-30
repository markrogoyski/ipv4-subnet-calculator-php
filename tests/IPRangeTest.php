<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\IPAddress;
use IPv4\IPRange;
use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class IPRangeTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    public function testConstructorCreatesValidRange(): void
    {
        // Given
        $start = new IPAddress('192.168.1.0');
        $end = new IPAddress('192.168.1.255');

        // When
        $range = new IPRange($start, $end);

        // Then
        $this->assertSame($start, $range->start());
        $this->assertSame($end, $range->end());
    }

    #[Test]
    public function testConstructorThrowsExceptionWhenStartGreaterThanEnd(): void
    {
        // Given
        $start = new IPAddress('192.168.1.100');
        $end = new IPAddress('192.168.1.50');

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Start IP '192.168.1.100' is greater than end IP '192.168.1.50'");

        // When
        new IPRange($start, $end);
    }

    #[Test]
    #[DataProvider('dataProviderForInvalidRanges')]
    public function testConstructorValidatesRangeOrder(string $startIp, string $endIp): void
    {
        // Given
        $start = new IPAddress($startIp);
        $end = new IPAddress($endIp);

        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        new IPRange($start, $end);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function dataProviderForInvalidRanges(): array
    {
        return [
            'reversed small range' => ['192.168.1.10', '192.168.1.5'],
            'reversed large range' => ['10.255.255.255', '10.0.0.0'],
            'max to min' => ['255.255.255.255', '0.0.0.0'],
            'adjacent reversed' => ['192.168.1.2', '192.168.1.1'],
        ];
    }

    #[Test]
    public function testStartReturnsFirstIpAddress(): void
    {
        // Given
        $start = new IPAddress('10.0.0.0');
        $end = new IPAddress('10.0.0.255');
        $range = new IPRange($start, $end);

        // When
        $result = $range->start();

        // Then
        $this->assertSame($start, $result);
        $this->assertSame('10.0.0.0', $result->asQuads());
    }

    #[Test]
    public function testEndReturnsLastIpAddress(): void
    {
        // Given
        $start = new IPAddress('10.0.0.0');
        $end = new IPAddress('10.0.0.255');
        $range = new IPRange($start, $end);

        // When
        $result = $range->end();

        // Then
        $this->assertSame($end, $result);
        $this->assertSame('10.0.0.255', $result->asQuads());
    }

    #[Test]
    #[DataProvider('dataProviderForCount')]
    public function testCountReturnsCorrectNumberOfAddresses(string $startIp, string $endIp, int $expectedCount): void
    {
        // Given
        $start = new IPAddress($startIp);
        $end = new IPAddress($endIp);
        $range = new IPRange($start, $end);

        // When
        $result = $range->count();

        // Then
        $this->assertSame($expectedCount, $result);
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function dataProviderForCount(): array
    {
        return [
            'single IP' => ['192.168.1.1', '192.168.1.1', 1],
            'two IPs' => ['192.168.1.1', '192.168.1.2', 2],
            '/30 subnet (4 IPs)' => ['192.168.1.0', '192.168.1.3', 4],
            '/29 subnet (8 IPs)' => ['192.168.1.0', '192.168.1.7', 8],
            '/28 subnet (16 IPs)' => ['192.168.1.0', '192.168.1.15', 16],
            '/27 subnet (32 IPs)' => ['192.168.1.0', '192.168.1.31', 32],
            '/26 subnet (64 IPs)' => ['192.168.1.0', '192.168.1.63', 64],
            '/25 subnet (128 IPs)' => ['192.168.1.0', '192.168.1.127', 128],
            '/24 subnet (256 IPs)' => ['192.168.1.0', '192.168.1.255', 256],
            '/16 subnet (65536 IPs)' => ['192.168.0.0', '192.168.255.255', 65_536],
            'full range' => ['0.0.0.0', '255.255.255.255', 4_294_967_296],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIndexedAddresses')]
    public function testAtReturnsAddressAtZeroBasedIndex(string $start, string $end, int $index, string $expectedIp): void
    {
        // Given
        $range = new IPRange(new IPAddress($start), new IPAddress($end));

        // When
        $ip = $range->at($index);

        // Then
        $this->assertSame($expectedIp, $ip->asQuads());
    }

    /**
     * @return array<string, array{string, string, int, string}>
     */
    public static function dataProviderForIndexedAddresses(): array
    {
        return [
            'first address'                  => ['192.0.2.10', '192.0.2.20', 0, '192.0.2.10'],
            'interior address'               => ['192.0.2.10', '192.0.2.20', 5, '192.0.2.15'],
            'last address'                   => ['192.0.2.10', '192.0.2.20', 10, '192.0.2.20'],
            'singleton'                      => ['192.0.2.7', '192.0.2.7', 0, '192.0.2.7'],
            'crossing an octet'              => ['192.0.2.254', '192.0.3.1', 2, '192.0.3.0'],
            'crossing two octets'            => ['10.0.255.255', '10.1.0.1', 1, '10.1.0.0'],
            'crossing signed 32-bit limit'   => ['127.255.255.254', '128.0.0.1', 2, '128.0.0.0'],
            'full IPv4 range first address'  => ['0.0.0.0', '255.255.255.255', 0, '0.0.0.0'],
            'full IPv4 range upper half'     => ['0.0.0.0', '255.255.255.255', 2_147_483_648, '128.0.0.0'],
            'full IPv4 range last address'   => ['0.0.0.0', '255.255.255.255', 4_294_967_295, '255.255.255.255'],
            'nonzero start at IPv4 boundary' => ['255.255.255.250', '255.255.255.255', 5, '255.255.255.255'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForInvalidAddressIndices')]
    public function testAtRejectsIndicesOutsideRange(string $start, string $end, int $index): void
    {
        // Given
        $range = new IPRange(new IPAddress($start), new IPAddress($end));

        // Then
        $this->expectException(\OutOfRangeException::class);

        // When
        $range->at($index);
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function dataProviderForInvalidAddressIndices(): array
    {
        return [
            'negative index'          => ['192.0.2.10', '192.0.2.20', -1],
            'index equal to count'    => ['192.0.2.10', '192.0.2.20', 11],
            'index beyond count'      => ['192.0.2.10', '192.0.2.20', 12],
            'singleton past end'      => ['255.255.255.255', '255.255.255.255', 1],
            'minimum integer'         => ['192.0.2.10', '192.0.2.20', \PHP_INT_MIN],
            'maximum integer'         => ['192.0.2.10', '192.0.2.20', \PHP_INT_MAX],
            'full IPv4 negative'      => ['0.0.0.0', '255.255.255.255', -1],
            'full IPv4 past end'      => ['0.0.0.0', '255.255.255.255', 4_294_967_296],
            'full IPv4 maximum index' => ['0.0.0.0', '255.255.255.255', \PHP_INT_MAX],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIndexedHosts')]
    public function testAtUsesUsableHostRange(string $cidr, int $index, string $expectedIp): void
    {
        // Given
        $range = Subnet::fromCidr($cidr)->hostRange();

        // When
        $ip = $range->at($index);

        // Then
        $this->assertSame($expectedIp, $ip->asQuads());
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function dataProviderForIndexedHosts(): array
    {
        return [
            '/30 first host'      => ['192.0.2.1/30', 0, '192.0.2.1'],
            '/30 last host'       => ['192.0.2.1/30', 1, '192.0.2.2'],
            '/31 first host'      => ['192.0.2.7/31', 0, '192.0.2.6'],
            '/31 last host'       => ['192.0.2.7/31', 1, '192.0.2.7'],
            '/32 only host'       => ['192.0.2.7/32', 0, '192.0.2.7'],
            '/31 IPv4 last host'  => ['255.255.255.255/31', 1, '255.255.255.255'],
            '/32 IPv4 first host' => ['0.0.0.0/32', 0, '0.0.0.0'],
            '/32 IPv4 last host'  => ['255.255.255.255/32', 0, '255.255.255.255'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForHostRangeBounds')]
    public function testAtRejectsIndexEqualToHostCount(string $cidr): void
    {
        // Given
        $range = Subnet::fromCidr($cidr)->hostRange();

        // Then
        $this->expectException(\OutOfRangeException::class);

        // When
        $range->at($range->count());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dataProviderForHostRangeBounds(): array
    {
        return [
            '/30' => ['192.0.2.0/30'],
            '/31' => ['192.0.2.6/31'],
            '/32' => ['192.0.2.7/32'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIndexedAddresses')]
    public function testIndexOfReturnsZeroBasedIndex(string $start, string $end, int $expectedIndex, string $ip): void
    {
        // Given
        $range = new IPRange(new IPAddress($start), new IPAddress($end));

        // When
        $stringIndex = $range->indexOf($ip);
        $objectIndex = $range->indexOf(new IPAddress($ip));

        // Then
        $this->assertSame($expectedIndex, $stringIndex);
        $this->assertSame($expectedIndex, $objectIndex);
    }

    #[Test]
    #[DataProvider('dataProviderForIndexedAddresses')]
    public function testIndexOfInvertsAt(string $start, string $end, int $index, string $expectedIp): void
    {
        // Given
        $range = new IPRange(new IPAddress($start), new IPAddress($end));

        // When
        $ip = $range->at($index);

        // Then
        $this->assertSame($expectedIp, $ip->asQuads());
        $this->assertSame($index, $range->indexOf($ip));
    }

    #[Test]
    #[DataProvider('dataProviderForAddressesOutsideRange')]
    public function testIndexOfReturnsNullOutsideRange(string $start, string $end, string $ip): void
    {
        // Given
        $range = new IPRange(new IPAddress($start), new IPAddress($end));

        // When
        $stringIndex = $range->indexOf($ip);
        $objectIndex = $range->indexOf(new IPAddress($ip));

        // Then
        $this->assertNull($stringIndex);
        $this->assertNull($objectIndex);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function dataProviderForAddressesOutsideRange(): array
    {
        return [
            'immediately before range' => ['192.0.2.10', '192.0.2.20', '192.0.2.9'],
            'immediately after range'  => ['192.0.2.10', '192.0.2.20', '192.0.2.21'],
            'before singleton'         => ['192.0.2.7', '192.0.2.7', '192.0.2.6'],
            'after singleton'          => ['192.0.2.7', '192.0.2.7', '192.0.2.8'],
            'first IPv4 address'       => ['0.0.0.1', '255.255.255.254', '0.0.0.0'],
            'last IPv4 address'        => ['0.0.0.1', '255.255.255.254', '255.255.255.255'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForMalformedIndexedAddresses')]
    public function testIndexOfRejectsMalformedAddress(string $ip): void
    {
        // Given
        $range = Subnet::fromCidr('0.0.0.0/0')->addressRange();

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid IPv4 address: '{$ip}'");

        // When
        $range->indexOf($ip);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dataProviderForMalformedIndexedAddresses(): array
    {
        return [
            'empty'          => [''],
            'nonnumeric'     => ['invalid'],
            'integer string' => ['3221225985'],
            'IPv6'           => ['::1'],
            'missing octet'  => ['192.0.2'],
            'invalid octet'  => ['192.0.2.256'],
            'leading zero'   => ['192.0.2.01'],
            'CIDR'           => ['192.0.2.1/24'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIndexedHosts')]
    public function testIndexOfUsesUsableHostRange(string $cidr, int $expectedIndex, string $ip): void
    {
        // Given
        $range = Subnet::fromCidr($cidr)->hostRange();

        // When
        $index = $range->indexOf($ip);

        // Then
        $this->assertSame($expectedIndex, $index);
        $this->assertSame($expectedIndex, $range->indexOf($range->at($expectedIndex)));
    }

    #[Test]
    #[DataProvider('dataProviderForAddressesOutsideHostRange')]
    public function testIndexOfReturnsNullOutsideHostRange(string $cidr, string $ip): void
    {
        // Given
        $range = Subnet::fromCidr($cidr)->hostRange();

        // When
        $index = $range->indexOf($ip);

        // Then
        $this->assertNull($index);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function dataProviderForAddressesOutsideHostRange(): array
    {
        return [
            '/30 network'   => ['192.0.2.0/30', '192.0.2.0'],
            '/30 broadcast' => ['192.0.2.0/30', '192.0.2.3'],
            'before /31'    => ['192.0.2.6/31', '192.0.2.5'],
            'after /31'     => ['192.0.2.6/31', '192.0.2.8'],
            'before /32'    => ['192.0.2.7/32', '192.0.2.6'],
            'after /32'     => ['192.0.2.7/32', '192.0.2.8'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForContainsWithString')]
    public function testContainsWithStringIdentifiesIpsInRange(
        string $startIp,
        string $endIp,
        string $testIp,
        bool $expectedResult
    ): void {
        // Given
        $start = new IPAddress($startIp);
        $end = new IPAddress($endIp);
        $range = new IPRange($start, $end);

        // When
        $result = $range->contains($testIp);

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, string, string, bool}>
     */
    public static function dataProviderForContainsWithString(): array
    {
        return [
            'IP at start of range' => ['192.168.1.0', '192.168.1.255', '192.168.1.0', true],
            'IP at end of range' => ['192.168.1.0', '192.168.1.255', '192.168.1.255', true],
            'IP in middle of range' => ['192.168.1.0', '192.168.1.255', '192.168.1.128', true],
            'IP before range' => ['192.168.1.100', '192.168.1.200', '192.168.1.99', false],
            'IP after range' => ['192.168.1.100', '192.168.1.200', '192.168.1.201', false],
            'IP in different subnet' => ['192.168.1.0', '192.168.1.255', '192.168.2.1', false],
            'single IP range contains itself' => ['10.0.0.1', '10.0.0.1', '10.0.0.1', true],
            'single IP range does not contain different' => ['10.0.0.1', '10.0.0.1', '10.0.0.2', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForContainsWithIpAddress')]
    public function testContainsWithIpAddressIdentifiesIpsInRange(
        string $startIp,
        string $endIp,
        string $testIp,
        bool $expectedResult
    ): void {
        // Given
        $start = new IPAddress($startIp);
        $end = new IPAddress($endIp);
        $range = new IPRange($start, $end);
        $testIpAddress = new IPAddress($testIp);

        // When
        $result = $range->contains($testIpAddress);

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, string, string, bool}>
     */
    public static function dataProviderForContainsWithIpAddress(): array
    {
        return [
            'IPAddress at start' => ['10.0.0.0', '10.0.0.255', '10.0.0.0', true],
            'IPAddress at end' => ['10.0.0.0', '10.0.0.255', '10.0.0.255', true],
            'IPAddress in middle' => ['10.0.0.0', '10.0.0.255', '10.0.0.100', true],
            'IPAddress before range' => ['10.0.0.50', '10.0.0.100', '10.0.0.49', false],
            'IPAddress after range' => ['10.0.0.50', '10.0.0.100', '10.0.0.101', false],
        ];
    }

    #[Test]
    public function testEqualsReturnsTrueForSameRange(): void
    {
        // Given
        $start1 = new IPAddress('192.168.1.0');
        $end1 = new IPAddress('192.168.1.255');
        $range1 = new IPRange($start1, $end1);

        $start2 = new IPAddress('192.168.1.0');
        $end2 = new IPAddress('192.168.1.255');
        $range2 = new IPRange($start2, $end2);

        // When
        $result = $range1->equals($range2);

        // Then
        $this->assertTrue($result);
    }

    #[Test]
    public function testEqualsReturnsFalseForDifferentRanges(): void
    {
        // Given
        $start1 = new IPAddress('192.168.1.0');
        $end1 = new IPAddress('192.168.1.255');
        $range1 = new IPRange($start1, $end1);

        $start2 = new IPAddress('192.168.2.0');
        $end2 = new IPAddress('192.168.2.255');
        $range2 = new IPRange($start2, $end2);

        // When
        $result = $range1->equals($range2);

        // Then
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('dataProviderForEquals')]
    public function testEqualsComparesRangesCorrectly(
        string $start1Ip,
        string $end1Ip,
        string $start2Ip,
        string $end2Ip,
        bool $expectedResult
    ): void {
        // Given
        $range1 = new IPRange(new IPAddress($start1Ip), new IPAddress($end1Ip));
        $range2 = new IPRange(new IPAddress($start2Ip), new IPAddress($end2Ip));

        // When
        $result = $range1->equals($range2);

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, string, string, string, bool}>
     */
    public static function dataProviderForEquals(): array
    {
        return [
            'identical ranges' => ['192.168.1.0', '192.168.1.255', '192.168.1.0', '192.168.1.255', true],
            'different start' => ['192.168.1.0', '192.168.1.255', '192.168.1.1', '192.168.1.255', false],
            'different end' => ['192.168.1.0', '192.168.1.255', '192.168.1.0', '192.168.1.254', false],
            'completely different' => ['10.0.0.0', '10.0.0.255', '172.16.0.0', '172.16.0.255', false],
            'single IP ranges equal' => ['10.0.0.1', '10.0.0.1', '10.0.0.1', '10.0.0.1', true],
            'single IP ranges different' => ['10.0.0.1', '10.0.0.1', '10.0.0.2', '10.0.0.2', false],
        ];
    }

    #[Test]
    public function testGetIteratorYieldsAllIpsInRange(): void
    {
        // Given
        $start = new IPAddress('192.168.1.0');
        $end = new IPAddress('192.168.1.3');
        $range = new IPRange($start, $end);

        // When
        $ips = [];
        foreach ($range as $ip) {
            $ips[] = $ip->asQuads();
        }

        // Then
        $expected = ['192.168.1.0', '192.168.1.1', '192.168.1.2', '192.168.1.3'];
        $this->assertSame($expected, $ips);
    }

    #[Test]
    #[DataProvider('dataProviderForIteration')]
    public function testIterationProducesCorrectSequence(string $startIp, string $endIp, array $expectedIps): void
    {
        // Given
        $start = new IPAddress($startIp);
        $end = new IPAddress($endIp);
        $range = new IPRange($start, $end);

        // When
        $ips = [];
        foreach ($range as $ip) {
            $ips[] = $ip->asQuads();
        }

        // Then
        $this->assertSame($expectedIps, $ips);
    }

    /**
     * @return array<string, array{string, string, string[]}>
     */
    public static function dataProviderForIteration(): array
    {
        return [
            'single IP' => [
                '192.168.1.1',
                '192.168.1.1',
                ['192.168.1.1'],
            ],
            'two IPs' => [
                '192.168.1.1',
                '192.168.1.2',
                ['192.168.1.1', '192.168.1.2'],
            ],
            'five IPs' => [
                '10.0.0.0',
                '10.0.0.4',
                ['10.0.0.0', '10.0.0.1', '10.0.0.2', '10.0.0.3', '10.0.0.4'],
            ],
            'crossing octet boundary' => [
                '192.168.0.254',
                '192.168.1.1',
                ['192.168.0.254', '192.168.0.255', '192.168.1.0', '192.168.1.1'],
            ],
        ];
    }

    #[Test]
    public function testToArrayReturnsAllIpsAsArray(): void
    {
        // Given
        $start = new IPAddress('10.0.0.0');
        $end = new IPAddress('10.0.0.2');
        $range = new IPRange($start, $end);

        // When
        $ips = $range->toArray();

        // Then
        $this->assertCount(3, $ips);
        $this->assertContainsOnlyInstancesOf(IPAddress::class, $ips);
        $this->assertSame('10.0.0.0', $ips[0]->asQuads());
        $this->assertSame('10.0.0.1', $ips[1]->asQuads());
        $this->assertSame('10.0.0.2', $ips[2]->asQuads());
    }

    #[Test]
    #[DataProvider('dataProviderForToArray')]
    public function testToArrayProducesCorrectArray(string $startIp, string $endIp, int $expectedCount): void
    {
        // Given
        $start = new IPAddress($startIp);
        $end = new IPAddress($endIp);
        $range = new IPRange($start, $end);

        // When
        $ips = $range->toArray();

        // Then
        $this->assertCount($expectedCount, $ips);
        $this->assertContainsOnlyInstancesOf(IPAddress::class, $ips);
        $this->assertSame($startIp, $ips[0]->asQuads());
        $this->assertSame($endIp, $ips[$expectedCount - 1]->asQuads());
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function dataProviderForToArray(): array
    {
        return [
            'single IP' => ['192.168.1.1', '192.168.1.1', 1],
            'two IPs' => ['192.168.1.1', '192.168.1.2', 2],
            'small range' => ['10.0.0.0', '10.0.0.9', 10],
            '/28 subnet' => ['192.168.1.0', '192.168.1.15', 16],
        ];
    }

    /**
     * @param list<string> $expectedCidrs
     */
    #[Test]
    #[DataProvider('dataProviderForToSubnets')]
    public function testToSubnetsReturnsMinimalExactCidrCover(
        string $startIp,
        string $endIp,
        array $expectedCidrs
    ): void {
        // Given
        $range = new IPRange(new IPAddress($startIp), new IPAddress($endIp));

        // When
        $subnets = $range->toSubnets();

        // Then
        $this->assertSame($expectedCidrs, \array_map(static fn (Subnet $subnet): string => $subnet->cidr(), $subnets));
        $this->assertExactMinimalCover($range, $subnets);
    }

    /**
     * @return array<string, array{string, string, list<string>}>
     */
    public static function dataProviderForToSubnets(): array
    {
        return [
            'single address /32'                        => ['192.0.2.7', '192.0.2.7', ['192.0.2.7/32']],
            'first address /32'                         => ['0.0.0.0', '0.0.0.0', ['0.0.0.0/32']],
            'last address /32'                          => ['255.255.255.255', '255.255.255.255', ['255.255.255.255/32']],
            'entire address space /0'                   => ['0.0.0.0', '255.255.255.255', ['0.0.0.0/0']],
            'aligned /31'                               => ['192.0.2.6', '192.0.2.7', ['192.0.2.6/31']],
            'aligned /24'                               => ['192.0.2.0', '192.0.2.255', ['192.0.2.0/24']],
            'unaligned two addresses'                   => ['192.0.2.1', '192.0.2.2', ['192.0.2.1/32', '192.0.2.2/32']],
            'unaligned six addresses'                   => [
                '192.0.2.1', '192.0.2.6',
                ['192.0.2.1/32', '192.0.2.2/31', '192.0.2.4/31', '192.0.2.6/32'],
            ],
            'crossing an octet'                         => [
                '192.0.2.254', '192.0.3.1',
                ['192.0.2.254/31', '192.0.3.0/31'],
            ],
            'crossing two octets'                       => [
                '10.0.255.255', '10.1.0.0',
                ['10.0.255.255/32', '10.1.0.0/32'],
            ],
            'crossing the signed 32-bit boundary'       => [
                '127.255.255.254', '128.0.0.1',
                ['127.255.255.254/31', '128.0.0.0/31'],
            ],
            'unaligned start ending at maximum address' => [
                '255.255.255.249', '255.255.255.255',
                ['255.255.255.249/32', '255.255.255.250/31', '255.255.255.252/30'],
            ],
            'zero start with unaligned end'             => [
                '0.0.0.0', '0.0.0.6',
                ['0.0.0.0/30', '0.0.0.4/31', '0.0.0.6/32'],
            ],
            'upper half of address space'               => ['128.0.0.0', '255.255.255.255', ['128.0.0.0/1']],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForBoundedRanges')]
    public function testToSubnetsMatchesIndependentlyEnumeratedAddresses(int $start, int $end): void
    {
        // Given
        $range = new IPRange(IPAddress::fromInteger($start), IPAddress::fromInteger($end));
        $expectedAddresses = \range($start, $end);

        // When
        $subnets = $range->toSubnets();

        // Then
        $this->assertExactMinimalCover($range, $subnets);
        $actualAddresses = [];
        foreach ($subnets as $subnet) {
            foreach ($subnet->addressRange() as $ip) {
                $actualAddresses[] = $ip->asInteger();
            }
        }
        $this->assertSame($expectedAddresses, $actualAddresses);
    }

    /**
     * Every nonempty subrange of four eight-address windows, including IPv4 boundaries.
     *
     * @return \Generator<string, array{int, int}>
     */
    public static function dataProviderForBoundedRanges(): \Generator
    {
        foreach ([0, 0xC00001FC, 0x7FFFFFFC, 0xFFFFFFF8] as $base) {
            for ($start = $base; $start < $base + 8; $start++) {
                for ($end = $start; $end < $base + 8; $end++) {
                    yield "{$start}-{$end}" => [$start, $end];
                }
            }
        }
    }

    /**
     * @param list<Subnet> $subnets
     */
    private function assertExactMinimalCover(IPRange $range, array $subnets): void
    {
        $this->assertNotEmpty($subnets);
        $this->assertSame(\range(0, \count($subnets) - 1), \array_keys($subnets));
        $cursor = $range->start()->asInteger();
        $addressCount = 0;
        $previous = null;

        foreach ($subnets as $subnet) {
            $this->assertSame($subnet->networkCidr(), $subnet->cidr(), 'Each subnet must be canonical.');
            $this->assertSame($cursor, $subnet->networkAddress()->asInteger(), 'Blocks must be sorted without gaps or overlap.');
            $this->assertLessThanOrEqual($range->end()->asInteger(), $subnet->broadcastAddress()->asInteger());

            if ($previous !== null && $previous->networkSize() === $subnet->networkSize()) {
                $parentSize = 2 * $subnet->addressCount();
                $this->assertNotSame(
                    \intdiv($previous->networkAddress()->asInteger(), $parentSize),
                    \intdiv($subnet->networkAddress()->asInteger(), $parentSize),
                    'No adjacent output blocks may be mergeable siblings.'
                );
            }

            $cursor = $subnet->broadcastAddress()->asInteger() + 1;
            $addressCount += $subnet->addressCount();
            $previous = $subnet;
        }

        $this->assertSame($range->end()->asInteger() + 1, $cursor);
        $this->assertSame($range->count(), $addressCount);
    }

    #[Test]
    public function testToStringReturnsRangeRepresentation(): void
    {
        // Given
        $start = new IPAddress('192.168.1.0');
        $end = new IPAddress('192.168.1.255');
        $range = new IPRange($start, $end);

        // When
        $result = (string) $range;

        // Then
        $this->assertSame('192.168.1.0 - 192.168.1.255', $result);
    }

    #[Test]
    #[DataProvider('dataProviderForToString')]
    public function testToStringFormatsCorrectly(string $startIp, string $endIp, string $expectedString): void
    {
        // Given
        $start = new IPAddress($startIp);
        $end = new IPAddress($endIp);
        $range = new IPRange($start, $end);

        // When
        $result = (string) $range;

        // Then
        $this->assertSame($expectedString, $result);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function dataProviderForToString(): array
    {
        return [
            'single IP' => ['10.0.0.1', '10.0.0.1', '10.0.0.1 - 10.0.0.1'],
            'small range' => ['192.168.1.0', '192.168.1.255', '192.168.1.0 - 192.168.1.255'],
            'large range' => ['10.0.0.0', '10.255.255.255', '10.0.0.0 - 10.255.255.255'],
            'full range' => ['0.0.0.0', '255.255.255.255', '0.0.0.0 - 255.255.255.255'],
        ];
    }

    #[Test]
    public function testCountableInterfaceWorks(): void
    {
        // Given
        $start = new IPAddress('192.168.1.0');
        $end = new IPAddress('192.168.1.9');
        $range = new IPRange($start, $end);

        // When
        $result = \count($range);

        // Then
        $this->assertSame(10, $result);
    }

    #[Test]
    public function testIteratorAggregateInterfaceWorks(): void
    {
        // Given
        $start = new IPAddress('10.0.0.0');
        $end = new IPAddress('10.0.0.2');
        $range = new IPRange($start, $end);

        // When
        $iterator = $range->getIterator();

        // Then
        $this->assertInstanceOf(\Generator::class, $iterator);
        $ips = \iterator_to_array($iterator, false);
        $this->assertCount(3, $ips);
    }

    #[Test]
    #[DataProvider('dataProviderForJsonEncode')]
    public function testJsonEncodeProducesStartAndEnd(string $start, string $end, string $expectedJson): void
    {
        // Given
        $range = new IPRange(new IPAddress($start), new IPAddress($end));

        // When
        $result = \json_encode($range, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);

        // Then
        $this->assertSame($expectedJson, $result);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function dataProviderForJsonEncode(): array
    {
        return [
            '/24' => ['192.168.1.0', '192.168.1.255', '{"start":"192.168.1.0","end":"192.168.1.255"}'],
            'RFC 3021 /31' => ['10.0.0.0', '10.0.0.1', '{"start":"10.0.0.0","end":"10.0.0.1"}'],
            'Single host /32' => ['10.0.0.5', '10.0.0.5', '{"start":"10.0.0.5","end":"10.0.0.5"}'],
            'entire IPv4 space /0' => ['0.0.0.0', '255.255.255.255', '{"start":"0.0.0.0","end":"255.255.255.255"}'],
        ];
    }
}
