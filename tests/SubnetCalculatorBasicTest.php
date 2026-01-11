<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4;

class SubnetCalculatorBasicTest extends \PHPUnit\Framework\TestCase
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
     * @test         getCidrNotation
     * @dataProvider dataProviderForIpAddresses
     * @param        string $ip_address
     * @param        int    $given_network_size
     * @param        string $expectedCidrNotation
     */
    public function testGetCidrNotation(string $ip_address, int $given_network_size, string $expectedCidrNotation): void
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $given_network_size);

        // When
        $cidrNotation = $sub->getCidrNotation();

        // Then
        $this->assertSame($expectedCidrNotation, $cidrNotation);
    }

    /**
     * @return array[] [ip_address, network_size, CIDR notation]
     */
    public function dataProviderForIpAddresses(): array
    {
        return [
            ['192.168.112.203', 1, '192.168.112.203/1'],
            ['192.168.84.233', 2, '192.168.84.233/2'],
            ['10.10.122.113', 3, '10.10.122.113/3'],
            ['255.255.255.255', 4, '255.255.255.255/4'],
            ['192.168.112.207', 5, '192.168.112.207/5'],
            ['192.128.0.1', 6, '192.128.0.1/6'],
            ['128.0.0.0', 7, '128.0.0.0/7'],
            ['235.90.125.222', 8, '235.90.125.222/8'],
            ['208.153.158.185', 9, '208.153.158.185/9'],
            ['99.107.189.17', 10, '99.107.189.17/10'],
            ['233.126.142.167', 11, '233.126.142.167/11'],
            ['205.39.43.86', 12, '205.39.43.86/12'],
            ['158.114.74.115', 13, '158.114.74.115/13'],
            ['127.132.3.128', 14, '127.132.3.128/14'],
            ['243.73.87.101', 15, '243.73.87.101/15'],
            ['176.103.67.129', 16, '176.103.67.129/16'],
            ['190.113.28.0', 17, '190.113.28.0/17'],
            ['204.243.103.224', 18, '204.243.103.224/18'],
            ['203.247.20.148', 19, '203.247.20.148/19'],
            ['15.254.55.4', 20, '15.254.55.4/20'],
            ['96.245.55.29', 21, '96.245.55.29/21'],
            ['88.102.195.7', 22, '88.102.195.7/22'],
            ['144.60.195.68', 23, '144.60.195.68/23'],
            ['189.191.237.105', 24, '189.191.237.105/24'],
            ['98.79.29.150', 25, '98.79.29.150/25'],
            ['56.5.145.126', 26, '56.5.145.126/26'],
            ['80.170.127.173', 27, '80.170.127.173/27'],
            ['92.123.10.117', 28, '92.123.10.117/28'],
            ['88.52.155.198', 29, '88.52.155.198/29'],
            ['230.233.123.40', 30, '230.233.123.40/30'],
            ['254.17.211.42', 31, '254.17.211.42/31'],
            ['57.51.231.108', 32, '57.51.231.108/32'],
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
}
