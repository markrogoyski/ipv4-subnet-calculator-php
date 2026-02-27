<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\IPAddress;
use IPv4\IPRange;
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
}
