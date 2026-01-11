<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4;

class SubnetCalculatorIpFormatsTest extends \PHPUnit\Framework\TestCase
{
    /** @var IPv4\SubnetCalculator */
    private $sub;

    /**
     * Set up test SubnetCalculator
     */
    public function setUp(): void
    {
        $this->sub = new IPv4\SubnetCalculator('192.168.112.203', 23);
    }
    /**
     * @test         getIPAddressQuads
     * @dataProvider dataProviderForIpAddressQuads
     * @param        string   $ip_address
     * @param        string[] $expected_quads
     */
    public function testGetIPAddressQuads(string $ip_address, array $expected_quads): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, 24);

        // When
        $quads = $sub->getIPAddressQuads();

        // Then
        $this->assertSame($expected_quads, $quads);
    }

    /**
     * @return array[] [ip_address, quads]
     */
    public function dataProviderForIpAddressQuads(): array
    {
        return [
            ['192.168.112.203', ['192', '168', '112', '203']],
            ['56.5.145.126', ['56', '5', '145', '126']],
            ['128.0.0.0', ['128', '0', '0', '0']],
        ];
    }

    /**
     * @test         getIPAddressHex
     * @dataProvider dataProviderForIpAddressHex
     * @param        string $ip_address
     * @param        string $expected_hex
     */
    public function testGetIPAddressHex(string $ip_address, string $expected_hex): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, 24);

        // When
        $hex = $sub->getIPAddressHex();

        // Then
        $this->assertSame($expected_hex, $hex);
    }

    /**
     * @return string[][] [ip_address, hex]
     */
    public function dataProviderForIpAddressHex(): array
    {
        return [
            ['192.168.112.203', 'C0A870CB'],
            ['56.5.145.126', '3805917E'],
            ['128.0.0.0', '80000000'],
        ];
    }

    /**
     * @test         getIPAddressBinary
     * @dataProvider dataProviderForIpAddressBinary
     * @param        string $ip_address
     * @param        string $expected_binary
     */
    public function testGetIPAddressBinary(string $ip_address, string $expected_binary): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, 24);

        // When
        $binary = $sub->getIPAddressBinary();

        // Then
        $this->assertSame($expected_binary, $binary);
    }

    /**
     * @return string[][] [ip_address, binary]
     */
    public function dataProviderForIpAddressBinary(): array
    {
        return [
            ['192.168.112.203', '11000000101010000111000011001011'],
            ['56.5.145.126', '00111000000001011001000101111110'],
            ['128.0.0.0', '10000000000000000000000000000000'],
        ];
    }

    /**
     * @test         getIPAddressInteger
     * @dataProvider dataProviderForIpAddressInteger
     * @param        string $ip_address
     * @param        int    $expected_integer
     */
    public function testGetIPAddressInteger(string $ip_address, int $expected_integer): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, 24);

        // When
        $integer = $sub->getIPAddressInteger();

        // Then
        $this->assertSame($expected_integer, $integer);
    }

    /**
     * @return string[][] [ip_address, int]
     */
    public function dataProviderForIpAddressInteger(): array
    {
        return [
            ['192.168.112.203', 3232264395],
            ['56.5.145.126', 939889022],
            ['128.0.0.0', 2147483648],
        ];
    }
}
