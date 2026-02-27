<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class SubnetIpFormatsTest extends \PHPUnit\Framework\TestCase
{
    /** @var Subnet */
    private $sub;

    /**
     * Set up test Subnet
     */
    public function setUp(): void
    {
        $this->sub = new Subnet('192.168.112.203', 23);
    }
    /**
     * getIPAddressQuads
     * @param        string   $ip_address
     * @param        string[] $expected_quads
     */
    #[Test]
    #[DataProvider('dataProviderForIpAddressQuads')]
    public function testGetIPAddressQuads(string $ip_address, array $expected_quads): void
    {
        // Given
        $sub = new Subnet($ip_address, 24);

        // When
        $quads = $sub->ipAddress()->asArray();

        // Then
        $this->assertSame($expected_quads, $quads);
    }

    /**
     * @return array[] [ip_address, quads]
     */
    public static function dataProviderForIpAddressQuads(): array
    {
        return [
            ['192.168.112.203', ['192', '168', '112', '203']],
            ['56.5.145.126', ['56', '5', '145', '126']],
            ['128.0.0.0', ['128', '0', '0', '0']],
        ];
    }

    /**
     * getIPAddressHex
     * @param        string $ip_address
     * @param        string $expected_hex
     */
    #[Test]
    #[DataProvider('dataProviderForIpAddressHex')]
    public function testGetIPAddressHex(string $ip_address, string $expected_hex): void
    {
        // Given
        $sub = new Subnet($ip_address, 24);

        // When
        $hex = $sub->ipAddress()->asHex();

        // Then
        $this->assertSame($expected_hex, $hex);
    }

    /**
     * @return string[][] [ip_address, hex]
     */
    public static function dataProviderForIpAddressHex(): array
    {
        return [
            ['192.168.112.203', 'C0A870CB'],
            ['56.5.145.126', '3805917E'],
            ['128.0.0.0', '80000000'],
        ];
    }

    /**
     * getIPAddressBinary
     * @param        string $ip_address
     * @param        string $expected_binary
     */
    #[Test]
    #[DataProvider('dataProviderForIpAddressBinary')]
    public function testGetIPAddressBinary(string $ip_address, string $expected_binary): void
    {
        // Given
        $sub = new Subnet($ip_address, 24);

        // When
        $binary = $sub->ipAddress()->asBinary();

        // Then
        $this->assertSame($expected_binary, $binary);
    }

    /**
     * @return string[][] [ip_address, binary]
     */
    public static function dataProviderForIpAddressBinary(): array
    {
        return [
            ['192.168.112.203', '11000000101010000111000011001011'],
            ['56.5.145.126', '00111000000001011001000101111110'],
            ['128.0.0.0', '10000000000000000000000000000000'],
        ];
    }

    /**
     * getIPAddressInteger
     * @param        string $ip_address
     * @param        int    $expected_integer
     */
    #[Test]
    #[DataProvider('dataProviderForIpAddressInteger')]
    public function testGetIPAddressInteger(string $ip_address, int $expected_integer): void
    {
        // Given
        $sub = new Subnet($ip_address, 24);

        // When
        $integer = $sub->ipAddress()->asInteger();

        // Then
        $this->assertSame($expected_integer, $integer);
    }

    /**
     * @return string[][] [ip_address, int]
     */
    public static function dataProviderForIpAddressInteger(): array
    {
        return [
            ['192.168.112.203', 3232264395],
            ['56.5.145.126', 939889022],
            ['128.0.0.0', 2147483648],
        ];
    }
}
