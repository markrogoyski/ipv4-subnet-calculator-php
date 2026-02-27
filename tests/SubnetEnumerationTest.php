<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class SubnetEnumerationTest extends \PHPUnit\Framework\TestCase
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
     * @return array[] [ip_address, network_size, number_addresses]
     */
    public static function dataProviderForGetAllIpsCount(): array
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
    public static function dataProviderForGetAllIpsHostOnlyCount(): array
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
     * getAllIPAddresses returns the expected number of IP addresses
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        int    $number_addresses
     */
    #[Test]
    #[DataProvider('dataProviderForGetAllIpsCount')]
    public function testGetAllIPsCount(string $ip_address, int $network_size, int $number_addresses): void
    {
        // Given
        $sub = new Subnet($ip_address, $network_size);

        // When
        $count = \iterator_count($sub->addressRange());

        // Then
        $this->assertEquals($number_addresses, $count);
        $this->assertEquals($count, $sub->addressCount());
    }

    /**
     * getAllHostIPAddresses returns the expected number of IP addresses
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        int    $number_addresses
     */
    #[Test]
    #[DataProvider('dataProviderForGetAllIpsHostOnlyCount')]
    public function testGetAllHostIPsCount(string $ip_address, int $network_size, int $number_addresses): void
    {
        // Given
        $sub = new Subnet($ip_address, $network_size);

        // When
        $count = \iterator_count($sub->hostRange());

        // Then
        $this->assertEquals($number_addresses, $count);
    }

    /**
     * getAllHostIPAddresses returns the expected number of IP addresses for edge case /32 network
     */
    #[Test]
    public function testGetAllHostIPsCountHostsOnlyEdgeCaseSlash32Network(): void
    {
        // Given
        $sub   = new Subnet('192.168.112.203', 32);
        $count = 0;

        // When
        foreach ($sub->addressRange() as $ip) {
            $count++;
        }

        // Then
        $this->assertEquals(1, $count);
    }

    /**
     * getAllIPAddresses returns the expected IP addresses
     * @param        string    $ip_address
     * @param        int       $network_size
     * @param        string[]  $ip_addresses
     */
    #[Test]
    #[DataProvider('dataProviderForGetAllIps')]
    public function testGetAllIPs(string $ip_address, int $network_size, array $ip_addresses): void
    {
        // Given
        $sub = new Subnet($ip_address, $network_size);

        // When
        foreach ($sub->addressRange() as $key => $ip) {
            // Then
            $this->assertEquals($ip_addresses[$key], (string) $ip);
        }
    }

    /**
     * @return array[] [ip_address, network_size, [ip_addresses]]
     */
    public static function dataProviderForGetAllIps(): array
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
     * getAllHostIPAddresses returns the expected IP addresses
     * @param        string   $ip_address
     * @param        int      $network_size
     * @param        string[] $ip_addresses
     */
    #[Test]
    #[DataProvider('dataProviderForGetAllIpsHostsOnly')]
    public function testGetAllHostIPAddresses(string $ip_address, int $network_size, array $ip_addresses): void
    {
        // Given
        $sub = new Subnet($ip_address, $network_size);

        // When
        foreach ($sub->hostRange() as $key => $ip) {
            // Then
            $this->assertEquals($ip_addresses[$key], (string) $ip);
        }
    }

    /**
     * @return array[] [ip_address, network_size, [ip_addresses]]
     */
    public static function dataProviderForGetAllIpsHostsOnly(): array
    {
        return [
            ['192.168.112.203', 28, ['192.168.112.193', '192.168.112.194', '192.168.112.195', '192.168.112.196', '192.168.112.197', '192.168.112.198', '192.168.112.199', '192.168.112.200', '192.168.112.201', '192.168.112.202', '192.168.112.203', '192.168.112.204', '192.168.112.205', '192.168.112.206']],
            ['192.168.112.203', 29, ['192.168.112.201', '192.168.112.202', '192.168.112.203', '192.168.112.204', '192.168.112.205', '192.168.112.206']],
            ['192.168.112.203', 30, ['192.168.112.201', '192.168.112.202']],
            ['192.168.112.203', 31, ['192.168.112.202', '192.168.112.203',]],
            ['192.168.112.203', 32, ['192.168.112.203']],
        ];
    }

    /* ******************************************** *
     * HIGH IP ADDRESS TESTS (>= 128.0.0.0)
     * Tests for IPs that use signed integers in PHP
     * ******************************************** */

    #[Test]
    #[DataProvider('dataProviderForHighIpGetAllIpsCount')]
    public function testGetAllIPsCountHighIpAddresses(string $ip_address, int $network_size, int $number_addresses): void
    {
        // Given
        $sub = new Subnet($ip_address, $network_size);

        // When
        $count = \iterator_count($sub->addressRange());

        // Then
        $this->assertEquals($number_addresses, $count);
        $this->assertEquals($count, $sub->addressCount());
    }

    public static function dataProviderForHighIpGetAllIpsCount(): array
    {
        return [
            // 200.x.x.x range
            ['200.0.0.0', 24, 256],
            ['200.0.0.0', 28, 16],
            ['200.0.0.0', 30, 4],
            ['200.0.0.0', 31, 2],
            ['200.0.0.0', 32, 1],
            // 255.x.x.x range (highest)
            ['255.255.255.0', 24, 256],
            ['255.255.255.0', 28, 16],
            ['255.255.255.252', 30, 4],
            ['255.255.255.254', 31, 2],
            ['255.255.255.255', 32, 1],
            // 128.x.x.x range (signed int boundary)
            ['128.0.0.0', 24, 256],
            ['128.0.0.0', 28, 16],
            ['128.0.0.0', 30, 4],
            // 224.x.x.x range (multicast)
            ['224.0.0.0', 28, 16],
            ['240.0.0.0', 28, 16],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForHighIpGetAllHostIpsCount')]
    public function testGetAllHostIPsCountHighIpAddresses(string $ip_address, int $network_size, int $number_addresses): void
    {
        // Given
        $sub = new Subnet($ip_address, $network_size);

        // When
        $count = \iterator_count($sub->hostRange());

        // Then
        $this->assertEquals($number_addresses, $count);
    }

    public static function dataProviderForHighIpGetAllHostIpsCount(): array
    {
        return [
            // 200.x.x.x range
            ['200.0.0.0', 24, 254],
            ['200.0.0.0', 28, 14],
            ['200.0.0.0', 30, 2],
            ['200.0.0.0', 31, 2],  // RFC 3021
            ['200.0.0.0', 32, 1],  // RFC 3021
            // 255.x.x.x range (highest)
            ['255.255.255.0', 24, 254],
            ['255.255.255.252', 30, 2],
            ['255.255.255.254', 31, 2],  // RFC 3021
            ['255.255.255.255', 32, 1],  // RFC 3021
            // 128.x.x.x range (signed int boundary)
            ['128.0.0.0', 24, 254],
            ['128.0.0.0', 30, 2],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForHighIpGetAllIpsValues')]
    public function testGetAllIPsValuesHighIpAddresses(string $ip_address, int $network_size, array $expected_ips): void
    {
        // Given
        $sub = new Subnet($ip_address, $network_size);
        $actual_ips = [];

        // When
        foreach ($sub->addressRange() as $ip) {
            $actual_ips[] = (string) $ip;
        }

        // Then
        $this->assertSame($expected_ips, $actual_ips);
    }

    public static function dataProviderForHighIpGetAllIpsValues(): array
    {
        return [
            '200.x.x.x /30' => [
                '200.100.50.0', 30,
                ['200.100.50.0', '200.100.50.1', '200.100.50.2', '200.100.50.3'],
            ],
            '200.x.x.x /31' => [
                '200.100.50.0', 31,
                ['200.100.50.0', '200.100.50.1'],
            ],
            '255.255.255.252 /30' => [
                '255.255.255.252', 30,
                ['255.255.255.252', '255.255.255.253', '255.255.255.254', '255.255.255.255'],
            ],
            '255.255.255.254 /31' => [
                '255.255.255.254', 31,
                ['255.255.255.254', '255.255.255.255'],
            ],
            '255.255.255.255 /32' => [
                '255.255.255.255', 32,
                ['255.255.255.255'],
            ],
            '128.0.0.0 /30' => [
                '128.0.0.0', 30,
                ['128.0.0.0', '128.0.0.1', '128.0.0.2', '128.0.0.3'],
            ],
            '224.0.0.0 /30 (multicast)' => [
                '224.0.0.0', 30,
                ['224.0.0.0', '224.0.0.1', '224.0.0.2', '224.0.0.3'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForHighIpGetAllHostIpsValues')]
    public function testGetAllHostIPsValuesHighIpAddresses(string $ip_address, int $network_size, array $expected_ips): void
    {
        // Given
        $sub = new Subnet($ip_address, $network_size);
        $actual_ips = [];

        // When
        foreach ($sub->hostRange() as $ip) {
            $actual_ips[] = (string) $ip;
        }

        // Then
        $this->assertSame($expected_ips, $actual_ips);
    }

    public static function dataProviderForHighIpGetAllHostIpsValues(): array
    {
        return [
            '200.x.x.x /30 hosts only' => [
                '200.100.50.0', 30,
                ['200.100.50.1', '200.100.50.2'],
            ],
            '200.x.x.x /31 (RFC 3021)' => [
                '200.100.50.0', 31,
                ['200.100.50.0', '200.100.50.1'],
            ],
            '255.255.255.252 /30 hosts only' => [
                '255.255.255.252', 30,
                ['255.255.255.253', '255.255.255.254'],
            ],
            '255.255.255.254 /31 (RFC 3021)' => [
                '255.255.255.254', 31,
                ['255.255.255.254', '255.255.255.255'],
            ],
            '255.255.255.255 /32 (RFC 3021)' => [
                '255.255.255.255', 32,
                ['255.255.255.255'],
            ],
            '128.0.0.0 /30 hosts only' => [
                '128.0.0.0', 30,
                ['128.0.0.1', '128.0.0.2'],
            ],
        ];
    }
}
