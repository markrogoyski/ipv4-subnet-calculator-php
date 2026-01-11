<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4;

class SubnetCalculatorHostTest extends \PHPUnit\Framework\TestCase
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
     * @test         getAddressableHostRange returns the lower and upper addressable hosts
     * @dataProvider dataProviderForAddressableHostRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $expected_minHost
     * @param        string $expected_maxHost
     */
    public function testGetAddressableHostRange(string $ip_address, int $network_size, string $expected_minHost, string $expected_maxHost): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $minHost = $sub->getAddressableHostRange()[0];
        $maxHost = $sub->getAddressableHostRange()[1];

        // Then
        $this->assertEquals($expected_minHost, $minHost);
        $this->assertEquals($expected_maxHost, $maxHost);
    }

    /**
     * @test         getMinHost returns the lower addressable host
     * @dataProvider dataProviderForAddressableHostRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $expected_minHost
     */
    public function testGetMinHost(string $ip_address, int $network_size, string $expected_minHost): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $minHost = $sub->getMinHost();

        // Then
        $this->assertEquals($expected_minHost, $minHost);
    }

    /**
     * @test         getMaxHost returns the upper addressable host
     * @dataProvider dataProviderForAddressableHostRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $_
     * @param        string $expected_maxHost
     */
    public function testGetMaxHost(string $ip_address, int $network_size, string $_, string $expected_maxHost): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $maxHost = $sub->getMaxHost();

        // Then
        $this->assertEquals($expected_maxHost, $maxHost);
    }

    /**
     * @return array[] [ip_address, network_size, minHost, maxHost]
     */
    public function dataProviderForAddressableHostRange(): array
    {
        return [
            ['192.168.112.203', 1, '128.0.0.1', '255.255.255.254'],
            ['192.168.112.203', 2, '192.0.0.1', '255.255.255.254'],
            ['192.168.112.203', 3, '192.0.0.1', '223.255.255.254'],
            ['192.168.112.203', 4, '192.0.0.1', '207.255.255.254'],
            ['192.168.112.203', 5, '192.0.0.1', '199.255.255.254'],
            ['192.168.112.203', 6, '192.0.0.1', '195.255.255.254'],
            ['192.168.112.203', 7, '192.0.0.1', '193.255.255.254'],
            ['192.168.112.203', 8, '192.0.0.1', '192.255.255.254'],
            ['192.168.112.203', 9, '192.128.0.1', '192.255.255.254'],
            ['192.168.112.203', 10, '192.128.0.1', '192.191.255.254'],
            ['192.168.112.203', 11, '192.160.0.1', '192.191.255.254'],
            ['192.168.112.203', 12, '192.160.0.1', '192.175.255.254'],
            ['192.168.112.203', 13, '192.168.0.1', '192.175.255.254'],
            ['192.168.112.203', 14, '192.168.0.1', '192.171.255.254'],
            ['192.168.112.203', 15, '192.168.0.1', '192.169.255.254'],
            ['192.168.112.203', 16, '192.168.0.1', '192.168.255.254'],
            ['192.168.112.203', 17, '192.168.0.1', '192.168.127.254'],
            ['192.168.112.203', 18, '192.168.64.1', '192.168.127.254'],
            ['192.168.112.203', 19, '192.168.96.1', '192.168.127.254'],
            ['192.168.112.203', 20, '192.168.112.1', '192.168.127.254'],
            ['192.168.112.203', 21, '192.168.112.1', '192.168.119.254'],
            ['192.168.112.203', 22, '192.168.112.1', '192.168.115.254'],
            ['192.168.112.203', 23, '192.168.112.1', '192.168.113.254'],
            ['192.168.112.203', 24, '192.168.112.1', '192.168.112.254'],
            ['192.168.112.203', 25, '192.168.112.129', '192.168.112.254'],
            ['192.168.112.203', 26, '192.168.112.193', '192.168.112.254'],
            ['192.168.112.203', 27, '192.168.112.193', '192.168.112.222'],
            ['192.168.112.203', 28, '192.168.112.193', '192.168.112.206'],
            ['192.168.112.203', 29, '192.168.112.201', '192.168.112.206'],
            ['192.168.112.203', 30, '192.168.112.201', '192.168.112.202'],
            ['192.168.112.203', 31, '192.168.112.202', '192.168.112.203'],
            ['192.168.112.203', 32, '192.168.112.203', '192.168.112.203'],
            // /31 edge cases: even IP input (IP is network base)
            ['10.0.0.0', 31, '10.0.0.0', '10.0.0.1'],
            ['10.0.0.2', 31, '10.0.0.2', '10.0.0.3'],
            // /31 edge cases: odd IP input (IP is broadcast)
            ['10.0.0.1', 31, '10.0.0.0', '10.0.0.1'],
            ['10.0.0.3', 31, '10.0.0.2', '10.0.0.3'],
            // /31 edge case: crossing octet boundary
            ['192.168.0.255', 31, '192.168.0.254', '192.168.0.255'],
            ['192.168.1.0', 31, '192.168.1.0', '192.168.1.1'],
        ];
    }

    /**
     * @test         getMinHostQuads returns an array of quads
     * @dataProvider dataProviderForGetMinHostQuads
     * @param string $ip_address
     * @param int    $network_size
     * @param int[]  $expected_quads
     */
    public function testGetMinHostQuads(string $ip_address, int $network_size, array $expected_quads): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $quads = $sub->getMinHostQuads();

        // Then
        $this->assertEquals($expected_quads, $quads);
    }

    /**
     * @return array[]
     */
    public function dataProviderForGetMinHostQuads(): array
    {
        return [
            ['192.168.112.203', 1, [128, 0, 0, 1]],
            ['192.168.112.203', 2, [192, 0, 0, 1]],
            ['192.168.112.203', 24, [192, 168, 112, 1]],
            ['192.168.112.203', 25, [192, 168, 112, 129]],
            ['192.168.112.203', 26, [192, 168, 112, 193]],
            ['192.168.112.203', 27, [192, 168, 112, 193]],
            ['192.168.112.203', 28, [192, 168, 112, 193]],
            ['192.168.112.203', 29, [192, 168, 112, 201]],
            ['192.168.112.203', 30, [192, 168, 112, 201]],
            ['192.168.112.203', 31, [192, 168, 112, 202]],
            ['192.168.112.203', 32, [192, 168, 112, 203]],
            // /31 edge cases: even IP input (IP is network base)
            ['10.0.0.0', 31, [10, 0, 0, 0]],
            ['10.0.0.2', 31, [10, 0, 0, 2]],
            // /31 edge cases: odd IP input (IP is broadcast)
            ['10.0.0.1', 31, [10, 0, 0, 0]],
            ['10.0.0.3', 31, [10, 0, 0, 2]],
            // /31 edge case: crossing octet boundary
            ['192.168.0.255', 31, [192, 168, 0, 254]],
            ['192.168.1.0', 31, [192, 168, 1, 0]],
        ];
    }

    /**
     * @test         getMaxHostQuads returns an array of quads
     * @dataProvider dataProviderForGetMaxHostQuads
     * @param string $ip_address
     * @param int    $network_size
     * @param int[]  $expected_quads
     */
    public function testGetMaxHostQuads(string $ip_address, int $network_size, array $expected_quads): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $quads = $sub->getMaxHostQuads();

        // Then
        $this->assertEquals($expected_quads, $quads);
    }

    /**
     * @return array[]
     */
    public function dataProviderForGetMaxHostQuads(): array
    {
        return [
            ['192.168.112.203', 1, [255, 255, 255, 254]],
            ['192.168.112.203', 2, [255, 255, 255, 254]],
            ['192.168.112.203', 24, [192, 168, 112, 254]],
            ['192.168.112.203', 25, [192, 168, 112, 254]],
            ['192.168.112.203', 26, [192, 168, 112, 254]],
            ['192.168.112.203', 27, [192, 168, 112, 222]],
            ['192.168.112.203', 28, [192, 168, 112, 206]],
            ['192.168.112.203', 29, [192, 168, 112, 206]],
            ['192.168.112.203', 30, [192, 168, 112, 202]],
            ['192.168.112.203', 31, [192, 168, 112, 203]],
            ['192.168.112.203', 32, [192, 168, 112, 203]],
            // /31 edge cases: even IP input (IP is network base)
            ['10.0.0.0', 31, [10, 0, 0, 1]],
            ['10.0.0.2', 31, [10, 0, 0, 3]],
            // /31 edge cases: odd IP input (IP is broadcast)
            ['10.0.0.1', 31, [10, 0, 0, 1]],
            ['10.0.0.3', 31, [10, 0, 0, 3]],
            // /31 edge case: crossing octet boundary
            ['192.168.0.255', 31, [192, 168, 0, 255]],
            ['192.168.1.0', 31, [192, 168, 1, 1]],
        ];
    }

    /**
     * @test         getMinHostHex returns a string of hex
     * @dataProvider dataProviderForGetMinHostHex
     * @param string $ip_address
     * @param int    $network_size
     * @param string $expected_hex
     */
    public function testGetMinHostHex(string $ip_address, int $network_size, string $expected_hex): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $hex = $sub->getMinHostHex();

        // Then
        $this->assertEquals($expected_hex, $hex);
    }

    /**
     * @return array[]
     */
    public function dataProviderForGetMinHostHex(): array
    {
        return [
            ['192.168.112.203', 1, '80000001'],
            ['192.168.112.203', 2, 'C0000001'],
            ['192.168.112.203', 24, 'C0A87001'],
            ['192.168.112.203', 25, 'C0A87081'],
            ['192.168.112.203', 26, 'C0A870C1'],
            ['192.168.112.203', 27, 'C0A870C1'],
            ['192.168.112.203', 28, 'C0A870C1'],
            ['192.168.112.203', 29, 'C0A870C9'],
            ['192.168.112.203', 30, 'C0A870C9'],
            ['192.168.112.203', 31, 'C0A870CA'],
            ['192.168.112.203', 32, 'C0A870CB'],
            // /31 edge cases: even IP input (IP is network base)
            ['10.0.0.0', 31, '0A000000'],
            ['10.0.0.2', 31, '0A000002'],
            // /31 edge cases: odd IP input (IP is broadcast)
            ['10.0.0.1', 31, '0A000000'],
            ['10.0.0.3', 31, '0A000002'],
            // /31 edge case: crossing octet boundary
            ['192.168.0.255', 31, 'C0A800FE'],
            ['192.168.1.0', 31, 'C0A80100'],
        ];
    }

    /**
     * @test         getMaxHostHex returns a string of hex
     * @dataProvider dataProviderForGetMaxHostHex
     * @param string $ip_address
     * @param int    $network_size
     * @param string $expected_hex
     */
    public function testGetMaxHostHex(string $ip_address, int $network_size, string $expected_hex): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $hex = $sub->getMaxHostHex();

        // Then
        $this->assertEquals($expected_hex, $hex);
    }

    /**
     * @return array[]
     */
    public function dataProviderForGetMaxHostHex(): array
    {
        return [
            ['192.168.112.203', 1, 'FFFFFFFE'],
            ['192.168.112.203', 2, 'FFFFFFFE'],
            ['192.168.112.203', 24, 'C0A870FE'],
            ['192.168.112.203', 25, 'C0A870FE'],
            ['192.168.112.203', 26, 'C0A870FE'],
            ['192.168.112.203', 27, 'C0A870DE'],
            ['192.168.112.203', 28, 'C0A870CE'],
            ['192.168.112.203', 29, 'C0A870CE'],
            ['192.168.112.203', 30, 'C0A870CA'],
            ['192.168.112.203', 31, 'C0A870CB'],
            ['192.168.112.203', 32, 'C0A870CB'],
            // /31 edge cases: even IP input (IP is network base)
            ['10.0.0.0', 31, '0A000001'],
            ['10.0.0.2', 31, '0A000003'],
            // /31 edge cases: odd IP input (IP is broadcast)
            ['10.0.0.1', 31, '0A000001'],
            ['10.0.0.3', 31, '0A000003'],
            // /31 edge case: crossing octet boundary
            ['192.168.0.255', 31, 'C0A800FF'],
            ['192.168.1.0', 31, 'C0A80101'],
        ];
    }

    /**
     * @test         getMinHostBinary returns a string of binary
     * @dataProvider dataProviderForGetMinHostBinary
     * @param string $ip_address
     * @param int    $network_size
     * @param string $expected_binary
     */
    public function testGetMinHostBinary(string $ip_address, int $network_size, string $expected_binary): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $binary = $sub->getMinHostBinary();

        // Then
        $this->assertEquals($expected_binary, $binary);
    }

    /**
     * @return array[]
     */
    public function dataProviderForGetMinHostBinary(): array
    {
        return [
            ['192.168.112.203', 1, '10000000000000000000000000000001'],
            ['192.168.112.203', 2, '11000000000000000000000000000001'],
            ['192.168.112.203', 24, '11000000101010000111000000000001'],
            ['192.168.112.203', 25, '11000000101010000111000010000001'],
            ['192.168.112.203', 26, '11000000101010000111000011000001'],
            ['192.168.112.203', 27, '11000000101010000111000011000001'],
            ['192.168.112.203', 28, '11000000101010000111000011000001'],
            ['192.168.112.203', 29, '11000000101010000111000011001001'],
            ['192.168.112.203', 30, '11000000101010000111000011001001'],
            ['192.168.112.203', 31, '11000000101010000111000011001010'],
            ['192.168.112.203', 32, '11000000101010000111000011001011'],
            // /31 edge cases: even IP input (IP is network base)
            ['10.0.0.0', 31, '00001010000000000000000000000000'],
            ['10.0.0.2', 31, '00001010000000000000000000000010'],
            // /31 edge cases: odd IP input (IP is broadcast)
            ['10.0.0.1', 31, '00001010000000000000000000000000'],
            ['10.0.0.3', 31, '00001010000000000000000000000010'],
            // /31 edge case: crossing octet boundary
            ['192.168.0.255', 31, '11000000101010000000000011111110'],
            ['192.168.1.0', 31, '11000000101010000000000100000000'],
        ];
    }

    /**
     * @test         getMaxHostBinary returns a string of binary
     * @dataProvider dataProviderForGetMaxHostBinary
     * @param string $ip_address
     * @param int    $network_size
     * @param string $expected_binary
     */
    public function testGetMaxHostBinary(string $ip_address, int $network_size, string $expected_binary): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $binary = $sub->getMaxHostBinary();

        // Then
        $this->assertEquals($expected_binary, $binary);
    }

    /**
     * @return array[]
     */
    public function dataProviderForGetMaxHostBinary(): array
    {
        return [
            ['192.168.112.203', 1, '11111111111111111111111111111110'],
            ['192.168.112.203', 2, '11111111111111111111111111111110'],
            ['192.168.112.203', 24, '11000000101010000111000011111110'],
            ['192.168.112.203', 25, '11000000101010000111000011111110'],
            ['192.168.112.203', 26, '11000000101010000111000011111110'],
            ['192.168.112.203', 27, '11000000101010000111000011011110'],
            ['192.168.112.203', 28, '11000000101010000111000011001110'],
            ['192.168.112.203', 29, '11000000101010000111000011001110'],
            ['192.168.112.203', 30, '11000000101010000111000011001010'],
            ['192.168.112.203', 31, '11000000101010000111000011001011'],
            ['192.168.112.203', 32, '11000000101010000111000011001011'],
            // /31 edge cases: even IP input (IP is network base)
            ['10.0.0.0', 31, '00001010000000000000000000000001'],
            ['10.0.0.2', 31, '00001010000000000000000000000011'],
            // /31 edge cases: odd IP input (IP is broadcast)
            ['10.0.0.1', 31, '00001010000000000000000000000001'],
            ['10.0.0.3', 31, '00001010000000000000000000000011'],
            // /31 edge case: crossing octet boundary
            ['192.168.0.255', 31, '11000000101010000000000011111111'],
            ['192.168.1.0', 31, '11000000101010000000000100000001'],
        ];
    }

    /**
     * @test         getMinHostInteger returns an integer
     * @dataProvider dataProviderForGetMinHostInteger
     * @param string $ip_address
     * @param int    $network_size
     * @param int    $expected_integer
     */
    public function testGetMinHostInteger(string $ip_address, int $network_size, int $expected_integer): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $integer = $sub->getMinHostInteger();

        // Then
        $this->assertEquals($expected_integer, $integer);
    }

    /**
     * @return array[]
     */
    public function dataProviderForGetMinHostInteger(): array
    {
        return [
            ['192.168.112.203', 1, 2147483649],
            ['192.168.112.203', 2, 3221225473],
            ['192.168.112.203', 24, 3232264193],
            ['192.168.112.203', 25, 3232264321],
            ['192.168.112.203', 26, 3232264385],
            ['192.168.112.203', 27, 3232264385],
            ['192.168.112.203', 28, 3232264385],
            ['192.168.112.203', 29, 3232264393],
            ['192.168.112.203', 30, 3232264393],
            ['192.168.112.203', 31, 3232264394],
            ['192.168.112.203', 32, 3232264395],
            // /31 edge cases: even IP input (IP is network base)
            ['10.0.0.0', 31, 167772160],
            ['10.0.0.2', 31, 167772162],
            // /31 edge cases: odd IP input (IP is broadcast)
            ['10.0.0.1', 31, 167772160],
            ['10.0.0.3', 31, 167772162],
            // /31 edge case: crossing octet boundary
            ['192.168.0.255', 31, 3232235774],
            ['192.168.1.0', 31, 3232235776],
        ];
    }

    /**
     * @test         getMaxHostInteger returns an integer
     * @dataProvider dataProviderForGetMaxHostInteger
     * @param string $ip_address
     * @param int    $network_size
     * @param int    $expected_integer
     */
    public function testGetMaxHostInteger(string $ip_address, int $network_size, int $expected_integer): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $integer = $sub->getMaxHostInteger();

        // Then
        $this->assertEquals($expected_integer, $integer);
    }

    /**
     * @return array[]
     */
    public function dataProviderForGetMaxHostInteger(): array
    {
        return [
            ['192.168.112.203', 1, 4294967294],
            ['192.168.112.203', 2, 4294967294],
            ['192.168.112.203', 24, 3232264446],
            ['192.168.112.203', 25, 3232264446],
            ['192.168.112.203', 26, 3232264446],
            ['192.168.112.203', 27, 3232264414],
            ['192.168.112.203', 28, 3232264398],
            ['192.168.112.203', 29, 3232264398],
            ['192.168.112.203', 30, 3232264394],
            ['192.168.112.203', 31, 3232264395],
            ['192.168.112.203', 32, 3232264395],
            // /31 edge cases: even IP input (IP is network base)
            ['10.0.0.0', 31, 167772161],
            ['10.0.0.2', 31, 167772163],
            // /31 edge cases: odd IP input (IP is broadcast)
            ['10.0.0.1', 31, 167772161],
            ['10.0.0.3', 31, 167772163],
            // /31 edge case: crossing octet boundary
            ['192.168.0.255', 31, 3232235775],
            ['192.168.1.0', 31, 3232235777],
        ];
    }

    /**
     * @test         getHostPortion
     * @dataProvider dataProviderForHostPortion
     * @param        string   $ip_address
     * @param        int      $network_size
     * @param        string   $host
     * @param        string[] $quads
     * @param        string   $hex
     * @param        string   $binary
     * @param        int      $integer
     */
    public function testGetHostPortion(string $ip_address, int $network_size, string $host, array $quads, string $hex, string $binary, int $integer): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // Then
        $this->assertSame($host, $sub->getHostPortion());
        $this->assertSame($quads, $sub->getHostPortionQuads());
        $this->assertSame($hex, $sub->getHostPortionHex());
        $this->assertSame($binary, $sub->getHostPortionBinary());
        $this->assertSame($integer, $sub->getHostPortionInteger());
    }

    /**
     * @return array[] [ip_address, network_size, host, quads, hex, binary]
     */
    public function dataProviderForHostPortion(): array
    {
        return [
            ['192.168.112.203', 1, '64.168.112.203', ['64', '168', '112', '203'], '40A870CB', '01000000101010000111000011001011', 1084780747],
            ['192.168.84.233', 2, '0.168.84.233', ['0', '168', '84', '233'], '00A854E9', '00000000101010000101010011101001', 11031785],
            ['10.10.122.113', 3, '10.10.122.113', ['10', '10', '122', '113'], '0A0A7A71', '00001010000010100111101001110001', 168458865],
            ['255.255.255.255', 4, '15.255.255.255', ['15', '255', '255', '255'], '0FFFFFFF', '00001111111111111111111111111111', 268435455],
            ['192.168.112.207', 5, '0.168.112.207', ['0', '168', '112', '207'], '00A870CF', '00000000101010000111000011001111', 11038927],
            ['192.128.0.1', 6, '0.128.0.1', ['0', '128', '0', '1'], '00800001', '00000000100000000000000000000001', 8388609],
            ['128.0.0.0', 7, '0.0.0.0', ['0', '0', '0', '0'], '00000000', '00000000000000000000000000000000', 0],
            ['235.90.125.222', 8, '0.90.125.222', ['0', '90', '125', '222'], '005A7DDE', '00000000010110100111110111011110', 5930462],
            ['208.153.158.185', 9, '0.25.158.185', ['0', '25', '158', '185'], '00199EB9', '00000000000110011001111010111001', 1679033],
            ['99.107.189.17', 10, '0.43.189.17', ['0', '43', '189', '17'], '002BBD11', '00000000001010111011110100010001', 2866449],
            ['233.126.142.167', 11, '0.30.142.167', ['0', '30', '142', '167'], '001E8EA7', '00000000000111101000111010100111', 2002599],
            ['205.39.43.86', 12, '0.7.43.86', ['0', '7', '43', '86'], '00072B56', '00000000000001110010101101010110', 469846],
            ['158.114.74.115', 13, '0.2.74.115', ['0', '2', '74', '115'], '00024A73', '00000000000000100100101001110011', 150131],
            ['127.132.3.128', 14, '0.0.3.128', ['0', '0', '3', '128'], '00000380', '00000000000000000000001110000000', 896],
            ['243.73.87.101', 15, '0.1.87.101', ['0', '1', '87', '101'], '00015765', '00000000000000010101011101100101',87909 ],
            ['176.103.67.129', 16, '0.0.67.129', ['0', '0', '67', '129'], '00004381', '00000000000000000100001110000001', 17281],
            ['190.113.28.0', 17, '0.0.28.0', ['0', '0', '28', '0'], '00001C00', '00000000000000000001110000000000', 7168],
            ['204.243.103.224', 18, '0.0.39.224', ['0', '0', '39', '224'], '000027E0', '00000000000000000010011111100000', 10208],
            ['203.247.20.148', 19, '0.0.20.148', ['0', '0', '20', '148'], '00001494', '00000000000000000001010010010100', 5268],
            ['15.254.55.4', 20, '0.0.7.4', ['0', '0', '7', '4'], '00000704', '00000000000000000000011100000100', 1796],
            ['96.245.55.29', 21, '0.0.7.29', ['0', '0', '7', '29'], '0000071D', '00000000000000000000011100011101', 1821],
            ['88.102.195.7', 22, '0.0.3.7', ['0', '0', '3', '7'], '00000307', '00000000000000000000001100000111', 775],
            ['144.60.195.68', 23, '0.0.1.68', ['0', '0', '1', '68'], '00000144', '00000000000000000000000101000100', 324],
            ['189.191.237.105', 24, '0.0.0.105', ['0', '0', '0', '105'], '00000069', '00000000000000000000000001101001', 105],
            ['98.79.29.150', 25, '0.0.0.22', ['0', '0', '0', '22'], '00000016', '00000000000000000000000000010110', 22],
            ['56.5.145.126', 26, '0.0.0.62', ['0', '0', '0', '62'], '0000003E', '00000000000000000000000000111110', 62],
            ['80.170.127.173', 27, '0.0.0.13', ['0', '0', '0', '13'], '0000000D', '00000000000000000000000000001101', 13],
            ['92.123.10.117', 28, '0.0.0.5', ['0', '0', '0', '5'], '00000005', '00000000000000000000000000000101', 5],
            ['88.52.155.198', 29, '0.0.0.6', ['0', '0', '0', '6'], '00000006', '00000000000000000000000000000110', 6],
            ['230.233.123.40', 30, '0.0.0.0', ['0', '0', '0', '0'], '00000000', '00000000000000000000000000000000', 0],
            ['254.17.211.42', 31, '0.0.0.0', ['0', '0', '0', '0'], '00000000', '00000000000000000000000000000000', 0],
            ['57.51.231.108', 32, '0.0.0.0', ['0', '0', '0', '0'], '00000000', '00000000000000000000000000000000', 0],
        ];
    }
}
