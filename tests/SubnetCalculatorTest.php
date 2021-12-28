<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4;

class SubnetCalculatorTest extends \PHPUnit\Framework\TestCase
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
     * @test         getIPAddress
     * @dataProvider dataProviderForIpAddresses
     * @param        string $given_ip_address
     * @param        int    $network_size
     */
    public function testGetIpAddress(string $given_ip_address, int $network_size): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($given_ip_address, $network_size);

        // When
        $ip_address = $sub->getIPAddress();

        // Then
        $this->assertSame($given_ip_address, $ip_address);
    }

    /**
     * @test         getNetworkSize
     * @dataProvider dataProviderForIpAddresses
     * @param        string $ip_address
     * @param        int    $given_network_size
     */
    public function testGetNetworkSize(string $ip_address, int $given_network_size): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $given_network_size);

        // When
        $network_size = $sub->getNetworkSize();

        // Then
        $this->assertSame($given_network_size, $network_size);
    }

    /**
     * @return array[] [ip_address, network_size]
     */
    public function dataProviderForIpAddresses(): array
    {
        return [
            ['192.168.112.203', 1],
            ['192.168.84.233', 2],
            ['10.10.122.113', 3],
            ['255.255.255.255', 4],
            ['192.168.112.207', 5],
            ['192.128.0.1', 6],
            ['128.0.0.0', 7],
            ['235.90.125.222', 8],
            ['208.153.158.185', 9],
            ['99.107.189.17', 10],
            ['233.126.142.167', 11],
            ['205.39.43.86', 12],
            ['158.114.74.115', 13],
            ['127.132.3.128', 14],
            ['243.73.87.101', 15],
            ['176.103.67.129', 16],
            ['190.113.28.0', 17],
            ['204.243.103.224', 18],
            ['203.247.20.148', 19],
            ['15.254.55.4', 20],
            ['96.245.55.29', 21],
            ['88.102.195.7', 22],
            ['144.60.195.68', 23],
            ['189.191.237.105', 24],
            ['98.79.29.150', 25],
            ['56.5.145.126', 26],
            ['80.170.127.173', 27],
            ['92.123.10.117', 28],
            ['88.52.155.198', 29],
            ['230.233.123.40', 30],
            ['254.17.211.42', 31],
            ['57.51.231.108', 32],
        ];
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

    /**
     * @test         getNumberIpAddresses returns the number of IP addresses
     * @dataProvider dataProviderForNumberOfAddresses
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        int    $expected_number_addresses
     */
    public function testGetNumberIPAddresses(string $ip_address, int $network_size, int $expected_number_addresses): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $number_addresses = $sub->getNumberIPAddresses();

        // Then
        $this->assertEquals($expected_number_addresses, $number_addresses);
    }

    /**
     * @return array[] [ip_address, network_size, number_addresses]
     */
    public function dataProviderForNumberOfAddresses(): array
    {
        return [
            ['192.168.112.203', 1,  2147483648],
            ['192.168.112.203', 2, 1073741824],
            ['192.168.112.203', 3, 536870912],
            ['192.168.112.203', 4, 268435456],
            ['192.168.112.203', 5, 134217728],
            ['192.168.112.203', 6, 67108864],
            ['192.168.112.203', 7, 33554432],
            ['192.168.112.203', 8, 16777216],
            ['192.168.112.203', 9, 8388608],
            ['192.168.112.203', 10, 4194304],
            ['192.168.112.203', 11, 2097152],
            ['192.168.112.203', 12, 1048576],
            ['192.168.112.203', 13, 524288],
            ['192.168.112.203', 14, 262144],
            ['192.168.112.203', 15, 131072],
            ['192.168.112.203', 16, 65536],
            ['192.168.112.203', 17, 32768],
            ['192.168.112.203', 18, 16384],
            ['192.168.112.203', 19, 8192],
            ['192.168.112.203', 20, 4096],
            ['192.168.112.203', 21, 2048],
            ['192.168.112.203', 22, 1024],
            ['192.168.112.203', 23, 512],
            ['192.168.112.203', 24, 256],
            ['192.168.112.203', 25, 128],
            ['192.168.112.203', 26, 64],
            ['192.168.112.203', 27, 32],
            ['192.168.112.203', 28, 16],
            ['192.168.112.203', 29, 8],
            ['192.168.112.203', 30, 4],
            ['192.168.112.203', 31, 2],
            ['192.168.112.203', 32, 1],
        ];
    }

    /**
     * @test         getNumberAddressableHosts returns the number of IP addresses
     * @dataProvider dataProviderForNumberOfAddressableHosts
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        int    $expected_number_addressable_hosts
     */
    public function testGetNumberAddressableHosts(string $ip_address, int $network_size, int $expected_number_addressable_hosts): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $number_addressable_hosts = $sub->getNumberAddressableHosts();

        // Then
        $this->assertEquals($expected_number_addressable_hosts, $number_addressable_hosts);
    }

    /**
     * @return array[] [ip_address, network_size, number_addressable_hosts]
     */
    public function dataProviderForNumberOfAddressableHosts(): array
    {
        return [
            ['192.168.112.203', 1, 2147483646],
            ['192.168.112.203', 2, 1073741822],
            ['192.168.112.203', 3, 536870910],
            ['192.168.112.203', 4, 268435454],
            ['192.168.112.203', 5, 134217726],
            ['192.168.112.203', 6, 67108862],
            ['192.168.112.203', 7, 33554430],
            ['192.168.112.203', 8, 16777214],
            ['192.168.112.203', 9, 8388606],
            ['192.168.112.203', 10, 4194302],
            ['192.168.112.203', 11, 2097150],
            ['192.168.112.203', 12, 1048574],
            ['192.168.112.203', 13, 524286],
            ['192.168.112.203', 14, 262142],
            ['192.168.112.203', 15, 131070],
            ['192.168.112.203', 16, 65534],
            ['192.168.112.203', 17, 32766],
            ['192.168.112.203', 18, 16382],
            ['192.168.112.203', 19, 8190],
            ['192.168.112.203', 20, 4094],
            ['192.168.112.203', 21, 2046],
            ['192.168.112.203', 22, 1022],
            ['192.168.112.203', 23, 510],
            ['192.168.112.203', 24, 254],
            ['192.168.112.203', 25, 126],
            ['192.168.112.203', 26, 62],
            ['192.168.112.203', 27, 30],
            ['192.168.112.203', 28, 14],
            ['192.168.112.203', 29, 6],
            ['192.168.112.203', 30, 2],
            ['192.168.112.203', 31, 2],
            ['192.168.112.203', 32, 1],
        ];
    }

    /**
     * @test         getIpAddressRange returns the lower and upper IP addresses in the range
     * @dataProvider dataProviderForIpAddressRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $expected_lower_ip
     * @param        string $expected_upper_ip
     */
    public function testGetIpAddressRange(string $ip_address, int $network_size, string $expected_lower_ip, string $expected_upper_ip): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $lower_ip = $sub->getIPAddressRange()[0];
        $upper_ip = $sub->getIPAddressRange()[1];

        // Then
        $this->assertEquals($expected_lower_ip, $lower_ip);
        $this->assertEquals($expected_upper_ip, $upper_ip);
    }

    /**
     * @test         getNetworkPortion
     * @dataProvider dataProviderForIpAddressRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $expected_network_portion
     */
    public function testGetNetworkPortionLowerIp(string $ip_address, int $network_size, string $expected_network_portion): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $network_portion = $sub->getNetworkPortion();

        // Then
        $this->assertEquals($expected_network_portion, $network_portion);
    }

    /**
     * @return array[] [ip_address, network_size, lower_ip, upper_ip]
     */
    public function dataProviderForIpAddressRange(): array
    {
        return [
            ['192.168.112.203', 1, '128.0.0.0', '255.255.255.255'],
            ['192.168.112.203', 2, '192.0.0.0', '255.255.255.255'],
            ['192.168.112.203', 3, '192.0.0.0', '223.255.255.255'],
            ['192.168.112.203', 4, '192.0.0.0', '207.255.255.255'],
            ['192.168.112.203', 5, '192.0.0.0', '199.255.255.255'],
            ['192.168.112.203', 6, '192.0.0.0', '195.255.255.255'],
            ['192.168.112.203', 7, '192.0.0.0', '193.255.255.255'],
            ['192.168.112.203', 8, '192.0.0.0', '192.255.255.255'],
            ['192.168.112.203', 9, '192.128.0.0', '192.255.255.255'],
            ['192.168.112.203', 10, '192.128.0.0', '192.191.255.255'],
            ['192.168.112.203', 11, '192.160.0.0', '192.191.255.255'],
            ['192.168.112.203', 12, '192.160.0.0', '192.175.255.255'],
            ['192.168.112.203', 13, '192.168.0.0', '192.175.255.255'],
            ['192.168.112.203', 14, '192.168.0.0', '192.171.255.255'],
            ['192.168.112.203', 15, '192.168.0.0', '192.169.255.255'],
            ['192.168.112.203', 16, '192.168.0.0', '192.168.255.255'],
            ['192.168.112.203', 17, '192.168.0.0', '192.168.127.255'],
            ['192.168.112.203', 18, '192.168.64.0', '192.168.127.255'],
            ['192.168.112.203', 19, '192.168.96.0', '192.168.127.255'],
            ['192.168.112.203', 20, '192.168.112.0', '192.168.127.255'],
            ['192.168.112.203', 21, '192.168.112.0', '192.168.119.255'],
            ['192.168.112.203', 22, '192.168.112.0', '192.168.115.255'],
            ['192.168.112.203', 23, '192.168.112.0', '192.168.113.255'],
            ['192.168.112.203', 24, '192.168.112.0', '192.168.112.255'],
            ['192.168.112.203', 25, '192.168.112.128', '192.168.112.255'],
            ['192.168.112.203', 26, '192.168.112.192', '192.168.112.255'],
            ['192.168.112.203', 27, '192.168.112.192', '192.168.112.223'],
            ['192.168.112.203', 28, '192.168.112.192', '192.168.112.207'],
            ['192.168.112.203', 29, '192.168.112.200', '192.168.112.207'],
            ['192.168.112.203', 30, '192.168.112.200', '192.168.112.203'],
            ['192.168.112.203', 31, '192.168.112.202', '192.168.112.203'],
            ['192.168.112.203', 32, '192.168.112.203', '192.168.112.203'],
        ];
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
     * @test         getBroadcastAddress returns the broadcast address
     * @dataProvider dataProviderForBroadcastAddress
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $expected_broadcast_address
     */
    public function testGetBroadcastAddress(string $ip_address, int $network_size, string $expected_broadcast_address): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $broadcast_address = $sub->getBroadcastAddress();

        // Then
        $this->assertEquals($expected_broadcast_address, $broadcast_address);
    }

    /**
     * @return array[] [ip_address, network_size, broadcast_address]
     */
    public function dataProviderForBroadcastAddress(): array
    {
        return [
            ['192.168.112.203', 1, '255.255.255.255'],
            ['192.168.112.203', 2, '255.255.255.255'],
            ['192.168.112.203', 3, '223.255.255.255'],
            ['192.168.112.203', 4, '207.255.255.255'],
            ['192.168.112.203', 5, '199.255.255.255'],
            ['192.168.112.203', 6, '195.255.255.255'],
            ['192.168.112.203', 7, '193.255.255.255'],
            ['192.168.112.203', 8, '192.255.255.255'],
            ['192.168.112.203', 9, '192.255.255.255'],
            ['192.168.112.203', 10, '192.191.255.255'],
            ['192.168.112.203', 11, '192.191.255.255'],
            ['192.168.112.203', 12, '192.175.255.255'],
            ['192.168.112.203', 13, '192.175.255.255'],
            ['192.168.112.203', 14, '192.171.255.255'],
            ['192.168.112.203', 15, '192.169.255.255'],
            ['192.168.112.203', 16, '192.168.255.255'],
            ['192.168.112.203', 17, '192.168.127.255'],
            ['192.168.112.203', 18, '192.168.127.255'],
            ['192.168.112.203', 19, '192.168.127.255'],
            ['192.168.112.203', 20, '192.168.127.255'],
            ['192.168.112.203', 21, '192.168.119.255'],
            ['192.168.112.203', 22, '192.168.115.255'],
            ['192.168.112.203', 23, '192.168.113.255'],
            ['192.168.112.203', 24, '192.168.112.255'],
            ['192.168.112.203', 25, '192.168.112.255'],
            ['192.168.112.203', 26, '192.168.112.255'],
            ['192.168.112.203', 27, '192.168.112.223'],
            ['192.168.112.203', 28, '192.168.112.207'],
            ['192.168.112.203', 29, '192.168.112.207'],
            ['192.168.112.203', 30, '192.168.112.203'],
            ['192.168.112.203', 31, '192.168.112.203'],
            ['192.168.112.203', 32, '192.168.112.203'],
        ];
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
            ['192.168.112.203', 31, '192.168.112.203', '192.168.112.203'],
            ['192.168.112.203', 32, '192.168.112.203', '192.168.112.203'],
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
            ['192.168.112.203', 31, [192, 168, 112, 203]],
            ['192.168.112.203', 32, [192, 168, 112, 203]],
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
            ['192.168.112.203', 31, 'C0A870CB'],
            ['192.168.112.203', 32, 'C0A870CB'],
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
            ['192.168.112.203', 31, '11000000101010000111000011001011'],
            ['192.168.112.203', 32, '11000000101010000111000011001011'],
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
            ['192.168.112.203', 31, 3232264395],
            ['192.168.112.203', 32, 3232264395],
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
        ];
    }

    /**
     * @test         getSubnetMask
     * @dataProvider dataProviderForSubnetMask
     * @param        int      $network_size
     * @param        string   $subnet_mask
     * @param        string[] $quads
     * @param        string   $hex
     * @param        string   $binary
     * @param        int      $integer
     */
    public function testGetSubnetMask(int $network_size, string $subnet_mask, array $quads, string $hex, string $binary, int $integer): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator('192.168.233.207', $network_size);

        // Then
        $this->assertSame($subnet_mask, $sub->getSubnetMask());
        $this->assertSame($quads, $sub->getSubnetMaskQuads());
        $this->assertSame($hex, $sub->getSubnetMaskHex());
        $this->assertSame($binary, $sub->getSubnetMaskBinary());
        $this->assertSame($integer, $sub->getSubnetMaskInteger());
    }

    /**
     * @return array[] [network size, subnet mask, hex, binary, integer]
     */
    public function dataProviderForSubnetMask(): array
    {
        return [
            [1, '128.0.0.0', ['128', '0', '0', '0'], '80000000', '10000000000000000000000000000000', 2147483648],
            [2, '192.0.0.0', ['192', '0', '0', '0'], 'C0000000', '11000000000000000000000000000000', 3221225472],
            [3, '224.0.0.0', ['224', '0', '0', '0'], 'E0000000', '11100000000000000000000000000000', 3758096384],
            [4, '240.0.0.0', ['240', '0', '0', '0'], 'F0000000', '11110000000000000000000000000000', 4026531840],
            [5, '248.0.0.0', ['248', '0', '0', '0'], 'F8000000', '11111000000000000000000000000000', 4160749568],
            [6, '252.0.0.0', ['252', '0', '0', '0'], 'FC000000', '11111100000000000000000000000000', 4227858432],
            [7, '254.0.0.0', ['254', '0', '0', '0'], 'FE000000', '11111110000000000000000000000000', 4261412864],
            [8, '255.0.0.0', ['255', '0', '0', '0'], 'FF000000', '11111111000000000000000000000000', 4278190080],
            [9, '255.128.0.0', ['255', '128', '0', '0'], 'FF800000', '11111111100000000000000000000000', 4286578688],
            [10, '255.192.0.0', ['255', '192', '0', '0'], 'FFC00000', '11111111110000000000000000000000', 4290772992],
            [11, '255.224.0.0', ['255', '224', '0', '0'], 'FFE00000', '11111111111000000000000000000000', 4292870144],
            [12, '255.240.0.0', ['255', '240', '0', '0'], 'FFF00000', '11111111111100000000000000000000', 4293918720],
            [13, '255.248.0.0', ['255', '248', '0', '0'], 'FFF80000', '11111111111110000000000000000000', 4294443008],
            [14, '255.252.0.0', ['255', '252', '0', '0'], 'FFFC0000', '11111111111111000000000000000000', 4294705152],
            [15, '255.254.0.0', ['255', '254', '0', '0'], 'FFFE0000', '11111111111111100000000000000000', 4294836224],
            [16, '255.255.0.0', ['255', '255', '0', '0'], 'FFFF0000', '11111111111111110000000000000000', 4294901760],
            [17, '255.255.128.0', ['255', '255', '128', '0'], 'FFFF8000', '11111111111111111000000000000000', 4294934528],
            [18, '255.255.192.0', ['255', '255', '192', '0'], 'FFFFC000', '11111111111111111100000000000000', 4294950912],
            [19, '255.255.224.0', ['255', '255', '224', '0'], 'FFFFE000', '11111111111111111110000000000000', 4294959104],
            [20, '255.255.240.0', ['255', '255', '240', '0'], 'FFFFF000', '11111111111111111111000000000000', 4294963200],
            [21, '255.255.248.0', ['255', '255', '248', '0'], 'FFFFF800', '11111111111111111111100000000000', 4294965248],
            [22, '255.255.252.0', ['255', '255', '252', '0'], 'FFFFFC00', '11111111111111111111110000000000', 4294966272],
            [23, '255.255.254.0', ['255', '255', '254', '0'], 'FFFFFE00', '11111111111111111111111000000000', 4294966784],
            [24, '255.255.255.0', ['255', '255', '255', '0'], 'FFFFFF00', '11111111111111111111111100000000', 4294967040],
            [25, '255.255.255.128', ['255', '255', '255', '128'], 'FFFFFF80', '11111111111111111111111110000000', 4294967168],
            [26, '255.255.255.192', ['255', '255', '255', '192'], 'FFFFFFC0', '11111111111111111111111111000000', 4294967232],
            [27, '255.255.255.224', ['255', '255', '255', '224'], 'FFFFFFE0', '11111111111111111111111111100000', 4294967264],
            [28, '255.255.255.240', ['255', '255', '255', '240'], 'FFFFFFF0', '11111111111111111111111111110000', 4294967280],
            [29, '255.255.255.248', ['255', '255', '255', '248'], 'FFFFFFF8', '11111111111111111111111111111000', 4294967288],
            [30, '255.255.255.252', ['255', '255', '255', '252'], 'FFFFFFFC', '11111111111111111111111111111100', 4294967292],
            [31, '255.255.255.254', ['255', '255', '255', '254'], 'FFFFFFFE', '11111111111111111111111111111110', 4294967294],
            [32, '255.255.255.255', ['255', '255', '255', '255'], 'FFFFFFFF', '11111111111111111111111111111111', 4294967295],
        ];
    }

    /**
     * @test         getHostPortion
     * @dataProvider dataProviderForNetworkPortion
     * @param        string   $ip_address
     * @param        int      $network_size
     * @param        string   $network
     * @param        string[] $quads
     * @param        string   $hex
     * @param        string   $binary
     * @param        int      $integer
     */
    public function testGetNetworkPortion(string $ip_address, int $network_size, string $network, array $quads, string $hex, string $binary, int $integer): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // Then
        $this->assertSame($network, $sub->getNetworkPortion());
        $this->assertSame($quads, $sub->getNetworkPortionQuads());
        $this->assertSame($hex, $sub->getNetworkPortionHex());
        $this->assertSame($binary, $sub->getNetworkPortionBinary());
        $this->assertSame($integer, $sub->getNetworkPortionInteger());
    }

    /**
     * @return array[] [ip_address, network_size, network, quads, hex, binary, integer]
     */
    public function dataProviderForNetworkPortion(): array
    {
        return [
            ['192.168.112.203', 1, '128.0.0.0', ['128', '0', '0', '0'], '80000000', '10000000000000000000000000000000', 2147483648],
            ['192.168.84.233', 2, '192.0.0.0', ['192', '0', '0', '0'], 'C0000000', '11000000000000000000000000000000', 3221225472],
            ['10.10.122.113', 3, '0.0.0.0', ['0', '0', '0', '0'], '00000000', '00000000000000000000000000000000', 0],
            ['255.255.255.255', 4, '240.0.0.0', ['240', '0', '0', '0'], 'F0000000', '11110000000000000000000000000000', 4026531840],
            ['192.168.112.207', 5, '192.0.0.0', ['192', '0', '0', '0'], 'C0000000', '11000000000000000000000000000000', 3221225472],
            ['192.128.0.1', 6, '192.0.0.0', ['192', '0', '0', '0'], 'C0000000', '11000000000000000000000000000000', 3221225472],
            ['128.0.0.0', 7, '128.0.0.0', ['128', '0', '0', '0'], '80000000', '10000000000000000000000000000000', 2147483648],
            ['235.90.125.222', 8, '235.0.0.0', ['235', '0', '0', '0'], 'EB000000', '11101011000000000000000000000000', 3942645760],
            ['208.153.158.185', 9, '208.128.0.0', ['208', '128', '0', '0'], 'D0800000', '11010000100000000000000000000000', 3498049536],
            ['99.107.189.17', 10, '99.64.0.0', ['99', '64', '0', '0'], '63400000', '01100011010000000000000000000000', 1665138688],
            ['233.126.142.167', 11, '233.96.0.0', ['233', '96', '0', '0'], 'E9600000', '11101001011000000000000000000000', 3915382784],
            ['205.39.43.86', 12, '205.32.0.0', ['205', '32', '0', '0'], 'CD200000', '11001101001000000000000000000000', 3441426432],
            ['158.114.74.115', 13, '158.112.0.0', ['158', '112', '0', '0'], '9E700000', '10011110011100000000000000000000', 2658140160],
            ['127.132.3.128', 14, '127.132.0.0', ['127', '132', '0', '0'], '7F840000', '01111111100001000000000000000000', 2139357184],
            ['243.73.87.101', 15, '243.72.0.0', ['243', '72', '0', '0'], 'F3480000', '11110011010010000000000000000000', 4081582080],
            ['176.103.67.129', 16, '176.103.0.0', ['176', '103', '0', '0'], 'B0670000', '10110000011001110000000000000000', 2959540224],
            ['190.113.28.0', 17, '190.113.0.0', ['190', '113', '0', '0'], 'BE710000', '10111110011100010000000000000000', 3195076608],
            ['204.243.103.224', 18, '204.243.64.0', ['204', '243', '64', '0'], 'CCF34000', '11001100111100110100000000000000', 3438493696],
            ['203.247.20.148', 19, '203.247.0.0', ['203', '247', '0', '0'], 'CBF70000', '11001011111101110000000000000000', 3421962240],
            ['15.254.55.4', 20, '15.254.48.0', ['15', '254', '48', '0'], '0FFE3000', '00001111111111100011000000000000', 268316672],
            ['96.245.55.29', 21, '96.245.48.0', ['96', '245', '48', '0'], '60F53000', '01100000111101010011000000000000', 1626681344],
            ['88.102.195.7', 22, '88.102.192.0', ['88', '102', '192', '0'], '5866C000', '01011000011001101100000000000000', 1483128832],
            ['144.60.195.68', 23, '144.60.194.0', ['144', '60', '194', '0'], '903CC200', '10010000001111001100001000000000', 2419900928],
            ['189.191.237.105', 24, '189.191.237.0', ['189', '191', '237', '0'], 'BDBFED00', '10111101101111111110110100000000', 3183471872],
            ['98.79.29.150', 25, '98.79.29.128', ['98', '79', '29', '128'], '624F1D80', '01100010010011110001110110000000', 1649352064],
            ['56.5.145.126', 26, '56.5.145.64', ['56', '5', '145', '64'], '38059140', '00111000000001011001000101000000', 939888960],
            ['80.170.127.173', 27, '80.170.127.160', ['80', '170', '127', '160'], '50AA7FA0', '01010000101010100111111110100000', 1353351072],
            ['92.123.10.117', 28, '92.123.10.112', ['92', '123', '10', '112'], '5C7B0A70', '01011100011110110000101001110000', 1551567472],
            ['88.52.155.198', 29, '88.52.155.192', ['88', '52', '155', '192'], '58349BC0', '01011000001101001001101111000000', 1479842752],
            ['230.233.123.40', 30, '230.233.123.40', ['230', '233', '123', '40'], 'E6E97B28', '11100110111010010111101100101000', 3874061096],
            ['254.17.211.42', 31, '254.17.211.42', ['254', '17', '211', '42'], 'FE11D32A', '11111110000100011101001100101010', 4262581034],
            ['57.51.231.108', 32, '57.51.231.108', ['57', '51', '231', '108'], '3933E76C', '00111001001100111110011101101100', 959702892],
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

    /**
     * @test         constructor bad IP address
     * @dataProvider dataProviderForBadIpAddresses
     * @param        string $ip_address
     * @throws       \Exception
     */
    public function testConstructorExceptionOnBadIPAddress(string $ip_address): void
    {
        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        $sub = new IPv4\SubnetCalculator($ip_address, 24);
    }

    /**
     * @test         validateInputs bad IP address
     * @dataProvider dataProviderForBadIpAddresses
     * @param        string $ip_address
     * @throws       \Exception
     */
    public function testValidateInputExceptionOnBadIPAddress(string $ip_address): void
    {
        // Given
        $validateInputs = new \ReflectionMethod(IPv4\SubnetCalculator::class, 'validateInputs');
        $validateInputs->setAccessible(true);

        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        $validateInputs->invokeArgs($this->sub, [$ip_address, 24]);
    }

    /**
     * @return string[][] [ip_address]
     */
    public function dataProviderForBadIpAddresses(): array
    {
        return [
            ['-1.168.3.4'],
            ['256.168.3.4'],
            ['555.444.333.222'],
        ];
    }

    /**
     * @test         constructor bad network size
     * @dataProvider dataProviderForBadNetworkSize
     * @param        int $network_size
     * @throws       \Exception
     */
    public function testConstructorExceptionOnBadNetworkSize(int $network_size): void
    {
        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        $sub = new IPv4\SubnetCalculator('192.168.112.203', $network_size);
    }


    /**
     * @test         validateInputs bad network size
     * @dataProvider dataProviderForBadNetworkSize
     * @param        int $network_size
     * @throws       \Exception
     */
    public function testValidateInputExceptionOnBadNetworkSize(int $network_size): void
    {
        // Given
        $validateInputs = new \ReflectionMethod(IPv4\SubnetCalculator::class, 'validateInputs');
        $validateInputs->setAccessible(true);

        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        $validateInputs->invokeArgs($this->sub, ['192.168.112.203', $network_size]);
    }

    /**
     * @return int[][] [network_size]
     */
    public function dataProviderForBadNetworkSize(): array
    {
        return [
            [-2],
            [-1],
            [0],
            [33],
            [34],
            [89394839],
        ];
    }

    /**
     * @test getSubnetArrayReport
     */
    public function testGetSubnetArrayReport(): void
    {
        // When
        $report = $this->sub->getSubnetArrayReport();

        // Then
        $this->assertIsArray($report);
        $this->assertArrayHasKey('ip_address_with_network_size', $report);
        $this->assertArrayHasKey('ip_address', $report);
        $this->assertArrayHasKey('subnet_mask', $report);
        $this->assertArrayHasKey('network_portion', $report);
        $this->assertArrayHasKey('host_portion', $report);
        $this->assertArrayHasKey('network_size', $report);
        $this->assertArrayHasKey('number_of_ip_addresses', $report);
        $this->assertArrayHasKey('number_of_addressable_hosts', $report);
        $this->assertArrayHasKey('ip_address_range', $report);
        $this->assertArrayHasKey('broadcast_address', $report);
        $this->assertArrayHasKey('min_host', $report);
        $this->assertArrayHasKey('max_host', $report);
    }

    /**
     * @test getSubnetJsonReport
     */
    public function testGetSubnetJsonReport(): void
    {
        // When
        $json = $this->sub->getSubnetJsonReport();

        // Then
        $this->assertIsString($json);
    }

    /**
     * @test getSubnetJsonReport gets a JSON error from the SubnetReportInterface
     */
    public function testGetSubnetJsonReportJsonError(): void
    {
        // Given
        /** @var \PHPUnit\Framework\MockObject\MockObject $subnetReport */
        $subnetReport = $this->getMockBuilder(IPv4\SubnetReport::class)
            ->onlyMethods(['createJsonReport'])
            ->getMock();
        $subnetReport->method('createJsonReport')->willReturn(false);

        /** @var IPv4\SubnetReport $subnetReport */
        $sub = new IPv4\SubnetCalculator('192.168.112.203', 23, $subnetReport);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $sub->getSubnetJsonReport();
    }

    /**
     * @test printSubnetReport
     */
    public function testPrintSubnetReport(): void
    {
        // Then
        $this->expectOutputRegex('
            /
                ^
                \d+[.]\d+[.]\d+[.]\d+\/\d+ \s+ Quads \s+ Hex \s+ Binary \s+ Integer \n
                .+?                                                                 \n
                IP [ ] Address:      .+                                             \n
                Subnet [ ] Mask:     .+                                             \n
                Network [ ] Portion: .+                                             \n
                Host [ ] Portion:    .+                                             \n
                                                                                    \n
                Number [ ] of [ ] IP [ ] Addresses:      \s+ \d+                    \n
                Number [ ] of [ ] Addressable [ ] Hosts: \s+ \d+                    \n
                IP [ ] Address [ ] Range:                \s+ .+?                    \n
                Broadcast [ ] Address:                   \s+ .+?                    \n
                Min [ ] Host:                            \s  .+?                    \n
                Max [ ] Host:                            \s  .+?                    \n
                $
            /xms
        ');

        // When
        $this->sub->printSubnetReport();
    }

    /**
     * @test getPrintableReport
     */
    public function testGetPrintableReport(): void
    {
        // When
        $report = $this->sub->getPrintableReport();

        // Then
        $this->assertIsString($report);
    }

    /**
     * @test \JsonSerializable interface
     */
    public function testJsonSerializableInterface(): void
    {
        // When
        $json = \json_encode($this->sub);

        // Then
        $this->assertIsString($json);

        // And
        $decoded = \json_decode($json, true);
        $this->assertArrayHasKey('ip_address_with_network_size', $decoded);
        $this->assertArrayHasKey('ip_address', $decoded);
        $this->assertArrayHasKey('subnet_mask', $decoded);
        $this->assertArrayHasKey('network_portion', $decoded);
        $this->assertArrayHasKey('host_portion', $decoded);
        $this->assertArrayHasKey('network_size', $decoded);
        $this->assertArrayHasKey('number_of_ip_addresses', $decoded);
        $this->assertArrayHasKey('number_of_addressable_hosts', $decoded);
        $this->assertArrayHasKey('ip_address_range', $decoded);
        $this->assertArrayHasKey('broadcast_address', $decoded);
        $this->assertArrayHasKey('min_host', $decoded);
        $this->assertArrayHasKey('max_host', $decoded);
    }

    /**
     * @return array[] [ip_address, network_size, number_addresses]
     */
    public function dataProviderForGetAllIpsCount(): array
    {
        return [
            ['192.168.112.203', 16, 65536],
            ['192.168.112.203', 17, 32768],
            ['192.168.112.203', 18, 16384],
            ['192.168.112.203', 19, 8192],
            ['192.168.112.203', 20, 4096],
            ['192.168.112.203', 21, 2048],
            ['192.168.112.203', 22, 1024],
            ['192.168.112.203', 23, 512],
            ['192.168.112.203', 24, 256],
            ['192.168.112.203', 25, 128],
            ['192.168.112.203', 26, 64],
            ['192.168.112.203', 27, 32],
            ['192.168.112.203', 28, 16],
            ['192.168.112.203', 29, 8],
            ['192.168.112.203', 30, 4],
            ['192.168.112.203', 31, 2],
            ['192.168.112.203', 32, 1],
        ];
    }

    /**
     * @return array[] [ip_address, network_size, number_addresses]
     */
    public function dataProviderForGetAllIpsHostOnlyCount(): array
    {
        return [
            ['192.168.112.203', 16, 65534],
            ['192.168.112.203', 17, 32766],
            ['192.168.112.203', 18, 16382],
            ['192.168.112.203', 19, 8190],
            ['192.168.112.203', 20, 4094],
            ['192.168.112.203', 21, 2046],
            ['192.168.112.203', 22, 1022],
            ['192.168.112.203', 23, 510],
            ['192.168.112.203', 24, 254],
            ['192.168.112.203', 25, 126],
            ['192.168.112.203', 26, 62],
            ['192.168.112.203', 27, 30],
            ['192.168.112.203', 28, 14],
            ['192.168.112.203', 29, 6],
            ['192.168.112.203', 30, 2],
            ['192.168.112.203', 31, 2],
        ];
    }

    /**
     * @test         getAllIPAddresses returns the expected number of IP addresses
     * @dataProvider dataProviderForGetAllIpsCount
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        int    $number_addresses
     */
    public function testGetAllIPsCount(string $ip_address, int $network_size, int $number_addresses): void
    {
        // Given
        $sub   = new IPv4\SubnetCalculator($ip_address, $network_size);
        $count = 0;

        // When
        foreach ($sub->getAllIPAddresses() as $ip) {
            $count++;
        }

        // Then
        $this->assertEquals($number_addresses, $count);
        $this->assertEquals($count, $sub->getNumberIPAddresses());
    }

    /**
     * @test         getAllHostIPAddresses returns the expected number of IP addresses
     * @dataProvider dataProviderForGetAllIpsHostOnlyCount
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        int    $number_addresses
     */
    public function testGetAllHostIPsCount(string $ip_address, int $network_size, int $number_addresses): void
    {
        // Given
        $sub   = new IPv4\SubnetCalculator($ip_address, $network_size);
        $count = 0;

        // When
        foreach ($sub->getAllHostIPAddresses() as $ip) {
            $count++;
        }

        // Then
        $this->assertEquals($number_addresses, $count);
    }

    /**
     * @test getAllHostIPAddresses returns the expected number of IP addresses for edge case /32 network
     */
    public function testGetAllHostIPsCountHostsOnlyEdgeCaseSlash32Network(): void
    {
        // Given
        $sub   = new IPv4\SubnetCalculator('192.168.112.203', 32);
        $count = 0;

        // When
        foreach ($sub->getAllIPAddresses() as $ip) {
            $count++;
        }

        // Then
        $this->assertEquals(1, $count);
    }

    /**
     * @test         getAllIPAddresses returns the expected IP addresses
     * @dataProvider dataProviderForGetAllIps
     * @param        string    $ip_address
     * @param        int       $network_size
     * @param        string[]  $ip_addresses
     */
    public function testGetAllIPs(string $ip_address, int $network_size, array $ip_addresses): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        foreach ($sub->getAllIPAddresses() as $key => $ip) {
            // Then
            $this->assertEquals($ip_addresses[$key], $ip);
        }
    }

    /**
     * @return array[] [ip_address, network_size, [ip_addresses]]
     */
    public function dataProviderForGetAllIps(): array
    {
        return [
            ['192.168.112.203', 28, ['192.168.112.192', '192.168.112.193', '192.168.112.194', '192.168.112.195', '192.168.112.196', '192.168.112.197', '192.168.112.198', '192.168.112.199', '192.168.112.200', '192.168.112.201', '192.168.112.202', '192.168.112.203', '192.168.112.204', '192.168.112.205', '192.168.112.206', '192.168.112.207']],
            ['192.168.112.203', 29, ['192.168.112.200', '192.168.112.201', '192.168.112.202', '192.168.112.203', '192.168.112.204', '192.168.112.205', '192.168.112.206', '192.168.112.207']],
            ['192.168.112.203', 30, ['192.168.112.200', '192.168.112.201', '192.168.112.202', '192.168.112.203']],
            ['192.168.112.203', 31, ['192.168.112.202', '192.168.112.203']],
            ['192.168.112.203', 32, ['192.168.112.203']],
        ];
    }

    /**
     * @test         getAllHostIPAddresses returns the expected IP addresses
     * @dataProvider dataProviderForGetAllIpsHostsOnly
     * @param        string   $ip_address
     * @param        int      $network_size
     * @param        string[] $ip_addresses
     */
    public function testGetAllHostIPAddresses(string $ip_address, int $network_size, array $ip_addresses): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        foreach ($sub->getAllHostIPAddresses() as $key => $ip) {
            // Then
            $this->assertEquals($ip_addresses[$key], $ip);
        }
    }

    /**
     * @return array[] [ip_address, network_size, [ip_addresses]]
     */
    public function dataProviderForGetAllIpsHostsOnly(): array
    {
        return [
            ['192.168.112.203', 28, ['192.168.112.193', '192.168.112.194', '192.168.112.195', '192.168.112.196', '192.168.112.197', '192.168.112.198', '192.168.112.199', '192.168.112.200', '192.168.112.201', '192.168.112.202', '192.168.112.203', '192.168.112.204', '192.168.112.205', '192.168.112.206']],
            ['192.168.112.203', 29, ['192.168.112.201', '192.168.112.202', '192.168.112.203', '192.168.112.204', '192.168.112.205', '192.168.112.206']],
            ['192.168.112.203', 30, ['192.168.112.201', '192.168.112.202']],
            ['192.168.112.203', 31, ['192.168.112.202', '192.168.112.203',]],
            ['192.168.112.203', 32, ['192.168.112.203']],
        ];
    }

    /**
     * @test getAllIPAddresses gets an error in the getIPAddressRange calculation
     */
    public function testGetAllIPAddressesIPRangeCalculationError(): void
    {
        // Given
        /** @var \PHPUnit\Framework\MockObject\MockObject $sub */
        $sub = $this->getMockBuilder(IPv4\SubnetCalculator::class)
            ->onlyMethods(['getIPAddressRange'])
            ->disableOriginalConstructor()
            ->getMock();
        $sub->method('getIPAddressRange')->willReturn(['-4', '-1']);
        /** @var IPv4\SubnetCalculator $sub */

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        foreach ($sub->getAllIPAddresses() as $ip) {
            // Exception is thrown
        }
    }

    /**
     * @test getAllHostIPAddresses gets an error in the getIPAddressRange calculation
     */
    public function testGetAllHostIPAddressesIPRangeCalculationError(): void
    {
        // Given
        /** @var \PHPUnit\Framework\MockObject\MockObject $sub */
        $sub = $this->getMockBuilder(IPv4\SubnetCalculator::class)
            ->onlyMethods(['getIPAddressRange'])
            ->disableOriginalConstructor()
            ->getMock();
        $sub->method('getIPAddressRange')->willReturn(['-4', '-1']);
        /** @var IPv4\SubnetCalculator $sub */

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        foreach ($sub->getAllHostIPAddresses() as $ip) {
            // Exception is thrown
        }
    }

    /**
     * @test         isIPAddressInSubnet
     * @dataProvider dataProviderForGetAllIps
     * @param        string   $ip_address
     * @param        int      $network_size
     * @param        string[] $ip_addresses
     */
    public function testIsIPAddressInSubnet(string $ip_address, int $network_size, array $ip_addresses): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        foreach ($ip_addresses as $ip_address) {
            // When
            $isIPInSubnet = $sub->isIPAddressInSubnet($ip_address);

            // Then
            $this->assertTrue($isIPInSubnet);
        }
    }

    /**
     * @test isIPAddressInSubnet for all IP addresses in a subnet
     */
    public function testIsIPAddressInSubnetForAllIPAddressesInSubnet(): void
    {
        foreach ($this->sub->getAllIPAddresses() as $ip_address) {
            // When
            $isIPInSubnet = $this->sub->isIPAddressInSubnet($ip_address);

            // Then
            $this->assertTrue($isIPInSubnet);
        }
    }

    /**
     * @test         isIPAddressInSubnet when it is not
     * @dataProvider dataProviderForIpAddressesNotInSubnet
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $ip_address_to_check
     */
    public function testIsIpAddressInSubnetWhenItIsNot(string $ip_address, int $network_size, string $ip_address_to_check): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $isIPInSubnet = $sub->isIPAddressInSubnet($ip_address_to_check);

        // Then
        $this->assertFalse($isIPInSubnet, "$ip_address_to_check");
    }

    /**
     * @return array[]
     */
    public function dataProviderForIpAddressesNotInSubnet(): array
    {
        return [
            ['192.168.112.203', 28, '10.168.112.194'],
            ['192.168.112.203', 28, '192.148.112.191'],
            ['192.168.112.203', 28, '192.168.111.191'],
            ['192.168.112.203', 28, '192.168.112.190'],
            ['192.168.112.203', 28, '192.168.112.191'],
            ['192.168.112.203', 28, '192.168.112.208'],
            ['192.168.112.203', 28, '192.168.112.209'],
            ['192.168.112.203', 28, '192.168.152.208'],
            ['192.168.112.203', 28, '192.178.112.208'],
            ['192.168.112.203', 28, '255.168.112.208'],
            ['192.168.112.203', 31, '192.168.112.201'],
            ['192.168.112.203', 31, '192.168.112.204'],
            ['192.168.112.203', 32, '192.168.112.202'],
            ['192.168.112.203', 32, '192.168.112.204'],

            ['192.168.112.203', 1, '127.0.0.0'],
            ['192.168.112.203', 2, '191.0.0.0'],
            ['192.168.112.203', 3, '191.0.0.0'],
            ['192.168.112.203', 4, '191.0.0.0'],
            ['192.168.112.203', 5, '191.0.0.0'],
            ['192.168.112.203', 6, '191.0.0.0'],
            ['192.168.112.203', 7, '190.0.0.0'],
            ['192.168.112.203', 8, '190.0.0.0'],
            ['192.168.112.203', 9, '190.128.0.0'],
            ['192.168.112.203', 10, '192.127.0.0'],
            ['192.168.112.203', 11, '192.150.0.0'],
            ['192.168.112.203', 12, '192.140.0.0'],
            ['192.168.112.203', 13, '192.167.0.0'],
            ['192.168.112.203', 14, '192.166.0.0'],
            ['192.168.112.203', 15, '192.165.0.0'],
            ['192.168.112.203', 16, '192.164.0.0'],
            ['192.168.112.203', 17, '192.163.0.0'],
            ['192.168.112.203', 18, '192.162.64.0'],
            ['192.168.112.203', 19, '192.161.96.0'],
            ['192.168.112.203', 20, '192.168.111.0'],
            ['192.168.112.203', 21, '192.168.110.0'],
            ['192.168.112.203', 22, '192.168.102.0'],
            ['192.168.112.203', 23, '192.168.102.0'],
            ['192.168.112.203', 24, '192.168.102.0'],
            ['192.168.112.203', 25, '192.168.102.128'],
            ['192.168.112.203', 26, '192.168.102.192'],
            ['192.168.112.203', 27, '192.168.102.192'],
            ['192.168.112.203', 28, '192.168.102.192'],
            ['192.168.112.203', 29, '192.168.111.200'],
            ['192.168.112.203', 30, '192.168.111.200'],
            ['192.168.112.203', 31, '192.168.111.202'],
            ['192.168.112.203', 32, '192.168.111.202'],

            ['192.168.112.203', 3, '224.255.255.255'],
            ['192.168.112.203', 4, '208.255.255.255'],
            ['192.168.112.203', 5, '200.255.255.255'],
            ['192.168.112.203', 6, '196.255.255.255'],
            ['192.168.112.203', 7, '194.255.255.255'],
            ['192.168.112.203', 8, '193.255.255.255'],
            ['192.168.112.203', 9, '194.255.255.255'],
            ['192.168.112.203', 10, '192.192.255.255'],
            ['192.168.112.203', 11, '192.193.255.255'],
            ['192.168.112.203', 12, '192.176.255.255'],
            ['192.168.112.203', 13, '192.177.255.255'],
            ['192.168.112.203', 14, '192.172.255.255'],
            ['192.168.112.203', 15, '192.179.255.255'],
            ['192.168.112.203', 16, '192.169.255.255'],
            ['192.168.112.203', 17, '192.178.127.255'],
            ['192.168.112.203', 18, '192.188.127.255'],
            ['192.168.112.203', 19, '192.198.127.255'],
            ['192.168.112.203', 20, '192.168.128.255'],
            ['192.168.112.203', 21, '192.168.129.255'],
            ['192.168.112.203', 22, '192.168.116.255'],
            ['192.168.112.203', 23, '192.168.114.255'],
            ['192.168.112.203', 24, '192.168.113.255'],
            ['192.168.112.203', 25, '192.168.113.255'],
            ['192.168.112.203', 26, '192.168.114.255'],
            ['192.168.112.203', 27, '192.168.112.224'],
            ['192.168.112.203', 28, '192.168.112.208'],
            ['192.168.112.203', 29, '192.168.112.208'],
            ['192.168.112.203', 30, '192.168.112.204'],
            ['192.168.112.203', 31, '192.168.112.205'],
            ['192.168.112.203', 32, '192.168.112.204'],
        ];
    }

    /**
     * @test         getIPv4ArpaDomain
     * @dataProvider dataProviderForIpv4ArpaDomain
     * @param        string $ipAddress
     * @param        string $expectedIPv4ArpaDomain
     */
    public function testGetIPv4ArpaDomain(string $ipAddress, string $expectedIPv4ArpaDomain): void
    {
        // Given
        $subnet = new IPv4\SubnetCalculator($ipAddress, 24);

        // When
        $ipv4ArpaDomain = $subnet->getIPv4ArpaDomain();

        // Then
        $this->assertEquals($expectedIPv4ArpaDomain, $ipv4ArpaDomain);
    }

    /**
     * @return string[][]
     */
    public function dataProviderForIpv4ArpaDomain(): array
    {
        return [
            ['8.8.4.4', '4.4.8.8.in-addr.arpa'],
            ['74.6.231.21', '21.231.6.74.in-addr.arpa'],
            ['192.168.21.165', '165.21.168.192.in-addr.arpa'],
            ['202.12.28.131', '131.28.12.202.in-addr.arpa'],
            ['1.2.3.4', '4.3.2.1.in-addr.arpa'],
            ['101.102.103.104', '104.103.102.101.in-addr.arpa'],
            ['192.0.2.0', '0.2.0.192.in-addr.arpa'],
            ['206.6.177.200', '200.177.6.206.in-addr.arpa'],
        ];
    }
}
