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
        $actualAddresses = \array_merge(...\array_map(
            static fn (Subnet $subnet): array => \array_map(
                static fn (IPAddress $ip): int => $ip->asInteger(),
                \iterator_to_array($subnet->addressRange(), false)
            ),
            $subnets
        ));
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

            $parentSize = 2 * $subnet->addressCount();
            $this->assertFalse(
                $previous !== null
                    && $previous->networkSize() === $subnet->networkSize()
                    && \intdiv($previous->networkAddress()->asInteger(), $parentSize)
                        === \intdiv($subnet->networkAddress()->asInteger(), $parentSize),
                'No adjacent output blocks may be mergeable siblings.'
            );

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

    #[Test]
    #[DataProvider('dataProviderForDottedQuadStringRanges')]
    public function testConstructorAcceptsDottedQuadStrings(string $start, string $end, int $expectedCount): void
    {
        // Given dotted-quad $start and $end strings

        // When
        $range = new IPRange($start, $end);

        // Then
        $this->assertSame(
            [$start, $end, $expectedCount],
            [$range->start()->asQuads(), $range->end()->asQuads(), $range->count()]
        );
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function dataProviderForDottedQuadStringRanges(): array
    {
        return [
            'DHCP pool'              => ['192.168.10.100', '192.168.10.199', 100],
            'RFC 3021 /31'           => ['192.0.2.6', '192.0.2.7', 2],
            'single host /32'        => ['192.0.2.7', '192.0.2.7', 1],
            'entire IPv4 space'      => ['0.0.0.0', '255.255.255.255', 4294967296],
            'start of address space' => ['0.0.0.0', '0.0.0.3', 4],
            'end of address space'   => ['255.255.255.252', '255.255.255.255', 4],
        ];
    }

    #[Test]
    public function testConstructorAcceptsStringStartAndIPAddressEnd(): void
    {
        // Given
        $end = new IPAddress('10.0.0.10');

        // When
        $range = new IPRange('10.0.0.1', $end);

        // Then
        $this->assertSame('10.0.0.1', $range->start()->asQuads());
        $this->assertSame($end, $range->end());
    }

    #[Test]
    public function testConstructorAcceptsIPAddressStartAndStringEnd(): void
    {
        // Given
        $start = new IPAddress('10.0.0.1');

        // When
        $range = new IPRange($start, '10.0.0.10');

        // Then
        $this->assertSame($start, $range->start());
        $this->assertSame('10.0.0.10', $range->end()->asQuads());
    }

    #[Test]
    #[DataProvider('dataProviderForInvalidAddressStrings')]
    public function testConstructorRejectsInvalidAddressStrings(string $start, string $end, string $expectedMessage): void
    {
        // Given a $start or $end string that is not a valid IPv4 address

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote($expectedMessage, '/') . '\z/');

        // When
        new IPRange($start, $end);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function dataProviderForInvalidAddressStrings(): array
    {
        return [
            'start octet out of range' => ['256.0.0.1', '10.0.0.10', "Invalid IPv4 address: '256.0.0.1'"],
            'start not an address'     => ['not-an-ip', '10.0.0.10', "Invalid IPv4 address: 'not-an-ip'"],
            'start empty'              => ['', '10.0.0.10', "Invalid IPv4 address: ''"],
            'start truncated'          => ['10.0.0', '10.0.0.10', "Invalid IPv4 address: '10.0.0'"],
            'end octet out of range'   => ['10.0.0.1', '10.0.0.256', "Invalid IPv4 address: '10.0.0.256'"],
            'both invalid'             => ['not-an-ip', '10.0.0.256', "Invalid IPv4 address: 'not-an-ip'"],
        ];
    }

    #[Test]
    public function testConstructorRejectsReversedStringRange(): void
    {
        // Given
        $start = '192.168.1.100';
        $end = '192.168.1.50';

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '/\A' . \preg_quote("Start IP '192.168.1.100' is greater than end IP '192.168.1.50'", '/') . '\z/'
        );

        // When
        new IPRange($start, $end);
    }

    #[Test]
    public function testConstructorAcceptsNamedStringArguments(): void
    {
        // Given
        $start = '10.0.0.1';
        $end = '10.0.0.10';

        // When
        $range = new IPRange(end: $end, start: $start);

        // Then
        $this->assertSame('10.0.0.1 - 10.0.0.10', (string) $range);
    }

    #[Test]
    public function testConstructorStoresSameIPAddressInstanceForSingleAddressRange(): void
    {
        // Given
        $ip = new IPAddress('192.0.2.7');

        // When
        $range = new IPRange($ip, $ip);

        // Then
        $this->assertSame($ip, $range->start());
        $this->assertSame($ip, $range->end());
        $this->assertSame(1, $range->count());
    }

    #[Test]
    public function testConstructorAcceptsNamedIPAddressArguments(): void
    {
        // Given
        $start = new IPAddress('10.0.0.1');
        $end = new IPAddress('10.0.0.10');

        // When
        $inOrder = new IPRange(start: $start, end: $end);
        $reversed = new IPRange(end: $end, start: $start);

        // Then
        $this->assertSame(
            ['10.0.0.1 - 10.0.0.10', '10.0.0.1 - 10.0.0.10'],
            [(string) $inOrder, (string) $reversed]
        );
        $this->assertSame($start, $reversed->start());
        $this->assertSame($end, $reversed->end());
    }

    #[Test]
    public function testContainsAcceptsNamedIpArgument(): void
    {
        // Given
        $range = new IPRange(new IPAddress('10.0.0.1'), new IPAddress('10.0.0.10'));

        // When
        $results = [$range->contains(ip: '10.0.0.1'), $range->contains(ip: new IPAddress('10.0.0.11'))];

        // Then
        $this->assertSame([true, false], $results);
    }

    #[Test]
    public function testNativeSerializationKeepsPropertyLayout(): void
    {
        // Given
        $range = new IPRange(new IPAddress('192.0.2.0'), new IPAddress('192.0.2.255'));

        // When
        $keys = \array_keys(\get_mangled_object_vars($range));
        $restored = \unserialize(\serialize($range));

        // Then
        $this->assertSame(
            ["\0IPv4\IPRange\0startInt", "\0IPv4\IPRange\0endInt", "\0IPv4\IPRange\0start", "\0IPv4\IPRange\0end"],
            $keys
        );
        $this->assertInstanceOf(IPRange::class, $restored);
        $this->assertTrue($restored->equals($range));
    }

    #[Test]
    #[DataProvider('dataProviderForContainsRange')]
    public function testContainsRangeChecksWhetherOtherRangeIsInside(
        string $aStart,
        string $aEnd,
        string $bStart,
        string $bEnd,
        bool $aContainsB,
        bool $bContainsA
    ): void {
        // Given
        $a = new IPRange($aStart, $aEnd);
        $b = new IPRange($bStart, $bEnd);

        // When
        $results = [$a->containsRange($b), $b->containsRange($a)];

        // Then
        $this->assertSame([$aContainsB, $bContainsA], $results);
    }

    /**
     * @return array<string, array{string, string, string, string, bool, bool}>
     */
    public static function dataProviderForContainsRange(): array
    {
        return [
            'identical'                      => ['192.0.2.0',       '192.0.2.255',     '192.0.2.0',       '192.0.2.255',     true, true],
            'nested'                         => ['192.0.2.0',       '192.0.2.255',     '192.0.2.64',      '192.0.2.127',     true, false],
            'shared start'                   => ['192.0.2.0',       '192.0.2.255',     '192.0.2.0',       '192.0.2.15',      true, false],
            'shared end'                     => ['192.0.2.0',       '192.0.2.255',     '192.0.2.240',     '192.0.2.255',     true, false],
            'partial overlap on the left'    => ['192.0.2.4',       '192.0.2.11',      '192.0.2.0',       '192.0.2.7',       false, false],
            'partial overlap on the right'   => ['192.0.2.0',       '192.0.2.7',       '192.0.2.4',       '192.0.2.11',      false, false],
            'overlap of one shared endpoint' => ['192.0.2.0',       '192.0.2.8',       '192.0.2.8',       '192.0.2.15',      false, false],
            'touching'                       => ['192.0.2.0',       '192.0.2.7',       '192.0.2.8',       '192.0.2.15',      false, false],
            'touching across an octet'       => ['192.0.2.128',     '192.0.2.255',     '192.0.3.0',       '192.0.3.127',     false, false],
            'one-address gap'                => ['192.0.2.0',       '192.0.2.7',       '192.0.2.9',       '192.0.2.15',      false, false],
            'wide gap'                       => ['10.0.0.0',        '10.0.0.255',      '192.0.2.0',       '192.0.2.255',     false, false],
            'single address inside'          => ['192.0.2.0',       '192.0.2.255',     '192.0.2.100',     '192.0.2.100',     true, false],
            'single address at start'        => ['192.0.2.0',       '192.0.2.255',     '192.0.2.0',       '192.0.2.0',       true, false],
            'single address at end'          => ['192.0.2.0',       '192.0.2.255',     '192.0.2.255',     '192.0.2.255',     true, false],
            'single address just before'     => ['192.0.2.10',      '192.0.2.20',      '192.0.2.9',       '192.0.2.9',       false, false],
            'single address just after'      => ['192.0.2.10',      '192.0.2.20',      '192.0.2.21',      '192.0.2.21',      false, false],
            'single address vs itself'       => ['198.51.100.1',    '198.51.100.1',    '198.51.100.1',    '198.51.100.1',    true, true],
            '/31 vs member /32'              => ['192.0.2.6',       '192.0.2.7',       '192.0.2.7',       '192.0.2.7',       true, false],
            '/31 vs next /31'                => ['192.0.2.6',       '192.0.2.7',       '192.0.2.8',       '192.0.2.9',       false, false],
            '/32 vs itself'                  => ['192.0.2.7',       '192.0.2.7',       '192.0.2.7',       '192.0.2.7',       true, true],
            'consecutive /32s'               => ['192.0.2.7',       '192.0.2.7',       '192.0.2.8',       '192.0.2.8',       false, false],
            'full range vs inner range'      => ['0.0.0.0',         '255.255.255.255', '192.0.2.0',       '192.0.2.255',     true, false],
            'full range vs full range'       => ['0.0.0.0',         '255.255.255.255', '0.0.0.0',         '255.255.255.255', true, true],
            'full range vs 0.0.0.0'          => ['0.0.0.0',         '255.255.255.255', '0.0.0.0',         '0.0.0.0',         true, false],
            'full range vs 255.255.255.255'  => ['0.0.0.0',         '255.255.255.255', '255.255.255.255', '255.255.255.255', true, false],
            'overlapping ranges at 0.0.0.0'  => ['0.0.0.0',         '0.0.0.7',         '0.0.0.4',         '0.0.0.15',        false, false],
            'touching ranges at 0.0.0.0'     => ['0.0.0.0',         '0.0.0.3',         '0.0.0.4',         '0.0.0.7',         false, false],
            'overlapping ranges at top'      => ['255.255.255.240', '255.255.255.255', '255.255.255.252', '255.255.255.255', true, false],
            'touching ranges at top'         => ['255.255.255.248', '255.255.255.251', '255.255.255.252', '255.255.255.255', false, false],
            '0.0.0.0 vs 255.255.255.255'     => ['0.0.0.0',         '0.0.0.0',         '255.255.255.255', '255.255.255.255', false, false],
            'first /24 vs last /24'          => ['0.0.0.0',         '0.0.0.255',       '255.255.255.0',   '255.255.255.255', false, false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForOverlaps')]
    public function testOverlapsChecksForSharedAddressesInEitherOrder(
        string $aStart,
        string $aEnd,
        string $bStart,
        string $bEnd,
        bool $expected
    ): void {
        // Given
        $a = new IPRange($aStart, $aEnd);
        $b = new IPRange($bStart, $bEnd);

        // When
        $results = [$a->overlaps($b), $b->overlaps($a)];

        // Then
        $this->assertSame([$expected, $expected], $results);
    }

    /**
     * @return array<string, array{string, string, string, string, bool}>
     */
    public static function dataProviderForOverlaps(): array
    {
        return [
            'identical'                      => ['192.0.2.0',       '192.0.2.255',     '192.0.2.0',       '192.0.2.255',     true],
            'nested'                         => ['192.0.2.0',       '192.0.2.255',     '192.0.2.64',      '192.0.2.127',     true],
            'shared start'                   => ['192.0.2.0',       '192.0.2.255',     '192.0.2.0',       '192.0.2.15',      true],
            'shared end'                     => ['192.0.2.0',       '192.0.2.255',     '192.0.2.240',     '192.0.2.255',     true],
            'partial overlap on the left'    => ['192.0.2.4',       '192.0.2.11',      '192.0.2.0',       '192.0.2.7',       true],
            'partial overlap on the right'   => ['192.0.2.0',       '192.0.2.7',       '192.0.2.4',       '192.0.2.11',      true],
            'overlap of one shared endpoint' => ['192.0.2.0',       '192.0.2.8',       '192.0.2.8',       '192.0.2.15',      true],
            'touching'                       => ['192.0.2.0',       '192.0.2.7',       '192.0.2.8',       '192.0.2.15',      false],
            'touching across an octet'       => ['192.0.2.128',     '192.0.2.255',     '192.0.3.0',       '192.0.3.127',     false],
            'one-address gap'                => ['192.0.2.0',       '192.0.2.7',       '192.0.2.9',       '192.0.2.15',      false],
            'wide gap'                       => ['10.0.0.0',        '10.0.0.255',      '192.0.2.0',       '192.0.2.255',     false],
            'single address inside'          => ['192.0.2.0',       '192.0.2.255',     '192.0.2.100',     '192.0.2.100',     true],
            'single address at start'        => ['192.0.2.0',       '192.0.2.255',     '192.0.2.0',       '192.0.2.0',       true],
            'single address at end'          => ['192.0.2.0',       '192.0.2.255',     '192.0.2.255',     '192.0.2.255',     true],
            'single address just before'     => ['192.0.2.10',      '192.0.2.20',      '192.0.2.9',       '192.0.2.9',       false],
            'single address just after'      => ['192.0.2.10',      '192.0.2.20',      '192.0.2.21',      '192.0.2.21',      false],
            'single address vs itself'       => ['198.51.100.1',    '198.51.100.1',    '198.51.100.1',    '198.51.100.1',    true],
            '/31 vs member /32'              => ['192.0.2.6',       '192.0.2.7',       '192.0.2.7',       '192.0.2.7',       true],
            '/31 vs next /31'                => ['192.0.2.6',       '192.0.2.7',       '192.0.2.8',       '192.0.2.9',       false],
            '/32 vs itself'                  => ['192.0.2.7',       '192.0.2.7',       '192.0.2.7',       '192.0.2.7',       true],
            'consecutive /32s'               => ['192.0.2.7',       '192.0.2.7',       '192.0.2.8',       '192.0.2.8',       false],
            'full range vs inner range'      => ['0.0.0.0',         '255.255.255.255', '192.0.2.0',       '192.0.2.255',     true],
            'full range vs full range'       => ['0.0.0.0',         '255.255.255.255', '0.0.0.0',         '255.255.255.255', true],
            'full range vs 0.0.0.0'          => ['0.0.0.0',         '255.255.255.255', '0.0.0.0',         '0.0.0.0',         true],
            'full range vs 255.255.255.255'  => ['0.0.0.0',         '255.255.255.255', '255.255.255.255', '255.255.255.255', true],
            'overlapping ranges at 0.0.0.0'  => ['0.0.0.0',         '0.0.0.7',         '0.0.0.4',         '0.0.0.15',        true],
            'touching ranges at 0.0.0.0'     => ['0.0.0.0',         '0.0.0.3',         '0.0.0.4',         '0.0.0.7',         false],
            'overlapping ranges at top'      => ['255.255.255.240', '255.255.255.255', '255.255.255.252', '255.255.255.255', true],
            'touching ranges at top'         => ['255.255.255.248', '255.255.255.251', '255.255.255.252', '255.255.255.255', false],
            '0.0.0.0 vs 255.255.255.255'     => ['0.0.0.0',         '0.0.0.0',         '255.255.255.255', '255.255.255.255', false],
            'first /24 vs last /24'          => ['0.0.0.0',         '0.0.0.255',       '255.255.255.0',   '255.255.255.255', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIntersection')]
    public function testIntersectionReturnsSharedAddressesInEitherOrder(
        string $aStart,
        string $aEnd,
        string $bStart,
        string $bEnd,
        ?string $expected
    ): void {
        // Given
        $a = new IPRange($aStart, $aEnd);
        $b = new IPRange($bStart, $bEnd);

        // When
        $results = [$a->intersection($b)?->__toString(), $b->intersection($a)?->__toString()];

        // Then
        $this->assertSame([$expected, $expected], $results);
    }

    /**
     * @return array<string, array{string, string, string, string, ?string}>
     */
    public static function dataProviderForIntersection(): array
    {
        return [
            'identical'                      => ['192.0.2.0',       '192.0.2.255',     '192.0.2.0',       '192.0.2.255',     '192.0.2.0 - 192.0.2.255'],
            'nested'                         => ['192.0.2.0',       '192.0.2.255',     '192.0.2.64',      '192.0.2.127',     '192.0.2.64 - 192.0.2.127'],
            'shared start'                   => ['192.0.2.0',       '192.0.2.255',     '192.0.2.0',       '192.0.2.15',      '192.0.2.0 - 192.0.2.15'],
            'shared end'                     => ['192.0.2.0',       '192.0.2.255',     '192.0.2.240',     '192.0.2.255',     '192.0.2.240 - 192.0.2.255'],
            'partial overlap on the left'    => ['192.0.2.4',       '192.0.2.11',      '192.0.2.0',       '192.0.2.7',       '192.0.2.4 - 192.0.2.7'],
            'partial overlap on the right'   => ['192.0.2.0',       '192.0.2.7',       '192.0.2.4',       '192.0.2.11',      '192.0.2.4 - 192.0.2.7'],
            'overlap of one shared endpoint' => ['192.0.2.0',       '192.0.2.8',       '192.0.2.8',       '192.0.2.15',      '192.0.2.8 - 192.0.2.8'],
            'touching'                       => ['192.0.2.0',       '192.0.2.7',       '192.0.2.8',       '192.0.2.15',      null],
            'touching across an octet'       => ['192.0.2.128',     '192.0.2.255',     '192.0.3.0',       '192.0.3.127',     null],
            'one-address gap'                => ['192.0.2.0',       '192.0.2.7',       '192.0.2.9',       '192.0.2.15',      null],
            'wide gap'                       => ['10.0.0.0',        '10.0.0.255',      '192.0.2.0',       '192.0.2.255',     null],
            'single address inside'          => ['192.0.2.0',       '192.0.2.255',     '192.0.2.100',     '192.0.2.100',     '192.0.2.100 - 192.0.2.100'],
            'single address at start'        => ['192.0.2.0',       '192.0.2.255',     '192.0.2.0',       '192.0.2.0',       '192.0.2.0 - 192.0.2.0'],
            'single address at end'          => ['192.0.2.0',       '192.0.2.255',     '192.0.2.255',     '192.0.2.255',     '192.0.2.255 - 192.0.2.255'],
            'single address just before'     => ['192.0.2.10',      '192.0.2.20',      '192.0.2.9',       '192.0.2.9',       null],
            'single address just after'      => ['192.0.2.10',      '192.0.2.20',      '192.0.2.21',      '192.0.2.21',      null],
            'single address vs itself'       => ['198.51.100.1',    '198.51.100.1',    '198.51.100.1',    '198.51.100.1',    '198.51.100.1 - 198.51.100.1'],
            '/31 vs member /32'              => ['192.0.2.6',       '192.0.2.7',       '192.0.2.7',       '192.0.2.7',       '192.0.2.7 - 192.0.2.7'],
            '/31 vs next /31'                => ['192.0.2.6',       '192.0.2.7',       '192.0.2.8',       '192.0.2.9',       null],
            '/32 vs itself'                  => ['192.0.2.7',       '192.0.2.7',       '192.0.2.7',       '192.0.2.7',       '192.0.2.7 - 192.0.2.7'],
            'consecutive /32s'               => ['192.0.2.7',       '192.0.2.7',       '192.0.2.8',       '192.0.2.8',       null],
            'full range vs inner range'      => ['0.0.0.0',         '255.255.255.255', '192.0.2.0',       '192.0.2.255',     '192.0.2.0 - 192.0.2.255'],
            'full range vs full range'       => ['0.0.0.0',         '255.255.255.255', '0.0.0.0',         '255.255.255.255', '0.0.0.0 - 255.255.255.255'],
            'full range vs 0.0.0.0'          => ['0.0.0.0',         '255.255.255.255', '0.0.0.0',         '0.0.0.0',         '0.0.0.0 - 0.0.0.0'],
            'full range vs 255.255.255.255'  => ['0.0.0.0',         '255.255.255.255', '255.255.255.255', '255.255.255.255', '255.255.255.255 - 255.255.255.255'],
            'overlapping ranges at 0.0.0.0'  => ['0.0.0.0',         '0.0.0.7',         '0.0.0.4',         '0.0.0.15',        '0.0.0.4 - 0.0.0.7'],
            'touching ranges at 0.0.0.0'     => ['0.0.0.0',         '0.0.0.3',         '0.0.0.4',         '0.0.0.7',         null],
            'overlapping ranges at top'      => ['255.255.255.240', '255.255.255.255', '255.255.255.252', '255.255.255.255', '255.255.255.252 - 255.255.255.255'],
            'touching ranges at top'         => ['255.255.255.248', '255.255.255.251', '255.255.255.252', '255.255.255.255', null],
            '0.0.0.0 vs 255.255.255.255'     => ['0.0.0.0',         '0.0.0.0',         '255.255.255.255', '255.255.255.255', null],
            'first /24 vs last /24'          => ['0.0.0.0',         '0.0.0.255',       '255.255.255.0',   '255.255.255.255', null],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIsAdjacent')]
    public function testIsAdjacentChecksWhetherDisjointRangesTouchInEitherOrder(
        string $aStart,
        string $aEnd,
        string $bStart,
        string $bEnd,
        bool $expected
    ): void {
        // Given
        $a = new IPRange($aStart, $aEnd);
        $b = new IPRange($bStart, $bEnd);

        // When
        $results = [$a->isAdjacent($b), $b->isAdjacent($a)];

        // Then
        $this->assertSame([$expected, $expected], $results);
    }

    /**
     * @return array<string, array{string, string, string, string, bool}>
     */
    public static function dataProviderForIsAdjacent(): array
    {
        return [
            'identical'                      => ['192.0.2.0',       '192.0.2.255',     '192.0.2.0',       '192.0.2.255',     false],
            'nested'                         => ['192.0.2.0',       '192.0.2.255',     '192.0.2.64',      '192.0.2.127',     false],
            'shared start'                   => ['192.0.2.0',       '192.0.2.255',     '192.0.2.0',       '192.0.2.15',      false],
            'shared end'                     => ['192.0.2.0',       '192.0.2.255',     '192.0.2.240',     '192.0.2.255',     false],
            'partial overlap on the left'    => ['192.0.2.4',       '192.0.2.11',      '192.0.2.0',       '192.0.2.7',       false],
            'partial overlap on the right'   => ['192.0.2.0',       '192.0.2.7',       '192.0.2.4',       '192.0.2.11',      false],
            'overlap of one shared endpoint' => ['192.0.2.0',       '192.0.2.8',       '192.0.2.8',       '192.0.2.15',      false],
            'touching'                       => ['192.0.2.0',       '192.0.2.7',       '192.0.2.8',       '192.0.2.15',      true],
            'touching across an octet'       => ['192.0.2.128',     '192.0.2.255',     '192.0.3.0',       '192.0.3.127',     true],
            'one-address gap'                => ['192.0.2.0',       '192.0.2.7',       '192.0.2.9',       '192.0.2.15',      false],
            'wide gap'                       => ['10.0.0.0',        '10.0.0.255',      '192.0.2.0',       '192.0.2.255',     false],
            'single address inside'          => ['192.0.2.0',       '192.0.2.255',     '192.0.2.100',     '192.0.2.100',     false],
            'single address at start'        => ['192.0.2.0',       '192.0.2.255',     '192.0.2.0',       '192.0.2.0',       false],
            'single address at end'          => ['192.0.2.0',       '192.0.2.255',     '192.0.2.255',     '192.0.2.255',     false],
            'single address just before'     => ['192.0.2.10',      '192.0.2.20',      '192.0.2.9',       '192.0.2.9',       true],
            'single address just after'      => ['192.0.2.10',      '192.0.2.20',      '192.0.2.21',      '192.0.2.21',      true],
            'single address vs itself'       => ['198.51.100.1',    '198.51.100.1',    '198.51.100.1',    '198.51.100.1',    false],
            '/31 vs member /32'              => ['192.0.2.6',       '192.0.2.7',       '192.0.2.7',       '192.0.2.7',       false],
            '/31 vs next /31'                => ['192.0.2.6',       '192.0.2.7',       '192.0.2.8',       '192.0.2.9',       true],
            '/32 vs itself'                  => ['192.0.2.7',       '192.0.2.7',       '192.0.2.7',       '192.0.2.7',       false],
            'consecutive /32s'               => ['192.0.2.7',       '192.0.2.7',       '192.0.2.8',       '192.0.2.8',       true],
            'full range vs inner range'      => ['0.0.0.0',         '255.255.255.255', '192.0.2.0',       '192.0.2.255',     false],
            'full range vs full range'       => ['0.0.0.0',         '255.255.255.255', '0.0.0.0',         '255.255.255.255', false],
            'full range vs 0.0.0.0'          => ['0.0.0.0',         '255.255.255.255', '0.0.0.0',         '0.0.0.0',         false],
            'full range vs 255.255.255.255'  => ['0.0.0.0',         '255.255.255.255', '255.255.255.255', '255.255.255.255', false],
            'overlapping ranges at 0.0.0.0'  => ['0.0.0.0',         '0.0.0.7',         '0.0.0.4',         '0.0.0.15',        false],
            'touching ranges at 0.0.0.0'     => ['0.0.0.0',         '0.0.0.3',         '0.0.0.4',         '0.0.0.7',         true],
            'overlapping ranges at top'      => ['255.255.255.240', '255.255.255.255', '255.255.255.252', '255.255.255.255', false],
            'touching ranges at top'         => ['255.255.255.248', '255.255.255.251', '255.255.255.252', '255.255.255.255', true],
            '0.0.0.0 vs 255.255.255.255'     => ['0.0.0.0',         '0.0.0.0',         '255.255.255.255', '255.255.255.255', false],
            'first /24 vs last /24'          => ['0.0.0.0',         '0.0.0.255',       '255.255.255.0',   '255.255.255.255', false],
        ];
    }

    #[Test]
    public function testIntersectionReusesBoundaryAddressInstances(): void
    {
        // Given
        $a = new IPRange('192.0.2.0', '192.0.2.7');
        $b = new IPRange('192.0.2.4', '192.0.2.11');

        // When
        $intersection = $a->intersection($b);

        // Then
        $this->assertNotNull($intersection);
        $this->assertSame($b->start(), $intersection->start());
        $this->assertSame($a->end(), $intersection->end());
    }

    #[Test]
    public function testRelationsAcceptSubnetAddressRanges(): void
    {
        // Given
        $block = Subnet::fromCidr('192.0.2.0/24')->addressRange();
        $lower = Subnet::fromCidr('192.0.2.0/25')->addressRange();
        $upper = Subnet::fromCidr('192.0.2.128/25')->addressRange();
        $p2p = Subnet::fromCidr('192.0.2.6/31');
        $host = Subnet::fromCidr('192.0.2.7/32');

        // When
        $results = [
            $block->containsRange($lower),
            $block->containsRange($upper),
            $lower->isAdjacent($upper),
            $lower->overlaps($upper),
            $p2p->hostRange()->equals($p2p->addressRange()),
            $p2p->hostRange()->containsRange($p2p->addressRange()),
            $host->hostRange()->equals($host->addressRange()),
            $host->hostRange()->containsRange($host->addressRange()),
        ];

        // Then
        $this->assertSame([true, true, true, false, true, true, true, true], $results);
    }

    #[Test]
    public function testIntersectionReportsOverlappingDhcpPools(): void
    {
        // Given
        $engineering = new IPRange('192.168.10.100', '192.168.10.199');
        $sales = new IPRange('192.168.10.150', '192.168.10.250');
        $guest = new IPRange('192.168.11.10', '192.168.11.254');
        $scope = Subnet::fromCidr('192.168.10.0/24')->hostRange();

        // When
        $overlap = $engineering->intersection($sales);

        // Then
        $this->assertNotNull($overlap);
        $this->assertSame(['192.168.10.150 - 192.168.10.199', 50], [(string) $overlap, $overlap->count()]);
        $this->assertSame([null, null], [$engineering->intersection($guest), $sales->intersection($guest)]);
        $this->assertSame([true, false], [$scope->containsRange($sales), $scope->containsRange($guest)]);
    }
}
