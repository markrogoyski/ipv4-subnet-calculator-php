<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Utilization Statistics feature.
 *
 * These methods help with capacity planning and choosing optimal subnet sizes
 * by providing statistics about subnet utilization efficiency.
 */
class SubnetUtilizationTest extends TestCase
{
    /* ******************************* *
     * getUsableHostPercentage() Tests
     * ******************************* */

    #[Test]
    #[DataProvider('dataProviderForUsableHostPercentage')]
    public function testGetUsableHostPercentage(
        string $ip,
        int $networkSize,
        float $expectedPercentage
    ): void {
        // Given
        $subnet = new Subnet($ip, $networkSize);

        // When
        $percentage = $subnet->usableHostPercentage();

        // Then
        $this->assertEqualsWithDelta($expectedPercentage, $percentage, 0.01);
    }

    public static function dataProviderForUsableHostPercentage(): array
    {
        return [
            '/24 - 254 usable of 256 total' => [
                '192.168.1.0',
                24,
                99.22, // (254/256) * 100
            ],
            '/25 - 126 usable of 128 total' => [
                '192.168.1.0',
                25,
                98.44, // (126/128) * 100
            ],
            '/30 - 2 usable of 4 total (point-to-point)' => [
                '192.168.1.0',
                30,
                50.00, // (2/4) * 100
            ],
            '/31 - 2 usable of 2 total (RFC 3021)' => [
                '10.0.0.0',
                31,
                100.00, // (2/2) * 100 - no waste
            ],
            '/32 - 1 usable of 1 total (single host)' => [
                '10.0.0.1',
                32,
                100.00, // (1/1) * 100 - no waste
            ],
            '/16 - 65534 usable of 65536 total' => [
                '172.16.0.0',
                16,
                99.997, // (65534/65536) * 100
            ],
            '/8 - 16777214 usable of 16777216 total' => [
                '10.0.0.0',
                8,
                99.99999, // (16777214/16777216) * 100
            ],
            '/29 - 6 usable of 8 total' => [
                '192.168.1.0',
                29,
                75.00, // (6/8) * 100
            ],
            '/28 - 14 usable of 16 total' => [
                '192.168.1.0',
                28,
                87.50, // (14/16) * 100
            ],
        ];
    }

    /* ******************************* *
     * getUnusableAddressCount() Tests
     * ******************************* */

    #[Test]
    #[DataProvider('dataProviderForUnusableAddressCount')]
    public function testGetUnusableAddressCount(
        string $ip,
        int $networkSize,
        int $expectedUnusable
    ): void {
        // Given
        $subnet = new Subnet($ip, $networkSize);

        // When
        $unusable = $subnet->unusableAddressCount();

        // Then
        $this->assertSame($expectedUnusable, $unusable);
    }

    public static function dataProviderForUnusableAddressCount(): array
    {
        return [
            '/24 - network + broadcast = 2 unusable' => [
                '192.168.1.0',
                24,
                2,
            ],
            '/25 - network + broadcast = 2 unusable' => [
                '192.168.1.0',
                25,
                2,
            ],
            '/30 - network + broadcast = 2 unusable' => [
                '192.168.1.0',
                30,
                2,
            ],
            '/31 - RFC 3021, no unusable addresses' => [
                '10.0.0.0',
                31,
                0,
            ],
            '/32 - single host, no unusable addresses' => [
                '10.0.0.1',
                32,
                0,
            ],
            '/16 - network + broadcast = 2 unusable' => [
                '172.16.0.0',
                16,
                2,
            ],
            '/8 - network + broadcast = 2 unusable' => [
                '10.0.0.0',
                8,
                2,
            ],
        ];
    }

    /* ********************************* *
     * getUtilizationForHosts() Tests
     * ********************************* */

    #[Test]
    #[DataProvider('dataProviderForUtilizationForHosts')]
    public function testGetUtilizationForHosts(
        string $ip,
        int $networkSize,
        int $requiredHosts,
        float $expectedUtilization
    ): void {
        // Given
        $subnet = new Subnet($ip, $networkSize);

        // When
        $utilization = $subnet->utilizationFor($requiredHosts);

        // Then
        $this->assertEqualsWithDelta($expectedUtilization, $utilization, 0.01);
    }

    public static function dataProviderForUtilizationForHosts(): array
    {
        return [
            '/24 with 200 hosts needed - good fit' => [
                '192.168.1.0',
                24,
                200,
                78.74, // (200/254) * 100
            ],
            '/24 with 254 hosts needed - perfect fit' => [
                '192.168.1.0',
                24,
                254,
                100.00, // (254/254) * 100
            ],
            '/24 with 50 hosts needed - oversized' => [
                '192.168.1.0',
                24,
                50,
                19.69, // (50/254) * 100
            ],
            '/24 with 300 hosts needed - insufficient (>100%)' => [
                '192.168.1.0',
                24,
                300,
                118.11, // (300/254) * 100
            ],
            '/31 with 2 hosts needed - perfect for P2P' => [
                '10.0.0.0',
                31,
                2,
                100.00, // (2/2) * 100
            ],
            '/32 with 1 host needed - perfect fit' => [
                '10.0.0.1',
                32,
                1,
                100.00, // (1/1) * 100
            ],
            '/30 with 2 hosts needed - perfect fit' => [
                '192.168.1.0',
                30,
                2,
                100.00, // (2/2) * 100
            ],
            '/29 with 5 hosts needed - good fit' => [
                '192.168.1.0',
                29,
                5,
                83.33, // (5/6) * 100
            ],
            '/25 with 100 hosts needed - good fit' => [
                '192.168.1.0',
                25,
                100,
                79.37, // (100/126) * 100
            ],
            '/24 with 0 hosts needed - empty' => [
                '192.168.1.0',
                24,
                0,
                0.00,
            ],
        ];
    }

    /* ******************************* *
     * getWastedAddresses() Tests
     * ******************************* */

    #[Test]
    #[DataProvider('dataProviderForWastedAddresses')]
    public function testGetWastedAddresses(
        string $ip,
        int $networkSize,
        int $requiredHosts,
        int $expectedWasted
    ): void {
        // Given
        $subnet = new Subnet($ip, $networkSize);

        // When
        $wasted = $subnet->wastedAddressesFor($requiredHosts);

        // Then
        $this->assertSame($expectedWasted, $wasted);
    }

    public static function dataProviderForWastedAddresses(): array
    {
        return [
            '/24 with 200 hosts needed - 54 wasted' => [
                '192.168.1.0',
                24,
                200,
                54, // 254 - 200 = 54 unused
            ],
            '/24 with 254 hosts needed - no waste' => [
                '192.168.1.0',
                24,
                254,
                0, // 254 - 254 = 0 unused
            ],
            '/24 with 50 hosts needed - 204 wasted' => [
                '192.168.1.0',
                24,
                50,
                204, // 254 - 50 = 204 unused
            ],
            '/24 with 300 hosts needed - insufficient (-46)' => [
                '192.168.1.0',
                24,
                300,
                -46, // 254 - 300 = -46 (negative = insufficient)
            ],
            '/31 with 2 hosts needed - no waste' => [
                '10.0.0.0',
                31,
                2,
                0, // 2 - 2 = 0
            ],
            '/32 with 1 host needed - no waste' => [
                '10.0.0.1',
                32,
                1,
                0, // 1 - 1 = 0
            ],
            '/30 with 1 host needed - 1 wasted' => [
                '192.168.1.0',
                30,
                1,
                1, // 2 - 1 = 1
            ],
            '/29 with 3 hosts needed - 3 wasted' => [
                '192.168.1.0',
                29,
                3,
                3, // 6 - 3 = 3
            ],
            '/25 with 126 hosts needed - no waste' => [
                '192.168.1.0',
                25,
                126,
                0, // 126 - 126 = 0
            ],
            '/16 with 10000 hosts needed - 55534 wasted' => [
                '172.16.0.0',
                16,
                10000,
                55534, // 65534 - 10000 = 55534
            ],
        ];
    }

    /* ******************************* *
     * Edge Cases
     * ******************************* */

    #[Test]
    public function testGetUtilizationForHostsWithZeroHosts(): void
    {
        // Given
        $subnet = new Subnet('192.168.1.0', 24);

        // When
        $utilization = $subnet->utilizationFor(0);

        // Then
        $this->assertSame(0.0, $utilization);
    }

    #[Test]
    public function testGetUtilizationForHostsRejectsNegativeValue(): void
    {
        // Given
        $subnet = new Subnet('192.168.1.0', 24);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Required hosts cannot be negative');

        // When
        $subnet->utilizationFor(-1);
    }

    #[Test]
    public function testGetWastedAddressesWithZeroHosts(): void
    {
        // Given
        $subnet = new Subnet('192.168.1.0', 24);

        // When
        $wasted = $subnet->wastedAddressesFor(0);

        // Then
        $this->assertSame(254, $wasted);
    }

    #[Test]
    public function testGetWastedAddressesRejectsNegativeValue(): void
    {
        // Given
        $subnet = new Subnet('192.168.1.0', 24);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Required hosts cannot be negative');

        // When
        $subnet->wastedAddressesFor(-5);
    }

    #[Test]
    public function testUtilizationConsistencyWithHostCount(): void
    {
        // Given
        $subnet = new Subnet('192.168.1.0', 24);
        $usableHosts = $subnet->hostCount();

        // When
        $utilization = $subnet->utilizationFor($usableHosts);

        // Then - 100% utilization when required equals available
        $this->assertEqualsWithDelta(100.0, $utilization, 0.01);
    }

    #[Test]
    public function testWastedPlusRequiredEqualsUsable(): void
    {
        // Given
        $subnet = new Subnet('192.168.1.0', 24);
        $requiredHosts = 150;

        // When
        $wasted = $subnet->wastedAddressesFor($requiredHosts);
        $usableHosts = $subnet->hostCount();

        // Then - wasted + required should equal usable (when required <= usable)
        $this->assertSame($usableHosts, $wasted + $requiredHosts);
    }
}
