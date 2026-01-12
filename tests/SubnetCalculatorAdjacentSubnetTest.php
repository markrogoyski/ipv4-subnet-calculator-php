<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\SubnetCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Adjacent Subnet Navigation feature.
 *
 * This feature enables navigation to previous/next subnets of the same size,
 * useful for sequential IP allocation and network expansion planning.
 */
class SubnetCalculatorAdjacentSubnetTest extends TestCase
{
    /* ********************* *
     * getNextSubnet() Tests
     * ********************* */

    /**
     * @test
     * @dataProvider dataProviderForGetNextSubnet
     */
    public function getNextSubnetReturnsCorrectSubnet(
        string $currentIp,
        int $networkSize,
        string $expectedNetworkPortion
    ): void {
        // Given
        $subnet = new SubnetCalculator($currentIp, $networkSize);

        // When
        $nextSubnet = $subnet->getNextSubnet();

        // Then
        $this->assertInstanceOf(SubnetCalculator::class, $nextSubnet);
        $this->assertSame($expectedNetworkPortion, $nextSubnet->getNetworkPortion());
        $this->assertSame($networkSize, $nextSubnet->getNetworkSize());
    }

    public function dataProviderForGetNextSubnet(): array
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
        ];
    }

    /**
     * @test
     */
    public function getNextSubnetThrowsExceptionWhenExceedingValidRange(): void
    {
        // Given
        $subnet = new SubnetCalculator('255.255.255.0', 24);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $subnet->getNextSubnet();
    }

    /**
     * @test
     */
    public function getNextSubnetThrowsExceptionForLastPossibleSubnet32(): void
    {
        // Given
        $subnet = new SubnetCalculator('255.255.255.255', 32);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $subnet->getNextSubnet();
    }

    /* ************************* *
     * getPreviousSubnet() Tests
     * ************************* */

    /**
     * @test
     * @dataProvider dataProviderForGetPreviousSubnet
     */
    public function getPreviousSubnetReturnsCorrectSubnet(
        string $currentIp,
        int $networkSize,
        string $expectedNetworkPortion
    ): void {
        // Given
        $subnet = new SubnetCalculator($currentIp, $networkSize);

        // When
        $previousSubnet = $subnet->getPreviousSubnet();

        // Then
        $this->assertInstanceOf(SubnetCalculator::class, $previousSubnet);
        $this->assertSame($expectedNetworkPortion, $previousSubnet->getNetworkPortion());
        $this->assertSame($networkSize, $previousSubnet->getNetworkSize());
    }

    public function dataProviderForGetPreviousSubnet(): array
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
        ];
    }

    /**
     * @test
     */
    public function getPreviousSubnetThrowsExceptionWhenBelowZero(): void
    {
        // Given
        $subnet = new SubnetCalculator('0.0.0.0', 24);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $subnet->getPreviousSubnet();
    }

    /**
     * @test
     */
    public function getPreviousSubnetThrowsExceptionForFirstPossibleSubnet32(): void
    {
        // Given
        $subnet = new SubnetCalculator('0.0.0.0', 32);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $subnet->getPreviousSubnet();
    }

    /* ************************** *
     * getAdjacentSubnets() Tests
     * ************************** */

    /**
     * @test
     * @dataProvider dataProviderForGetAdjacentSubnetsForward
     */
    public function getAdjacentSubnetsReturnsCorrectForwardSubnets(
        string $currentIp,
        int $networkSize,
        int $count,
        array $expectedNetworkPortions
    ): void {
        // Given
        $subnet = new SubnetCalculator($currentIp, $networkSize);

        // When
        $adjacentSubnets = $subnet->getAdjacentSubnets($count);

        // Then
        $this->assertCount(\count($expectedNetworkPortions), $adjacentSubnets);
        foreach ($adjacentSubnets as $index => $adjacentSubnet) {
            $this->assertInstanceOf(SubnetCalculator::class, $adjacentSubnet);
            $this->assertSame($expectedNetworkPortions[$index], $adjacentSubnet->getNetworkPortion());
            $this->assertSame($networkSize, $adjacentSubnet->getNetworkSize());
        }
    }

    public function dataProviderForGetAdjacentSubnetsForward(): array
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

    /**
     * @test
     * @dataProvider dataProviderForGetAdjacentSubnetsBackward
     */
    public function getAdjacentSubnetsReturnsCorrectBackwardSubnets(
        string $currentIp,
        int $networkSize,
        int $count,
        array $expectedNetworkPortions
    ): void {
        // Given
        $subnet = new SubnetCalculator($currentIp, $networkSize);

        // When
        $adjacentSubnets = $subnet->getAdjacentSubnets($count);

        // Then
        $this->assertCount(\count($expectedNetworkPortions), $adjacentSubnets);
        foreach ($adjacentSubnets as $index => $adjacentSubnet) {
            $this->assertInstanceOf(SubnetCalculator::class, $adjacentSubnet);
            $this->assertSame($expectedNetworkPortions[$index], $adjacentSubnet->getNetworkPortion());
            $this->assertSame($networkSize, $adjacentSubnet->getNetworkSize());
        }
    }

    public function dataProviderForGetAdjacentSubnetsBackward(): array
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

    /**
     * @test
     */
    public function getAdjacentSubnetsStopsAtUpperBoundary(): void
    {
        // Given - requesting more subnets than possible
        $subnet = new SubnetCalculator('255.255.255.0', 24);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $subnet->getAdjacentSubnets(2);
    }

    /**
     * @test
     */
    public function getAdjacentSubnetsStopsAtLowerBoundary(): void
    {
        // Given - requesting more subnets backward than possible
        $subnet = new SubnetCalculator('0.0.1.0', 24);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $subnet->getAdjacentSubnets(-2);
    }

    /* ***************************** *
     * Integration/Chaining Tests
     * ***************************** */

    /**
     * @test
     */
    public function getNextSubnetAndPreviousSubnetAreInverses(): void
    {
        // Given
        $originalSubnet = new SubnetCalculator('192.168.5.0', 24);

        // When
        $nextSubnet = $originalSubnet->getNextSubnet();
        $backToOriginal = $nextSubnet->getPreviousSubnet();

        // Then
        $this->assertSame(
            $originalSubnet->getNetworkPortion(),
            $backToOriginal->getNetworkPortion()
        );
    }

    /**
     * @test
     */
    public function chainingMultipleNextSubnetsCalls(): void
    {
        // Given
        $subnet = new SubnetCalculator('192.168.0.0', 24);

        // When
        $subnet = $subnet->getNextSubnet();
        $subnet = $subnet->getNextSubnet();
        $subnet = $subnet->getNextSubnet();

        // Then
        $this->assertSame('192.168.3.0', $subnet->getNetworkPortion());
    }

    /**
     * @test
     */
    public function chainingMultiplePreviousSubnetsCalls(): void
    {
        // Given
        $subnet = new SubnetCalculator('192.168.5.0', 24);

        // When
        $subnet = $subnet->getPreviousSubnet();
        $subnet = $subnet->getPreviousSubnet();
        $subnet = $subnet->getPreviousSubnet();

        // Then
        $this->assertSame('192.168.2.0', $subnet->getNetworkPortion());
    }
}
