<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Exclude/Difference Operations feature.
 *
 * These tests verify the ability to calculate what remains of a subnet
 * after removing another subnet, useful for carving out reserved ranges.
 */
class SubnetExcludeTest extends TestCase
{
    #[Test]
    #[DataProvider('dataProviderForExcludeFirstHalf')]
    public function excludeFirstHalfReturnsSecondHalf(
        string $baseIp,
        int $basePrefix,
        string $excludeIp,
        int $excludePrefix,
        array $expectedResults
    ): void {
        // Given
        $base = new Subnet($baseIp, $basePrefix);
        $exclude = new Subnet($excludeIp, $excludePrefix);

        // When
        $result = $base->exclude($exclude);

        // Then
        $this->assertCount(\count($expectedResults), $result);
        foreach ($expectedResults as $index => $expected) {
            $this->assertSame($expected['network'], $result[$index]->networkPortion()->asQuads());
            $this->assertSame($expected['prefix'], $result[$index]->networkSize());
        }
    }

    public static function dataProviderForExcludeFirstHalf(): array
    {
        return [
            'Remove first half of /24' => [
                '192.168.0.0', 24,
                '192.168.0.0', 25,
                [['network' => '192.168.0.128', 'prefix' => 25]],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForExcludeSecondHalf')]
    public function excludeSecondHalfReturnsFirstHalf(
        string $baseIp,
        int $basePrefix,
        string $excludeIp,
        int $excludePrefix,
        array $expectedResults
    ): void {
        // Given
        $base = new Subnet($baseIp, $basePrefix);
        $exclude = new Subnet($excludeIp, $excludePrefix);

        // When
        $result = $base->exclude($exclude);

        // Then
        $this->assertCount(\count($expectedResults), $result);
        foreach ($expectedResults as $index => $expected) {
            $this->assertSame($expected['network'], $result[$index]->networkPortion()->asQuads());
            $this->assertSame($expected['prefix'], $result[$index]->networkSize());
        }
    }

    public static function dataProviderForExcludeSecondHalf(): array
    {
        return [
            'Remove second half of /24' => [
                '192.168.0.0', 24,
                '192.168.0.128', 25,
                [['network' => '192.168.0.0', 'prefix' => 25]],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForExcludeFirstQuarter')]
    public function excludeFirstQuarterReturnsRemainder(
        string $baseIp,
        int $basePrefix,
        string $excludeIp,
        int $excludePrefix,
        array $expectedResults
    ): void {
        // Given
        $base = new Subnet($baseIp, $basePrefix);
        $exclude = new Subnet($excludeIp, $excludePrefix);

        // When
        $result = $base->exclude($exclude);

        // Then
        $this->assertCount(\count($expectedResults), $result);
        foreach ($expectedResults as $index => $expected) {
            $this->assertSame($expected['network'], $result[$index]->networkPortion()->asQuads());
            $this->assertSame($expected['prefix'], $result[$index]->networkSize());
        }
    }

    public static function dataProviderForExcludeFirstQuarter(): array
    {
        return [
            'Remove first quarter of /24' => [
                '10.0.0.0', 24,
                '10.0.0.0', 26,
                [
                    ['network' => '10.0.0.64', 'prefix' => 26],
                    ['network' => '10.0.0.128', 'prefix' => 25],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForFullExclusion')]
    public function excludeEntireSubnetReturnsEmptyArray(
        string $baseIp,
        int $basePrefix,
        string $excludeIp,
        int $excludePrefix
    ): void {
        // Given
        $base = new Subnet($baseIp, $basePrefix);
        $exclude = new Subnet($excludeIp, $excludePrefix);

        // When
        $result = $base->exclude($exclude);

        // Then
        $this->assertCount(0, $result);
    }

    public static function dataProviderForFullExclusion(): array
    {
        return [
            'Exclude identical /24' => [
                '192.168.0.0', 24,
                '192.168.0.0', 24,
            ],
            'Exclude larger subnet that contains base' => [
                '192.168.0.0', 25,
                '192.168.0.0', 24,
            ],
            'Exclude identical /32' => [
                '10.0.0.1', 32,
                '10.0.0.1', 32,
            ],
            'Exclude /31 containing the /32' => [
                '10.0.0.0', 32,
                '10.0.0.0', 31,
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForNoOverlap')]
    public function excludeNonOverlappingReturnsOriginal(
        string $baseIp,
        int $basePrefix,
        string $excludeIp,
        int $excludePrefix
    ): void {
        // Given
        $base = new Subnet($baseIp, $basePrefix);
        $exclude = new Subnet($excludeIp, $excludePrefix);

        // When
        $result = $base->exclude($exclude);

        // Then
        $this->assertCount(1, $result);
        $this->assertSame($base->networkPortion()->asQuads(), $result[0]->networkPortion()->asQuads());
        $this->assertSame($base->networkSize(), $result[0]->networkSize());
    }

    public static function dataProviderForNoOverlap(): array
    {
        return [
            'Adjacent /24 subnets' => [
                '192.168.0.0', 24,
                '192.168.1.0', 24,
            ],
            'Completely different networks' => [
                '10.0.0.0', 8,
                '172.16.0.0', 12,
            ],
            'Adjacent /31 subnets' => [
                '10.0.0.0', 31,
                '10.0.0.2', 31,
            ],
            'Adjacent /32 hosts' => [
                '10.0.0.1', 32,
                '10.0.0.2', 32,
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForSingleHostExclusion')]
    public function excludeSingleHostFromSubnet(
        string $baseIp,
        int $basePrefix,
        string $excludeIp,
        int $excludePrefix,
        int $expectedResultCount
    ): void {
        // Given
        $base = new Subnet($baseIp, $basePrefix);
        $exclude = new Subnet($excludeIp, $excludePrefix);

        // When
        $result = $base->exclude($exclude);

        // Then
        $this->assertCount($expectedResultCount, $result);

        // Verify the excluded host is not in any of the result subnets
        foreach ($result as $subnet) {
            $this->assertFalse(
                $subnet->containsIP($excludeIp),
                "Excluded IP $excludeIp should not be in result subnet {$subnet->networkPortion()->asQuads()}/{$subnet->networkSize()}"
            );
        }

        // Verify all result subnets are within the original base subnet
        foreach ($result as $subnet) {
            $this->assertTrue(
                $base->contains($subnet),
                "Result subnet should be contained in base subnet"
            );
        }
    }

    public static function dataProviderForSingleHostExclusion(): array
    {
        return [
            'Exclude single host from /24' => [
                '192.168.0.0', 24,
                '192.168.0.100', 32,
                8, // /24 -> /25 -> /26 -> /27 -> /28 -> /29 -> /30 -> /31 -> /32 = 8 subnets
            ],
            'Exclude first host from /30' => [
                '10.0.0.0', 30,
                '10.0.0.0', 32,
                2, // /30 - /32 = /31 + /32
            ],
            'Exclude last host from /30' => [
                '10.0.0.0', 30,
                '10.0.0.3', 32,
                2, // /30 - /32 = /31 + /32
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForRfc3021Exclusions')]
    public function excludeFromRfc3021Networks(
        string $baseIp,
        int $basePrefix,
        string $excludeIp,
        int $excludePrefix,
        array $expectedResults
    ): void {
        // Given
        $base = new Subnet($baseIp, $basePrefix);
        $exclude = new Subnet($excludeIp, $excludePrefix);

        // When
        $result = $base->exclude($exclude);

        // Then
        $this->assertCount(\count($expectedResults), $result);
        foreach ($expectedResults as $index => $expected) {
            $this->assertSame($expected['network'], $result[$index]->networkPortion()->asQuads());
            $this->assertSame($expected['prefix'], $result[$index]->networkSize());
        }
    }

    public static function dataProviderForRfc3021Exclusions(): array
    {
        return [
            '/31 minus first /32' => [
                '10.0.0.0', 31,
                '10.0.0.0', 32,
                [['network' => '10.0.0.1', 'prefix' => 32]],
            ],
            '/31 minus second /32' => [
                '10.0.0.0', 31,
                '10.0.0.1', 32,
                [['network' => '10.0.0.0', 'prefix' => 32]],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForExcludeMiddleSection')]
    public function excludeMiddleSectionReturnsBothEnds(
        string $baseIp,
        int $basePrefix,
        string $excludeIp,
        int $excludePrefix,
        int $expectedMinResultCount
    ): void {
        // Given
        $base = new Subnet($baseIp, $basePrefix);
        $exclude = new Subnet($excludeIp, $excludePrefix);

        // When
        $result = $base->exclude($exclude);

        // Then
        $this->assertGreaterThanOrEqual($expectedMinResultCount, \count($result));

        // Verify the excluded subnet is not overlapping with any result
        foreach ($result as $subnet) {
            $this->assertFalse(
                $subnet->overlaps($exclude),
                "Result subnet should not overlap with excluded subnet"
            );
        }
    }

    public static function dataProviderForExcludeMiddleSection(): array
    {
        return [
            'Exclude middle /26 from /24' => [
                '10.0.0.0', 24,
                '10.0.0.64', 26,
                2, // At least: 10.0.0.0/26 and 10.0.0.128/25
            ],
            'Exclude second quarter from /24' => [
                '10.0.0.0', 24,
                '10.0.0.64', 26,
                2,
            ],
        ];
    }

    #[Test]
    public function excludeAllRemovesMultipleSubnets(): void
    {
        // Given - A /24 network
        $base = new Subnet('192.168.0.0', 24);

        // Two non-overlapping /26 subnets to exclude
        $excludes = [
            new Subnet('192.168.0.0', 26),   // First quarter
            new Subnet('192.168.0.128', 26), // Third quarter
        ];

        // When
        $result = $base->excludeAll($excludes);

        // Then - Should have second quarter and fourth quarter remaining
        $this->assertCount(2, $result);

        // Verify neither excluded subnet is in the result
        foreach ($result as $subnet) {
            foreach ($excludes as $excluded) {
                $this->assertFalse(
                    $subnet->overlaps($excluded),
                    "Result subnet should not overlap with any excluded subnet"
                );
            }
        }

        // Verify the remaining subnets cover the expected ranges
        $remainingNetworks = \array_map(function ($s) {
            return $s->networkPortion()->asQuads() . '/' . $s->networkSize();
        }, $result);

        $this->assertContains('192.168.0.64/26', $remainingNetworks);
        $this->assertContains('192.168.0.192/26', $remainingNetworks);
    }

    #[Test]
    public function excludeAllWithOverlappingExclusions(): void
    {
        // Given - A /24 network
        $base = new Subnet('192.168.0.0', 24);

        // Overlapping exclusions: /25 contains the /26
        $excludes = [
            new Subnet('192.168.0.0', 25),  // First half
            new Subnet('192.168.0.0', 26),  // First quarter (overlaps with above)
        ];

        // When
        $result = $base->excludeAll($excludes);

        // Then - Should have only second half remaining
        $this->assertCount(1, $result);
        $this->assertSame('192.168.0.128', $result[0]->networkPortion()->asQuads());
        $this->assertSame(25, $result[0]->networkSize());
    }

    #[Test]
    public function excludeAllWithEmptyArrayReturnsOriginal(): void
    {
        // Given
        $base = new Subnet('192.168.0.0', 24);

        // When
        $result = $base->excludeAll([]);

        // Then
        $this->assertCount(1, $result);
        $this->assertSame('192.168.0.0', $result[0]->networkPortion()->asQuads());
        $this->assertSame(24, $result[0]->networkSize());
    }

    #[Test]
    public function excludeAllWithFullCoverageReturnsEmpty(): void
    {
        // Given - A /24 network
        $base = new Subnet('192.168.0.0', 24);

        // Exclusions that cover the entire base
        $excludes = [
            new Subnet('192.168.0.0', 25),
            new Subnet('192.168.0.128', 25),
        ];

        // When
        $result = $base->excludeAll($excludes);

        // Then
        $this->assertCount(0, $result);
    }

    #[Test]
    public function excludeAllWithNonOverlappingReturnsOriginal(): void
    {
        // Given
        $base = new Subnet('192.168.0.0', 24);

        // Exclusions that don't overlap with base
        $excludes = [
            new Subnet('192.168.1.0', 24),
            new Subnet('10.0.0.0', 8),
        ];

        // When
        $result = $base->excludeAll($excludes);

        // Then
        $this->assertCount(1, $result);
        $this->assertSame('192.168.0.0', $result[0]->networkPortion()->asQuads());
        $this->assertSame(24, $result[0]->networkSize());
    }

    #[Test]
    #[DataProvider('dataProviderForExcludeReturnsOptimalSubnets')]
    public function excludeReturnsOptimalSubnets(
        string $baseIp,
        int $basePrefix,
        string $excludeIp,
        int $excludePrefix
    ): void {
        // Given
        $base = new Subnet($baseIp, $basePrefix);
        $exclude = new Subnet($excludeIp, $excludePrefix);

        // When
        $result = $base->exclude($exclude);

        // Then - verify results are properly aligned CIDR blocks
        foreach ($result as $subnet) {
            $networkInt = $subnet->networkPortion()->asInteger();
            $size = $subnet->networkSize();
            $blockSize = (int) \pow(2, 32 - $size);

            // Network address should be aligned to the block size
            $this->assertSame(
                0,
                \sprintf('%u', $networkInt) % $blockSize,
                "Subnet {$subnet->networkPortion()->asQuads()}/{$size} is not properly aligned"
            );
        }
    }

    public static function dataProviderForExcludeReturnsOptimalSubnets(): array
    {
        return [
            '/24 minus first /25' => ['192.168.0.0', 24, '192.168.0.0', 25],
            '/24 minus first /26' => ['10.0.0.0', 24, '10.0.0.0', 26],
            '/24 minus middle /26' => ['10.0.0.0', 24, '10.0.0.64', 26],
            '/30 minus /32' => ['10.0.0.0', 30, '10.0.0.1', 32],
        ];
    }

    #[Test]
    public function excludePreservesTotalAddressCount(): void
    {
        // Given - A /24 network (256 addresses)
        $base = new Subnet('192.168.0.0', 24);
        $exclude = new Subnet('192.168.0.64', 26); // 64 addresses

        // When
        $result = $base->exclude($exclude);

        // Then - remaining should have 256 - 64 = 192 addresses
        $totalRemaining = 0;
        foreach ($result as $subnet) {
            $totalRemaining += $subnet->addressCount();
        }

        $this->assertSame(192, $totalRemaining);
    }

    /* ******************************************** *
     * LARGE NETWORK TESTS
     * Tests for very small prefix sizes that could
     * stress integer handling
     * ******************************************** */

    #[Test]
    #[DataProvider('dataProviderForLargeNetworks')]
    public function excludeFromLargeNetworks(
        string $baseIp,
        int $basePrefix,
        string $excludeIp,
        int $excludePrefix,
        array $expectedResults
    ): void {
        // Given
        $base = new Subnet($baseIp, $basePrefix);
        $exclude = new Subnet($excludeIp, $excludePrefix);

        // When
        $result = $base->exclude($exclude);

        // Then
        $this->assertCount(\count($expectedResults), $result);
        foreach ($expectedResults as $index => $expected) {
            $this->assertSame($expected['network'], $result[$index]->networkPortion()->asQuads());
            $this->assertSame($expected['prefix'], $result[$index]->networkSize());
        }
    }

    public static function dataProviderForLargeNetworks(): array
    {
        return [
            'Exclude second half of /8' => [
                '10.0.0.0', 8,
                '10.128.0.0', 9,
                [['network' => '10.0.0.0', 'prefix' => 9]],
            ],
            'Exclude first half of /8' => [
                '10.0.0.0', 8,
                '10.0.0.0', 9,
                [['network' => '10.128.0.0', 'prefix' => 9]],
            ],
            'Exclude /16 from /8' => [
                '10.0.0.0', 8,
                '10.0.0.0', 16,
                [
                    ['network' => '10.1.0.0', 'prefix' => 16],
                    ['network' => '10.2.0.0', 'prefix' => 15],
                    ['network' => '10.4.0.0', 'prefix' => 14],
                    ['network' => '10.8.0.0', 'prefix' => 13],
                    ['network' => '10.16.0.0', 'prefix' => 12],
                    ['network' => '10.32.0.0', 'prefix' => 11],
                    ['network' => '10.64.0.0', 'prefix' => 10],
                    ['network' => '10.128.0.0', 'prefix' => 9],
                ],
            ],
            'Exclude second half of /16' => [
                '172.16.0.0', 16,
                '172.16.128.0', 17,
                [['network' => '172.16.0.0', 'prefix' => 17]],
            ],
            'Exclude /24 from /16' => [
                '172.16.0.0', 16,
                '172.16.0.0', 24,
                [
                    ['network' => '172.16.1.0', 'prefix' => 24],
                    ['network' => '172.16.2.0', 'prefix' => 23],
                    ['network' => '172.16.4.0', 'prefix' => 22],
                    ['network' => '172.16.8.0', 'prefix' => 21],
                    ['network' => '172.16.16.0', 'prefix' => 20],
                    ['network' => '172.16.32.0', 'prefix' => 19],
                    ['network' => '172.16.64.0', 'prefix' => 18],
                    ['network' => '172.16.128.0', 'prefix' => 17],
                ],
            ],
        ];
    }

    #[Test]
    public function excludeFromLargeNetworkPreservesAddressCount(): void
    {
        // Given - A /8 network (16,777,216 addresses)
        $base = new Subnet('10.0.0.0', 8);
        $exclude = new Subnet('10.128.0.0', 9); // 8,388,608 addresses

        // When
        $result = $base->exclude($exclude);

        // Then - remaining should have 16,777,216 - 8,388,608 = 8,388,608 addresses
        $totalRemaining = 0;
        foreach ($result as $subnet) {
            $totalRemaining += $subnet->addressCount();
        }

        $this->assertSame(8388608, $totalRemaining);
    }

    /* ******************************************** *
     * HIGH IP RANGE TESTS
     * Tests for IPs with high bit set (128-255 range)
     * which use signed integers in PHP
     * ******************************************** */

    #[Test]
    #[DataProvider('dataProviderForHighIpRanges')]
    public function excludeFromHighIpRanges(
        string $baseIp,
        int $basePrefix,
        string $excludeIp,
        int $excludePrefix,
        array $expectedResults
    ): void {
        // Given
        $base = new Subnet($baseIp, $basePrefix);
        $exclude = new Subnet($excludeIp, $excludePrefix);

        // When
        $result = $base->exclude($exclude);

        // Then
        $this->assertCount(\count($expectedResults), $result);
        foreach ($expectedResults as $index => $expected) {
            $this->assertSame($expected['network'], $result[$index]->networkPortion()->asQuads());
            $this->assertSame($expected['prefix'], $result[$index]->networkSize());
        }
    }

    public static function dataProviderForHighIpRanges(): array
    {
        return [
            'Exclude from 240.x.x.x range (reserved)' => [
                '240.0.0.0', 24,
                '240.0.0.128', 25,
                [['network' => '240.0.0.0', 'prefix' => 25]],
            ],
            'Exclude from 255.255.255.x range' => [
                '255.255.255.0', 24,
                '255.255.255.0', 25,
                [['network' => '255.255.255.128', 'prefix' => 25]],
            ],
            'Exclude from 128.x.x.x range' => [
                '128.0.0.0', 24,
                '128.0.0.0', 26,
                [
                    ['network' => '128.0.0.64', 'prefix' => 26],
                    ['network' => '128.0.0.128', 'prefix' => 25],
                ],
            ],
            'Exclude from 192.x.x.x range' => [
                '192.0.0.0', 24,
                '192.0.0.64', 26,
                [
                    ['network' => '192.0.0.0', 'prefix' => 26],
                    ['network' => '192.0.0.128', 'prefix' => 25],
                ],
            ],
            'Exclude /32 from high range /31' => [
                '255.255.255.254', 31,
                '255.255.255.254', 32,
                [['network' => '255.255.255.255', 'prefix' => 32]],
            ],
            'Exclude /32 from 200.x.x.x /30' => [
                '200.200.200.0', 30,
                '200.200.200.2', 32,
                [
                    ['network' => '200.200.200.0', 'prefix' => 31],
                    ['network' => '200.200.200.3', 'prefix' => 32],
                ],
            ],
            // Large high-IP network tests (signed integer edge cases)
            'Exclude first half of 200.0.0.0/8' => [
                '200.0.0.0', 8,
                '200.0.0.0', 9,
                [['network' => '200.128.0.0', 'prefix' => 9]],
            ],
            'Exclude second half of 200.0.0.0/8' => [
                '200.0.0.0', 8,
                '200.128.0.0', 9,
                [['network' => '200.0.0.0', 'prefix' => 9]],
            ],
            'Exclude /16 from 200.0.0.0/8' => [
                '200.0.0.0', 8,
                '200.100.0.0', 16,
                [
                    ['network' => '200.0.0.0', 'prefix' => 10],
                    ['network' => '200.64.0.0', 'prefix' => 11],
                    ['network' => '200.96.0.0', 'prefix' => 14],
                    ['network' => '200.101.0.0', 'prefix' => 16],
                    ['network' => '200.102.0.0', 'prefix' => 15],
                    ['network' => '200.104.0.0', 'prefix' => 13],
                    ['network' => '200.112.0.0', 'prefix' => 12],
                    ['network' => '200.128.0.0', 'prefix' => 9],
                ],
            ],
            'Exclude from 255.0.0.0/8 (highest range)' => [
                '255.0.0.0', 8,
                '255.0.0.0', 9,
                [['network' => '255.128.0.0', 'prefix' => 9]],
            ],
        ];
    }

    #[Test]
    public function excludeFromHighIpRangePreservesAddressCount(): void
    {
        // Given - A /24 network in high IP range
        $base = new Subnet('240.0.0.0', 24);
        $exclude = new Subnet('240.0.0.0', 26); // 64 addresses

        // When
        $result = $base->exclude($exclude);

        // Then - remaining should have 256 - 64 = 192 addresses
        $totalRemaining = 0;
        foreach ($result as $subnet) {
            $totalRemaining += $subnet->addressCount();
        }

        $this->assertSame(192, $totalRemaining);
    }

    /* ******************************************** *
     * BOUNDARY ADDRESS EXCLUSION TESTS
     * Tests for excluding first/last addresses
     * ******************************************** */

    #[Test]
    #[DataProvider('dataProviderForBoundaryAddressExclusions')]
    public function excludeBoundaryAddresses(
        string $baseIp,
        int $basePrefix,
        string $excludeIp,
        int $excludePrefix,
        int $expectedRemainingAddresses
    ): void {
        // Given
        $base = new Subnet($baseIp, $basePrefix);
        $exclude = new Subnet($excludeIp, $excludePrefix);

        // When
        $result = $base->exclude($exclude);

        // Then
        $totalRemaining = 0;
        foreach ($result as $subnet) {
            $totalRemaining += $subnet->addressCount();
        }

        $this->assertSame($expectedRemainingAddresses, $totalRemaining);

        // Verify excluded IP is not in any result subnet
        foreach ($result as $subnet) {
            $this->assertFalse(
                $subnet->containsIP($excludeIp),
                "Excluded IP $excludeIp should not be in result subnet"
            );
        }
    }

    public static function dataProviderForBoundaryAddressExclusions(): array
    {
        return [
            'Exclude first address (.0) from /24' => [
                '10.0.0.0', 24,
                '10.0.0.0', 32,
                255, // 256 - 1
            ],
            'Exclude last address (.255) from /24' => [
                '10.0.0.0', 24,
                '10.0.0.255', 32,
                255, // 256 - 1
            ],
            'Exclude octet boundary address (.128) from /24' => [
                '10.0.0.0', 24,
                '10.0.0.128', 32,
                255, // 256 - 1
            ],
            'Exclude first address from /30' => [
                '10.0.0.0', 30,
                '10.0.0.0', 32,
                3, // 4 - 1
            ],
            'Exclude last address from /30' => [
                '10.0.0.0', 30,
                '10.0.0.3', 32,
                3, // 4 - 1
            ],
            'Exclude first address from /16' => [
                '172.16.0.0', 16,
                '172.16.0.0', 32,
                65535, // 65536 - 1
            ],
            'Exclude last address from /16' => [
                '172.16.0.0', 16,
                '172.16.255.255', 32,
                65535, // 65536 - 1
            ],
        ];
    }

    #[Test]
    public function excludeFirstAndLastAddressFromSubnet(): void
    {
        // Given - A /24 network
        $base = new Subnet('10.0.0.0', 24);

        // Exclude first and last addresses
        $excludes = [
            new Subnet('10.0.0.0', 32),   // First
            new Subnet('10.0.0.255', 32), // Last
        ];

        // When
        $result = $base->excludeAll($excludes);

        // Then - Should have 254 addresses remaining
        $totalRemaining = 0;
        foreach ($result as $subnet) {
            $totalRemaining += $subnet->addressCount();
        }

        $this->assertSame(254, $totalRemaining);

        // Verify neither excluded IP is in any result subnet
        foreach ($result as $subnet) {
            $this->assertFalse($subnet->containsIP('10.0.0.0'));
            $this->assertFalse($subnet->containsIP('10.0.0.255'));
        }
    }

    /* ******************************************** *
     * EXCLUDEALL EDGE CASES
     * Tests for many sequential exclusions
     * ******************************************** */

    #[Test]
    public function excludeAllWithManySequentialExclusions(): void
    {
        // Given - A /24 network
        $base = new Subnet('192.168.0.0', 24);

        // 16 non-overlapping /28 subnets to exclude (all but one quarter)
        $excludes = [];
        for ($i = 0; $i < 12; $i++) {
            $octet = $i * 16;
            $excludes[] = new Subnet("192.168.0.$octet", 28);
        }

        // When
        $result = $base->excludeAll($excludes);

        // Then - Should have 256 - (12 * 16) = 64 addresses remaining
        $totalRemaining = 0;
        foreach ($result as $subnet) {
            $totalRemaining += $subnet->addressCount();
        }

        $this->assertSame(64, $totalRemaining);

        // Verify no excluded subnet overlaps with results
        foreach ($result as $subnet) {
            foreach ($excludes as $excluded) {
                $this->assertFalse(
                    $subnet->overlaps($excluded),
                    "Result subnet should not overlap with any excluded subnet"
                );
            }
        }
    }

    #[Test]
    public function excludeAllWithAlternatingExclusions(): void
    {
        // Given - A /24 network, exclude every other /26
        $base = new Subnet('10.0.0.0', 24);

        $excludes = [
            new Subnet('10.0.0.0', 26),   // 0-63
            new Subnet('10.0.0.128', 26), // 128-191
        ];

        // When
        $result = $base->excludeAll($excludes);

        // Then - Should have 128 addresses in two /26 blocks
        $this->assertCount(2, $result);

        $remainingNetworks = \array_map(function ($s) {
            return $s->networkPortion()->asQuads() . '/' . $s->networkSize();
        }, $result);

        $this->assertContains('10.0.0.64/26', $remainingNetworks);
        $this->assertContains('10.0.0.192/26', $remainingNetworks);
    }

    #[Test]
    public function excludeAllWithScatteredSingleHosts(): void
    {
        // Given - A /28 network (16 addresses)
        $base = new Subnet('10.0.0.0', 28);

        // Exclude 4 scattered hosts
        $excludes = [
            new Subnet('10.0.0.0', 32),
            new Subnet('10.0.0.5', 32),
            new Subnet('10.0.0.10', 32),
            new Subnet('10.0.0.15', 32),
        ];

        // When
        $result = $base->excludeAll($excludes);

        // Then - Should have 12 addresses remaining
        $totalRemaining = 0;
        foreach ($result as $subnet) {
            $totalRemaining += $subnet->addressCount();
        }

        $this->assertSame(12, $totalRemaining);

        // Verify all excluded hosts are not in results
        foreach ($result as $subnet) {
            $this->assertFalse($subnet->containsIP('10.0.0.0'));
            $this->assertFalse($subnet->containsIP('10.0.0.5'));
            $this->assertFalse($subnet->containsIP('10.0.0.10'));
            $this->assertFalse($subnet->containsIP('10.0.0.15'));
        }
    }
}
