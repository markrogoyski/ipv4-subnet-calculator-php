<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4;

class SubnetCalculatorEnumerationTest extends \PHPUnit\Framework\TestCase
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
}
