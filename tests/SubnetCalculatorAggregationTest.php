<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\SubnetCalculator;
use IPv4\SubnetCalculatorFactory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CIDR Aggregation / Supernetting feature.
 *
 * @covers \IPv4\SubnetCalculatorFactory::aggregate
 * @covers \IPv4\SubnetCalculatorFactory::summarize
 *
 * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - Classless Inter-domain Routing (CIDR)
 */
class SubnetCalculatorAggregationTest extends TestCase
{
    /* ************************** *
     * aggregate() method tests
     * ************************** */

    /**
     * @test
     * @dataProvider dataProviderForAggregate
     *
     * @param string[] $inputCidrs Array of CIDR strings to aggregate
     * @param string[] $expectedCidrs Expected resulting CIDR strings
     */
    public function aggregate(array $inputCidrs, array $expectedCidrs): void
    {
        // Given
        $subnets = \array_map(function ($cidr) {
            return SubnetCalculatorFactory::fromCidr($cidr);
        }, $inputCidrs);

        // When
        $result = SubnetCalculatorFactory::aggregate($subnets);

        // Then
        $resultCidrs = \array_map(function (SubnetCalculator $subnet) {
            return $subnet->getNetworkPortion() . '/' . $subnet->getNetworkSize();
        }, $result);

        $this->assertSame($expectedCidrs, $resultCidrs);
    }

    /**
     * @return array<string, array{inputCidrs: string[], expectedCidrs: string[]}>
     */
    public function dataProviderForAggregate(): array
    {
        return [
            'two /24s aggregate to one /23' => [
                'inputCidrs' => ['192.168.0.0/24', '192.168.1.0/24'],
                'expectedCidrs' => ['192.168.0.0/23'],
            ],
            'four /24s aggregate to one /22' => [
                'inputCidrs' => ['10.0.0.0/24', '10.0.1.0/24', '10.0.2.0/24', '10.0.3.0/24'],
                'expectedCidrs' => ['10.0.0.0/22'],
            ],
            'non-contiguous subnets cannot aggregate' => [
                'inputCidrs' => ['192.168.0.0/24', '192.168.2.0/24'],
                'expectedCidrs' => ['192.168.0.0/24', '192.168.2.0/24'],
            ],
            'two /31s aggregate to one /30' => [
                'inputCidrs' => ['10.0.0.0/31', '10.0.0.2/31'],
                'expectedCidrs' => ['10.0.0.0/30'],
            ],
            'two /32s aggregate to one /31' => [
                'inputCidrs' => ['10.0.0.0/32', '10.0.0.1/32'],
                'expectedCidrs' => ['10.0.0.0/31'],
            ],
            'single subnet returns unchanged' => [
                'inputCidrs' => ['192.168.1.0/24'],
                'expectedCidrs' => ['192.168.1.0/24'],
            ],
            'empty input returns empty' => [
                'inputCidrs' => [],
                'expectedCidrs' => [],
            ],
            'three of four /24s partial aggregation' => [
                'inputCidrs' => ['10.0.0.0/24', '10.0.1.0/24', '10.0.2.0/24'],
                'expectedCidrs' => ['10.0.0.0/23', '10.0.2.0/24'],
            ],
            'reversed order still aggregates' => [
                'inputCidrs' => ['192.168.1.0/24', '192.168.0.0/24'],
                'expectedCidrs' => ['192.168.0.0/23'],
            ],
            'duplicate subnets deduplicated' => [
                'inputCidrs' => ['192.168.1.0/24', '192.168.1.0/24'],
                'expectedCidrs' => ['192.168.1.0/24'],
            ],
            'overlapping subnets simplified' => [
                'inputCidrs' => ['192.168.0.0/23', '192.168.0.0/24'],
                'expectedCidrs' => ['192.168.0.0/23'],
            ],
            'nested subnets simplified to largest' => [
                'inputCidrs' => ['10.0.0.0/8', '10.1.0.0/16', '10.1.1.0/24'],
                'expectedCidrs' => ['10.0.0.0/8'],
            ],
            'eight /24s aggregate to one /21' => [
                'inputCidrs' => [
                    '172.16.0.0/24', '172.16.1.0/24', '172.16.2.0/24', '172.16.3.0/24',
                    '172.16.4.0/24', '172.16.5.0/24', '172.16.6.0/24', '172.16.7.0/24',
                ],
                'expectedCidrs' => ['172.16.0.0/21'],
            ],
            'mixed sizes aggregate where possible' => [
                'inputCidrs' => ['192.168.0.0/25', '192.168.0.128/25'],
                'expectedCidrs' => ['192.168.0.0/24'],
            ],
            'subnets with gap cannot fully aggregate' => [
                'inputCidrs' => ['10.0.0.0/24', '10.0.1.0/24', '10.0.3.0/24'],
                'expectedCidrs' => ['10.0.0.0/23', '10.0.3.0/24'],
            ],
            'different sized contiguous subnets' => [
                'inputCidrs' => ['10.0.0.0/25', '10.0.0.128/26', '10.0.0.192/26'],
                'expectedCidrs' => ['10.0.0.0/24'],
            ],
            'misaligned adjacent blocks cannot merge' => [
                'inputCidrs' => ['10.0.1.0/24', '10.0.2.0/24'],
                'expectedCidrs' => ['10.0.1.0/24', '10.0.2.0/24'],
            ],
            'three /32s with partial adjacency' => [
                'inputCidrs' => ['10.0.0.0/32', '10.0.0.1/32', '10.0.0.5/32'],
                'expectedCidrs' => ['10.0.0.0/31', '10.0.0.5/32'],
            ],
            'multiple non-contiguous ranges aggregate separately' => [
                'inputCidrs' => ['10.0.0.0/24', '10.0.1.0/24', '192.168.0.0/24', '192.168.1.0/24'],
                'expectedCidrs' => ['10.0.0.0/23', '192.168.0.0/23'],
            ],
            'scrambled order still aggregates correctly' => [
                'inputCidrs' => ['10.0.3.0/24', '10.0.0.0/24', '10.0.2.0/24', '10.0.1.0/24'],
                'expectedCidrs' => ['10.0.0.0/22'],
            ],
            'two /1 subnets cannot merge to /0' => [
                'inputCidrs' => ['0.0.0.0/1', '128.0.0.0/1'],
                'expectedCidrs' => ['0.0.0.0/1', '128.0.0.0/1'],
            ],
            '/2 subnets can merge to /1' => [
                'inputCidrs' => ['0.0.0.0/2', '64.0.0.0/2'],
                'expectedCidrs' => ['0.0.0.0/1'],
            ],
        ];
    }

    /* ************************** *
     * summarize() method tests
     * ************************** */

    /**
     * @test
     * @dataProvider dataProviderForSummarize
     *
     * @param string[] $inputCidrs Array of CIDR strings to summarize
     * @param string $expectedCidr Expected resulting CIDR string
     */
    public function summarize(array $inputCidrs, string $expectedCidr): void
    {
        // Given
        $subnets = \array_map(function ($cidr) {
            return SubnetCalculatorFactory::fromCidr($cidr);
        }, $inputCidrs);

        // When
        $result = SubnetCalculatorFactory::summarize($subnets);

        // Then
        $resultCidr = $result->getNetworkPortion() . '/' . $result->getNetworkSize();
        $this->assertSame($expectedCidr, $resultCidr);
    }

    /**
     * @return array<string, array{inputCidrs: string[], expectedCidr: string}>
     */
    public function dataProviderForSummarize(): array
    {
        return [
            'two contiguous /24s summarize to /23' => [
                'inputCidrs' => ['192.168.0.0/24', '192.168.1.0/24'],
                'expectedCidr' => '192.168.0.0/23',
            ],
            'non-contiguous /24s need larger summary' => [
                'inputCidrs' => ['192.168.0.0/24', '192.168.2.0/24'],
                'expectedCidr' => '192.168.0.0/22',
            ],
            'sparse IPs need /24 to cover' => [
                'inputCidrs' => ['10.0.0.0/32', '10.0.0.255/32'],
                'expectedCidr' => '10.0.0.0/24',
            ],
            'single subnet returns unchanged' => [
                'inputCidrs' => ['192.168.1.0/24'],
                'expectedCidr' => '192.168.1.0/24',
            ],
            'two /32s summarize to /31' => [
                'inputCidrs' => ['10.0.0.0/32', '10.0.0.1/32'],
                'expectedCidr' => '10.0.0.0/31',
            ],
            'widely spaced subnets need large summary' => [
                'inputCidrs' => ['10.0.0.0/24', '10.255.255.0/24'],
                'expectedCidr' => '10.0.0.0/8',
            ],
            'overlapping subnets use largest' => [
                'inputCidrs' => ['192.168.0.0/23', '192.168.0.0/24'],
                'expectedCidr' => '192.168.0.0/23',
            ],
            'adjacent /31s summarize to /30' => [
                'inputCidrs' => ['10.0.0.0/31', '10.0.0.2/31'],
                'expectedCidr' => '10.0.0.0/30',
            ],
            'crossing octet boundaries' => [
                'inputCidrs' => ['192.167.255.0/24', '192.168.0.0/24'],
                'expectedCidr' => '192.160.0.0/12',
            ],
        ];
    }

    /**
     * @test
     */
    public function summarizeThrowsExceptionForEmptyArray(): void
    {
        // Given
        $subnets = [];

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot summarize empty subnet array');

        // When
        SubnetCalculatorFactory::summarize($subnets);
    }

    /**
     * @test
     */
    public function summarizeThrowsExceptionWhenPrefixZeroRequired(): void
    {
        // Given - Subnets spanning entire IP space require /0 which is not supported
        $subnets = [
            SubnetCalculatorFactory::fromCidr('0.0.0.0/32'),
            SubnetCalculatorFactory::fromCidr('255.255.255.255/32'),
        ];

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot summarize: result would require /0 which is not a valid network size');

        // When
        SubnetCalculatorFactory::summarize($subnets);
    }

    /* ************************** *
     * Edge case tests
     * ************************** */

    /**
     * @test
     */
    public function aggregateHandlesHighAddressRange(): void
    {
        // Given - High address range close to 255.255.255.255
        $subnets = [
            SubnetCalculatorFactory::fromCidr('255.255.254.0/24'),
            SubnetCalculatorFactory::fromCidr('255.255.255.0/24'),
        ];

        // When
        $result = SubnetCalculatorFactory::aggregate($subnets);

        // Then
        $this->assertCount(1, $result);
        $this->assertSame('255.255.254.0', $result[0]->getNetworkPortion());
        $this->assertSame(23, $result[0]->getNetworkSize());
    }

    /**
     * @test
     */
    public function aggregateHandlesLowAddressRange(): void
    {
        // Given - Low address range starting at 0.0.0.0
        $subnets = [
            SubnetCalculatorFactory::fromCidr('0.0.0.0/24'),
            SubnetCalculatorFactory::fromCidr('0.0.1.0/24'),
        ];

        // When
        $result = SubnetCalculatorFactory::aggregate($subnets);

        // Then
        $this->assertCount(1, $result);
        $this->assertSame('0.0.0.0', $result[0]->getNetworkPortion());
        $this->assertSame(23, $result[0]->getNetworkSize());
    }

    /**
     * @test
     */
    public function aggregateNormalizesInputToNetworkAddresses(): void
    {
        // Given - Subnets specified with non-network IPs
        $subnets = [
            new SubnetCalculator('192.168.0.100', 24),
            new SubnetCalculator('192.168.1.200', 24),
        ];

        // When
        $result = SubnetCalculatorFactory::aggregate($subnets);

        // Then
        $this->assertCount(1, $result);
        $this->assertSame('192.168.0.0', $result[0]->getNetworkPortion());
        $this->assertSame(23, $result[0]->getNetworkSize());
    }

    /**
     * @test
     */
    public function summarizeNormalizesInputToNetworkAddresses(): void
    {
        // Given - Subnets specified with non-network IPs
        $subnets = [
            new SubnetCalculator('10.0.0.50', 24),
            new SubnetCalculator('10.0.2.100', 24),
        ];

        // When
        $result = SubnetCalculatorFactory::summarize($subnets);

        // Then
        $this->assertSame('10.0.0.0', $result->getNetworkPortion());
        $this->assertSame(22, $result->getNetworkSize());
    }

    /**
     * @test
     */
    public function aggregateManySmallSubnets(): void
    {
        // Given - 16 /28 subnets that should aggregate to a /24
        $subnets = [];
        for ($i = 0; $i < 16; $i++) {
            $subnets[] = SubnetCalculatorFactory::fromCidr('10.1.1.' . ($i * 16) . '/28');
        }

        // When
        $result = SubnetCalculatorFactory::aggregate($subnets);

        // Then
        $this->assertCount(1, $result);
        $this->assertSame('10.1.1.0', $result[0]->getNetworkPortion());
        $this->assertSame(24, $result[0]->getNetworkSize());
    }

    /**
     * @test
     */
    public function aggregateAll256Host32sToSlash24(): void
    {
        // Given - All 256 /32 addresses in a /24 range
        $subnets = [];
        for ($i = 0; $i < 256; $i++) {
            $subnets[] = SubnetCalculatorFactory::fromCidr('172.16.5.' . $i . '/32');
        }

        // When
        $result = SubnetCalculatorFactory::aggregate($subnets);

        // Then
        $this->assertCount(1, $result);
        $this->assertSame('172.16.5.0', $result[0]->getNetworkPortion());
        $this->assertSame(24, $result[0]->getNetworkSize());
    }
}
