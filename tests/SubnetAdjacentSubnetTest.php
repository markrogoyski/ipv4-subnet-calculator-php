<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Adjacent Subnet Navigation feature.
 *
 * This feature enables navigation to previous/next subnets of the same size,
 * useful for sequential IP allocation and network expansion planning.
 */
class SubnetAdjacentSubnetTest extends TestCase
{
    /* ********************* *
     * getNextSubnet() Tests
     * ********************* */

    #[Test]
    #[DataProvider('dataProviderForGetNextSubnet')]
    public function getNextSubnetReturnsCorrectSubnet(
        string $currentIp,
        int $networkSize,
        string $expectedNetworkPortion
    ): void {
        // Given
        $subnet = new Subnet($currentIp, $networkSize);

        // When
        $nextSubnet = $subnet->next();

        // Then
        $this->assertInstanceOf(Subnet::class, $nextSubnet);
        $this->assertSame($expectedNetworkPortion, $nextSubnet->networkPortion()->asQuads());
        $this->assertSame($networkSize, $nextSubnet->networkSize());
    }

    public static function dataProviderForGetNextSubnet(): array
    {
        return [
            'Standard /24' => [
                '192.168.0.0',
                24,
                '192.168.1.0',
            ],
            '/24 crosses third octet' => [
                '192.168.255.0',
                24,
                '192.169.0.0',
            ],
            '/8 network' => [
                '10.0.0.0',
                8,
                '11.0.0.0',
            ],
            '/30 subnet' => [
                '192.168.0.0',
                30,
                '192.168.0.4',
            ],
            '/31 RFC 3021' => [
                '10.0.0.0',
                31,
                '10.0.0.2',
            ],
            '/32 single host' => [
                '10.0.0.0',
                32,
                '10.0.0.1',
            ],
            '/16 network' => [
                '172.16.0.0',
                16,
                '172.17.0.0',
            ],
            '/25 half network' => [
                '192.168.1.0',
                25,
                '192.168.1.128',
            ],
            '/25 second half' => [
                '192.168.1.128',
                25,
                '192.168.2.0',
            ],
            'IP not on boundary still works' => [
                '192.168.0.50',
                24,
                '192.168.1.0',
            ],
            // High IP addresses (≥128.0.0.0) - tests signed/unsigned conversion
            'High IP /24 basic' => [
                '200.0.0.0',
                24,
                '200.0.1.0',
            ],
            'High IP /24 near max' => [
                '250.0.0.0',
                24,
                '250.0.1.0',
            ],
            'High IP /16' => [
                '200.0.0.0',
                16,
                '200.1.0.0',
            ],
            'IP at 128.0.0.0 boundary' => [
                '128.0.0.0',
                24,
                '128.0.1.0',
            ],
            // Multiple octet crossings
            'Crosses second and third octet' => [
                '10.255.255.0',
                24,
                '11.0.0.0',
            ],
            'Large subnet crosses first octet' => [
                '10.0.0.0',
                8,
                '11.0.0.0',
            ],
            // Very large subnets
            '/1 subnet (half internet)' => [
                '0.0.0.0',
                1,
                '128.0.0.0',
            ],
            '/2 subnet' => [
                '0.0.0.0',
                2,
                '64.0.0.0',
            ],
            '/3 subnet' => [
                '0.0.0.0',
                3,
                '32.0.0.0',
            ],
        ];
    }

    #[Test]
    public function getNextSubnetThrowsExceptionWhenExceedingValidRange(): void
    {
        // Given
        $subnet = new Subnet('255.255.255.0', 24);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $subnet->next();
    }

    #[Test]
    public function getNextSubnetThrowsExceptionForLastPossibleSubnet32(): void
    {
        // Given
        $subnet = new Subnet('255.255.255.255', 32);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $subnet->next();
    }

    /* ************************* *
     * getPreviousSubnet() Tests
     * ************************* */

    #[Test]
    #[DataProvider('dataProviderForGetPreviousSubnet')]
    public function getPreviousSubnetReturnsCorrectSubnet(
        string $currentIp,
        int $networkSize,
        string $expectedNetworkPortion
    ): void {
        // Given
        $subnet = new Subnet($currentIp, $networkSize);

        // When
        $previousSubnet = $subnet->previous();

        // Then
        $this->assertInstanceOf(Subnet::class, $previousSubnet);
        $this->assertSame($expectedNetworkPortion, $previousSubnet->networkPortion()->asQuads());
        $this->assertSame($networkSize, $previousSubnet->networkSize());
    }

    public static function dataProviderForGetPreviousSubnet(): array
    {
        return [
            'Standard /24' => [
                '192.168.1.0',
                24,
                '192.168.0.0',
            ],
            '/24 crosses third octet' => [
                '192.169.0.0',
                24,
                '192.168.255.0',
            ],
            '/8 network' => [
                '11.0.0.0',
                8,
                '10.0.0.0',
            ],
            '/30 subnet' => [
                '192.168.0.4',
                30,
                '192.168.0.0',
            ],
            '/31 RFC 3021' => [
                '10.0.0.2',
                31,
                '10.0.0.0',
            ],
            '/32 single host' => [
                '10.0.0.1',
                32,
                '10.0.0.0',
            ],
            '/16 network' => [
                '172.17.0.0',
                16,
                '172.16.0.0',
            ],
            '/25 second half' => [
                '192.168.1.128',
                25,
                '192.168.1.0',
            ],
            '/25 crosses octet' => [
                '192.168.2.0',
                25,
                '192.168.1.128',
            ],
            'IP not on boundary still works' => [
                '192.168.1.50',
                24,
                '192.168.0.0',
            ],
            // High IP addresses (≥128.0.0.0) - tests signed/unsigned conversion
            'High IP /24 basic' => [
                '200.0.1.0',
                24,
                '200.0.0.0',
            ],
            'High IP /24 near max' => [
                '250.0.1.0',
                24,
                '250.0.0.0',
            ],
            'High IP /16' => [
                '200.1.0.0',
                16,
                '200.0.0.0',
            ],
            'IP at 128.0.0.0 boundary' => [
                '128.0.1.0',
                24,
                '128.0.0.0',
            ],
            'Crossing below 128.0.0.0' => [
                '128.0.0.0',
                24,
                '127.255.255.0',
            ],
            // Multiple octet crossings
            'Crosses second and third octet' => [
                '11.0.0.0',
                24,
                '10.255.255.0',
            ],
            'Large subnet crosses first octet' => [
                '11.0.0.0',
                8,
                '10.0.0.0',
            ],
            // Very large subnets
            '/1 subnet (half internet)' => [
                '128.0.0.0',
                1,
                '0.0.0.0',
            ],
            '/2 subnet' => [
                '64.0.0.0',
                2,
                '0.0.0.0',
            ],
            '/3 subnet' => [
                '32.0.0.0',
                3,
                '0.0.0.0',
            ],
        ];
    }

    #[Test]
    public function getPreviousSubnetThrowsExceptionWhenBelowZero(): void
    {
        // Given
        $subnet = new Subnet('0.0.0.0', 24);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $subnet->previous();
    }

    #[Test]
    public function getPreviousSubnetThrowsExceptionForFirstPossibleSubnet32(): void
    {
        // Given
        $subnet = new Subnet('0.0.0.0', 32);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $subnet->previous();
    }

    /* ************************** *
     * getAdjacentSubnets() Tests
     * ************************** */

    #[Test]
    #[DataProvider('dataProviderForGetAdjacentSubnetsForward')]
    public function getAdjacentSubnetsReturnsCorrectForwardSubnets(
        string $currentIp,
        int $networkSize,
        int $count,
        array $expectedNetworkPortions
    ): void {
        // Given
        $subnet = new Subnet($currentIp, $networkSize);

        // When
        $adjacentSubnets = $subnet->adjacent($count);

        // Then
        $this->assertCount(\count($expectedNetworkPortions), $adjacentSubnets);
        foreach ($adjacentSubnets as $index => $adjacentSubnet) {
            $this->assertInstanceOf(Subnet::class, $adjacentSubnet);
            $this->assertSame($expectedNetworkPortions[$index], $adjacentSubnet->networkPortion()->asQuads());
            $this->assertSame($networkSize, $adjacentSubnet->networkSize());
        }
    }

    public static function dataProviderForGetAdjacentSubnetsForward(): array
    {
        return [
            'Three /24s forward' => [
                '192.168.0.0',
                24,
                3,
                ['192.168.1.0', '192.168.2.0', '192.168.3.0'],
            ],
            'Two /30s forward' => [
                '10.0.0.0',
                30,
                2,
                ['10.0.0.4', '10.0.0.8'],
            ],
            'Five /32s forward' => [
                '192.168.1.0',
                32,
                5,
                ['192.168.1.1', '192.168.1.2', '192.168.1.3', '192.168.1.4', '192.168.1.5'],
            ],
            'Two /31s forward' => [
                '10.0.0.0',
                31,
                2,
                ['10.0.0.2', '10.0.0.4'],
            ],
            'Zero count returns empty' => [
                '192.168.0.0',
                24,
                0,
                [],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForGetAdjacentSubnetsBackward')]
    public function getAdjacentSubnetsReturnsCorrectBackwardSubnets(
        string $currentIp,
        int $networkSize,
        int $count,
        array $expectedNetworkPortions
    ): void {
        // Given
        $subnet = new Subnet($currentIp, $networkSize);

        // When
        $adjacentSubnets = $subnet->adjacent($count);

        // Then
        $this->assertCount(\count($expectedNetworkPortions), $adjacentSubnets);
        foreach ($adjacentSubnets as $index => $adjacentSubnet) {
            $this->assertInstanceOf(Subnet::class, $adjacentSubnet);
            $this->assertSame($expectedNetworkPortions[$index], $adjacentSubnet->networkPortion()->asQuads());
            $this->assertSame($networkSize, $adjacentSubnet->networkSize());
        }
    }

    public static function dataProviderForGetAdjacentSubnetsBackward(): array
    {
        return [
            'Three /24s backward' => [
                '192.168.5.0',
                24,
                -3,
                ['192.168.4.0', '192.168.3.0', '192.168.2.0'],
            ],
            'Two /30s backward' => [
                '10.0.0.12',
                30,
                -2,
                ['10.0.0.8', '10.0.0.4'],
            ],
            'Five /32s backward' => [
                '192.168.1.10',
                32,
                -5,
                ['192.168.1.9', '192.168.1.8', '192.168.1.7', '192.168.1.6', '192.168.1.5'],
            ],
            'Two /31s backward' => [
                '10.0.0.6',
                31,
                -2,
                ['10.0.0.4', '10.0.0.2'],
            ],
        ];
    }

    #[Test]
    public function getAdjacentSubnetsStopsAtUpperBoundary(): void
    {
        // Given - requesting more subnets than possible
        $subnet = new Subnet('255.255.255.0', 24);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $subnet->adjacent(2);
    }

    #[Test]
    public function getAdjacentSubnetsStopsAtLowerBoundary(): void
    {
        // Given - requesting more subnets backward than possible
        $subnet = new Subnet('0.0.1.0', 24);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $subnet->adjacent(-2);
    }

    /* ************************** *
     * Large Adjacent Count Tests
     * ************************** */

    #[Test]
    public function getAdjacentSubnetsHandlesLargeForwardCount(): void
    {
        // Given
        $subnet = new Subnet('192.168.0.0', 24);

        // When
        $adjacentSubnets = $subnet->adjacent(100);

        // Then
        $this->assertCount(100, $adjacentSubnets);
        $this->assertSame('192.168.1.0', $adjacentSubnets[0]->networkPortion()->asQuads());
        $this->assertSame('192.168.100.0', $adjacentSubnets[99]->networkPortion()->asQuads());
    }

    #[Test]
    public function getAdjacentSubnetsHandlesLargeBackwardCount(): void
    {
        // Given
        $subnet = new Subnet('192.168.200.0', 24);

        // When
        $adjacentSubnets = $subnet->adjacent(-100);

        // Then
        $this->assertCount(100, $adjacentSubnets);
        $this->assertSame('192.168.199.0', $adjacentSubnets[0]->networkPortion()->asQuads());
        $this->assertSame('192.168.100.0', $adjacentSubnets[99]->networkPortion()->asQuads());
    }

    #[Test]
    public function getAdjacentSubnetsWithHighIpAddresses(): void
    {
        // Given - tests signed/unsigned conversion with multiple subnets
        $subnet = new Subnet('200.0.0.0', 24);

        // When
        $adjacentSubnets = $subnet->adjacent(5);

        // Then
        $this->assertCount(5, $adjacentSubnets);
        $this->assertSame('200.0.1.0', $adjacentSubnets[0]->networkPortion()->asQuads());
        $this->assertSame('200.0.5.0', $adjacentSubnets[4]->networkPortion()->asQuads());
    }

    /* ************************* *
     * Exact Boundary Tests
     * ************************* */

    #[Test]
    public function getNextSubnetReachesExactlyMaxBoundary(): void
    {
        // Given - 255.255.255.0/24 + 1 would start at 256.0.0.0 which is invalid
        // But 255.255.254.0/24 + 1 = 255.255.255.0/24 which is valid
        $subnet = new Subnet('255.255.254.0', 24);

        // When
        $nextSubnet = $subnet->next();

        // Then
        $this->assertSame('255.255.255.0', $nextSubnet->networkPortion()->asQuads());
    }

    #[Test]
    public function getPreviousSubnetReachesExactlyMinBoundary(): void
    {
        // Given - 0.0.1.0/24 - 1 = 0.0.0.0/24 which is valid
        $subnet = new Subnet('0.0.1.0', 24);

        // When
        $previousSubnet = $subnet->previous();

        // Then
        $this->assertSame('0.0.0.0', $previousSubnet->networkPortion()->asQuads());
    }

    #[Test]
    public function getNextSubnetWithSlash32AtSecondToLastAddress(): void
    {
        // Given - 255.255.255.254/32 + 1 = 255.255.255.255/32 (valid, last address)
        $subnet = new Subnet('255.255.255.254', 32);

        // When
        $nextSubnet = $subnet->next();

        // Then
        $this->assertSame('255.255.255.255', $nextSubnet->networkPortion()->asQuads());
    }

    #[Test]
    public function getPreviousSubnetWithSlash32AtSecondAddress(): void
    {
        // Given - 0.0.0.1/32 - 1 = 0.0.0.0/32 (valid, first address)
        $subnet = new Subnet('0.0.0.1', 32);

        // When
        $previousSubnet = $subnet->previous();

        // Then
        $this->assertSame('0.0.0.0', $previousSubnet->networkPortion()->asQuads());
    }

    #[Test]
    public function getAdjacentSubnetsReachesExactBoundaryForward(): void
    {
        // Given - start at 255.255.252.0/24, can get exactly 3 more subnets
        $subnet = new Subnet('255.255.252.0', 24);

        // When
        $adjacentSubnets = $subnet->adjacent(3);

        // Then
        $this->assertCount(3, $adjacentSubnets);
        $this->assertSame('255.255.253.0', $adjacentSubnets[0]->networkPortion()->asQuads());
        $this->assertSame('255.255.254.0', $adjacentSubnets[1]->networkPortion()->asQuads());
        $this->assertSame('255.255.255.0', $adjacentSubnets[2]->networkPortion()->asQuads());
    }

    #[Test]
    public function getAdjacentSubnetsReachesExactBoundaryBackward(): void
    {
        // Given - start at 0.0.3.0/24, can get exactly 3 previous subnets
        $subnet = new Subnet('0.0.3.0', 24);

        // When
        $adjacentSubnets = $subnet->adjacent(-3);

        // Then
        $this->assertCount(3, $adjacentSubnets);
        $this->assertSame('0.0.2.0', $adjacentSubnets[0]->networkPortion()->asQuads());
        $this->assertSame('0.0.1.0', $adjacentSubnets[1]->networkPortion()->asQuads());
        $this->assertSame('0.0.0.0', $adjacentSubnets[2]->networkPortion()->asQuads());
    }

    /* ***************************** *
     * Integration/Chaining Tests
     * ***************************** */

    #[Test]
    public function getNextSubnetAndPreviousSubnetAreInverses(): void
    {
        // Given
        $originalSubnet = new Subnet('192.168.5.0', 24);

        // When
        $nextSubnet = $originalSubnet->next();
        $backToOriginal = $nextSubnet->previous();

        // Then
        $this->assertSame(
            $originalSubnet->networkPortion()->asQuads(),
            $backToOriginal->networkPortion()->asQuads()
        );
    }

    #[Test]
    public function chainingMultipleNextSubnetsCalls(): void
    {
        // Given
        $subnet = new Subnet('192.168.0.0', 24);

        // When
        $subnet = $subnet->next();
        $subnet = $subnet->next();
        $subnet = $subnet->next();

        // Then
        $this->assertSame('192.168.3.0', $subnet->networkPortion()->asQuads());
    }

    #[Test]
    public function chainingMultiplePreviousSubnetsCalls(): void
    {
        // Given
        $subnet = new Subnet('192.168.5.0', 24);

        // When
        $subnet = $subnet->previous();
        $subnet = $subnet->previous();
        $subnet = $subnet->previous();

        // Then
        $this->assertSame('192.168.2.0', $subnet->networkPortion()->asQuads());
    }
}
