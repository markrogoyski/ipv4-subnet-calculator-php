<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4;

class SubnetCalculatorUtilityTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @return array[]
     */
    public function invalidIPV4Provider(): array
    {
        $invalid = 'New networkSize must be larger than the base networkSize.';
        $max     = 'New networkSize must be smaller than the maximum networkSize.';

        return [
            ['1.2.3.4', 1, 1, $invalid],
            ['1.2.3.4', 10, 10, $invalid],
            ['1.2.3.4', 20, 20, $invalid],
            ['1.2.3.4', 24, 24, $invalid],
            ['1.2.3.4', 25, 25, $invalid],
            ['1.2.3.4', 32, 32, $invalid],
            ['1.2.3.4', 1, 35, $max],
            ['1.2.3.4', 10, 36, $max],
            ['1.2.3.4', 20, 37, $max],
            ['1.2.3.4', 24, 38, $max],
            ['1.2.3.4', 25, 39, $max],
            ['1.2.3.4', 32, 40, $max],
            ['1.2.3.4', 32, 128, $max],
            ['1.2.3.4',32, 100, $max],
        ];
    }

    /**
     * @dataProvider invalidIPV4Provider
     *
     * @param string $inputString
     * @param int    $networkSize
     * @param int    $newNetworkSize
     * @param mixed  $expectedMessage
     */
    public function testInvalidSplitIPV4(string $inputString, int $networkSize, int $newNetworkSize, string $expectedMessage)
    {
        // Given
        $subnet = new IPv4\SubnetCalculator($inputString, $networkSize);

        // When
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        // Then
        $subnet->split($newNetworkSize);
    }

    public function validIPV4Provider()
    {
        return [
            [
                '0.0.0.0',
                1,
                2,
                [
                    '0.0.0.0/2',
                    '64.0.0.0/2',
                ],
            ],
            [
                '64.0.0.0',
                2,
                3,
                [
                    '64.0.0.0/3',
                    '96.0.0.0/3',
                ],
            ],
            [
                '124.52.4.4',
                14,
                15,
                [
                    '124.52.0.0/15',
                    '124.54.0.0/15',
                ],
            ],
            [
                '124.54.4.4',
                24,
                25,
                [
                    '124.54.4.0/25',
                    '124.54.4.128/25',
                ],
            ],
            [
                '124.54.4.4',
                25,
                26,
                [
                    '124.54.4.0/26',
                    '124.54.4.64/26',
                ],
            ],
            [
                '124.54.4.4',
                30,
                31,
                [
                    '124.54.4.4/31',
                    '124.54.4.6/31',
                ],
            ],
            [
                '124.54.4.4',
                31,
                32,
                [
                    '124.54.4.4/32',
                    '124.54.4.5/32',
                ],
            ],
            [
                '1.2.3.4',
                24,
                25,
                [
                    '1.2.3.0/25',
                    '1.2.3.128/25',
                ],
            ],
            [
                '1.2.3.4',
                24,
                26,
                [
                    '1.2.3.0/26',
                    '1.2.3.64/26',
                    '1.2.3.128/26',
                    '1.2.3.192/26',
                ],
            ],
            [
                '1.2.3.4',
                24,
                30,
                [
                    '1.2.3.0/30',
                    '1.2.3.4/30',
                    '1.2.3.8/30',
                    '1.2.3.12/30',
                    '1.2.3.16/30',
                    '1.2.3.20/30',
                    '1.2.3.24/30',
                    '1.2.3.28/30',
                    '1.2.3.32/30',
                    '1.2.3.36/30',
                    '1.2.3.40/30',
                    '1.2.3.44/30',
                    '1.2.3.48/30',
                    '1.2.3.52/30',
                    '1.2.3.56/30',
                    '1.2.3.60/30',
                    '1.2.3.64/30',
                    '1.2.3.68/30',
                    '1.2.3.72/30',
                    '1.2.3.76/30',
                    '1.2.3.80/30',
                    '1.2.3.84/30',
                    '1.2.3.88/30',
                    '1.2.3.92/30',
                    '1.2.3.96/30',
                    '1.2.3.100/30',
                    '1.2.3.104/30',
                    '1.2.3.108/30',
                    '1.2.3.112/30',
                    '1.2.3.116/30',
                    '1.2.3.120/30',
                    '1.2.3.124/30',
                    '1.2.3.128/30',
                    '1.2.3.132/30',
                    '1.2.3.136/30',
                    '1.2.3.140/30',
                    '1.2.3.144/30',
                    '1.2.3.148/30',
                    '1.2.3.152/30',
                    '1.2.3.156/30',
                    '1.2.3.160/30',
                    '1.2.3.164/30',
                    '1.2.3.168/30',
                    '1.2.3.172/30',
                    '1.2.3.176/30',
                    '1.2.3.180/30',
                    '1.2.3.184/30',
                    '1.2.3.188/30',
                    '1.2.3.192/30',
                    '1.2.3.196/30',
                    '1.2.3.200/30',
                    '1.2.3.204/30',
                    '1.2.3.208/30',
                    '1.2.3.212/30',
                    '1.2.3.216/30',
                    '1.2.3.220/30',
                    '1.2.3.224/30',
                    '1.2.3.228/30',
                    '1.2.3.232/30',
                    '1.2.3.236/30',
                    '1.2.3.240/30',
                    '1.2.3.244/30',
                    '1.2.3.248/30',
                    '1.2.3.252/30',
                ],
            ],
            [
                '1.2.3.4',
                22,
                23,
                [
                    '1.2.0.0/23',
                    '1.2.2.0/23',
                ],
            ],
            [
                '192.168.1.0',
                30,
                31,
                [
                    '192.168.1.0/31',
                    '192.168.1.2/31',
                ],
            ],
            [
                '192.168.1.0',
                30,
                32,
                [
                    '192.168.1.0/32',
                    '192.168.1.1/32',
                    '192.168.1.2/32',
                    '192.168.1.3/32',
                ],
            ],
            [
                '192.168.1.0',
                29,
                30,
                [
                    '192.168.1.0/30',
                    '192.168.1.4/30',
                ],
            ],
            [
                '10.0.0.0',
                16,
                20,
                [
                    '10.0.0.0/20',
                    '10.0.16.0/20',
                    '10.0.32.0/20',
                    '10.0.48.0/20',
                    '10.0.64.0/20',
                    '10.0.80.0/20',
                    '10.0.96.0/20',
                    '10.0.112.0/20',
                    '10.0.128.0/20',
                    '10.0.144.0/20',
                    '10.0.160.0/20',
                    '10.0.176.0/20',
                    '10.0.192.0/20',
                    '10.0.208.0/20',
                    '10.0.224.0/20',
                    '10.0.240.0/20',
                ],
            ],
            [
                '192.168.1.0',
                24,
                28,
                [
                    '192.168.1.0/28',
                    '192.168.1.16/28',
                    '192.168.1.32/28',
                    '192.168.1.48/28',
                    '192.168.1.64/28',
                    '192.168.1.80/28',
                    '192.168.1.96/28',
                    '192.168.1.112/28',
                    '192.168.1.128/28',
                    '192.168.1.144/28',
                    '192.168.1.160/28',
                    '192.168.1.176/28',
                    '192.168.1.192/28',
                    '192.168.1.208/28',
                    '192.168.1.224/28',
                    '192.168.1.240/28',
                ],
            ],
        ];
    }

    /**
     * @dataProvider validIPV4Provider
     *
     * @param string $inputString
     * @param int    $networkSize
     * @param int    $newNetworkSize
     * @param array  $expectedSubnets
     */
    public function testValidSplitIPV4(string $inputString, int $networkSize, int $newNetworkSize, array $expectedSubnets)
    {
        // Given
        $subnet = new IPv4\SubnetCalculator($inputString, $networkSize);

        // When
        $splitSubnets = $subnet->split($newNetworkSize);

        // Then
        $splitSubnetCidrNotations = \array_map(
            function ($newSplitSubnet) {
                return  $newSplitSubnet->getCidrNotation();
            },
            $splitSubnets
        );
        $this->assertSame($expectedSubnets, $splitSubnetCidrNotations);
    }

    /**
     * @test convertIpToInt throws an exception if given an invalid IP address is provided as input. Logic error.
     */
    public function testInternalMethodConvertIpToIntImpossibleScenario()
    {
        // Given
        $convertTpToInt = new \ReflectionMethod($this->sub, 'convertIpToInt');
        $convertTpToInt->setAccessible(true);

        // Then
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid IP address string');

        // When
        $convertTpToInt->invoke($this->sub, '300.300.300.300');
    }
}
