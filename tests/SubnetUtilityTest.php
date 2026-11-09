<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class SubnetUtilityTest extends \PHPUnit\Framework\TestCase
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
     * isIPAddressInSubnet
     *
     * @param string $subnet_ip       IP address defining the subnet
     * @param int    $network_size    CIDR network size
     * @param string $ip_to_check     IP address to check if it's in the subnet
     */
    #[Test]
    #[DataProvider('dataProviderForIsIPAddressInSubnet')]
    public function testIsIPAddressInSubnet(string $subnet_ip, int $network_size, string $ip_to_check): void
    {
        // Given
        $sub = new Subnet($subnet_ip, $network_size);

        // When
        $isIPInSubnet = $sub->containsIP($ip_to_check);

        // Then
        $this->assertTrue($isIPInSubnet, "Expected {$ip_to_check} to be in subnet {$subnet_ip}/{$network_size}");
    }

    /**
     * @return array[] [subnet_ip, network_size, ip_to_check]
     */
    public static function dataProviderForIsIPAddressInSubnet(): array
    {
        return [
            // /28 network - 192.168.112.192/28
            ['192.168.112.203', 28, '192.168.112.192'],
            ['192.168.112.203', 28, '192.168.112.193'],
            ['192.168.112.203', 28, '192.168.112.194'],
            ['192.168.112.203', 28, '192.168.112.195'],
            ['192.168.112.203', 28, '192.168.112.196'],
            ['192.168.112.203', 28, '192.168.112.197'],
            ['192.168.112.203', 28, '192.168.112.198'],
            ['192.168.112.203', 28, '192.168.112.199'],
            ['192.168.112.203', 28, '192.168.112.200'],
            ['192.168.112.203', 28, '192.168.112.201'],
            ['192.168.112.203', 28, '192.168.112.202'],
            ['192.168.112.203', 28, '192.168.112.203'],
            ['192.168.112.203', 28, '192.168.112.204'],
            ['192.168.112.203', 28, '192.168.112.205'],
            ['192.168.112.203', 28, '192.168.112.206'],
            ['192.168.112.203', 28, '192.168.112.207'],

            // /29 network - 192.168.112.200/29
            ['192.168.112.203', 29, '192.168.112.200'],
            ['192.168.112.203', 29, '192.168.112.201'],
            ['192.168.112.203', 29, '192.168.112.202'],
            ['192.168.112.203', 29, '192.168.112.203'],
            ['192.168.112.203', 29, '192.168.112.204'],
            ['192.168.112.203', 29, '192.168.112.205'],
            ['192.168.112.203', 29, '192.168.112.206'],
            ['192.168.112.203', 29, '192.168.112.207'],

            // /30 network - 192.168.112.200/30
            ['192.168.112.203', 30, '192.168.112.200'],
            ['192.168.112.203', 30, '192.168.112.201'],
            ['192.168.112.203', 30, '192.168.112.202'],
            ['192.168.112.203', 30, '192.168.112.203'],

            // /31 network - 192.168.112.202/31
            ['192.168.112.203', 31, '192.168.112.202'],
            ['192.168.112.203', 31, '192.168.112.203'],

            // /32 network - 192.168.112.203/32
            ['192.168.112.203', 32, '192.168.112.203'],
        ];
    }

    /**
     * isIPAddressInSubnet for all IP addresses in a subnet
     */
    #[Test]
    public function testIsIPAddressInSubnetForAllIPAddressesInSubnet(): void
    {
        foreach ($this->sub->addressRange() as $ip_address) {
            // When
            $isIPInSubnet = $this->sub->containsIP($ip_address);

            // Then
            $this->assertTrue($isIPInSubnet);
        }
    }

    /**
     * isIPAddressInSubnet when it is not
     *
     * @param string $ip_address
     * @param int    $network_size
     * @param string $ip_address_to_check
     */
    #[Test]
    #[DataProvider('dataProviderForIpAddressesNotInSubnet')]
    public function testIsIpAddressInSubnetWhenItIsNot(string $ip_address, int $network_size, string $ip_address_to_check): void
    {
        // Given
        $sub = new Subnet($ip_address, $network_size);

        // When
        $isIPInSubnet = $sub->containsIP($ip_address_to_check);

        // Then
        $this->assertFalse($isIPInSubnet, "$ip_address_to_check");
    }

    /**
     * @return array[]
     */
    public static function dataProviderForIpAddressesNotInSubnet(): array
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
     * arpaDomain reverses the input IP address, not the network address
     *
     * @param string $ipAddress
     * @param int    $networkSize
     * @param string $expectedArpaDomain
     */
    #[Test]
    #[DataProvider('dataProviderForArpaDomain')]
    public function arpaDomainReversesInputIpAddress(string $ipAddress, int $networkSize, string $expectedArpaDomain): void
    {
        // Given
        $subnet = new Subnet($ipAddress, $networkSize);

        // When
        $arpaDomain = $subnet->arpaDomain();

        // Then
        $this->assertSame($expectedArpaDomain, $arpaDomain);
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function dataProviderForArpaDomain(): array
    {
        return [
            '/24 host'              => ['8.8.4.4', 24, '4.4.8.8.in-addr.arpa'],
            '/24 host 2'            => ['74.6.231.21', 24, '21.231.6.74.in-addr.arpa'],
            '/24 private host'      => ['192.168.21.165', 24, '165.21.168.192.in-addr.arpa'],
            '/24 host 3'            => ['202.12.28.131', 24, '131.28.12.202.in-addr.arpa'],
            '/24 host 4'            => ['1.2.3.4', 24, '4.3.2.1.in-addr.arpa'],
            '/24 host 5'            => ['101.102.103.104', 24, '104.103.102.101.in-addr.arpa'],
            '/24 network address'   => ['192.0.2.0', 24, '0.2.0.192.in-addr.arpa'],
            '/24 host 6'            => ['206.6.177.200', 24, '200.177.6.206.in-addr.arpa'],
            '/23 documented example' => ['192.168.112.203', 23, '203.112.168.192.in-addr.arpa'],
            '/8 host'               => ['10.20.30.40', 8, '40.30.20.10.in-addr.arpa'],
            '/0 host'               => ['203.0.113.7', 0, '7.113.0.203.in-addr.arpa'],
            '/31 upper address'     => ['10.0.0.1', 31, '1.0.0.10.in-addr.arpa'],
            '/32 single host'       => ['10.0.0.7', 32, '7.0.0.10.in-addr.arpa'],
        ];
    }

    /**
     * reverseZones returns the exact octet-aligned reverse-zone origins covering the network
     *
     * @param string   $cidr
     * @param string[] $expectedZones
     */
    #[Test]
    #[DataProvider('dataProviderForReverseZones')]
    public function reverseZonesReturnsOctetAlignedOrigins(string $cidr, array $expectedZones): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $zones = $subnet->reverseZones();

        // Then
        $this->assertSame($expectedZones, $zones);
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function dataProviderForReverseZones(): array
    {
        return [
            '/0 entire space'           => ['0.0.0.0/0', ['in-addr.arpa']],
            '/0 host bits'              => ['203.0.113.7/0', ['in-addr.arpa']],
            '/7 two /8 zones'           => ['10.0.0.0/7', ['10.in-addr.arpa', '11.in-addr.arpa']],
            '/7 upper endpoint'         => ['255.255.255.255/7', ['254.in-addr.arpa', '255.in-addr.arpa']],
            '/8'                        => ['10.0.0.0/8', ['10.in-addr.arpa']],
            '/8 lower endpoint'         => ['0.0.0.0/8', ['0.in-addr.arpa']],
            '/8 host bits'              => ['10.20.30.40/8', ['10.in-addr.arpa']],
            '/15 two /16 zones'         => ['172.16.0.0/15', ['16.172.in-addr.arpa', '17.172.in-addr.arpa']],
            '/16'                       => ['172.16.0.0/16', ['16.172.in-addr.arpa']],
            '/16 host bits'             => ['172.16.200.9/16', ['16.172.in-addr.arpa']],
            '/20 sixteen /24 zones'     => ['192.168.16.0/20', [
                '16.168.192.in-addr.arpa', '17.168.192.in-addr.arpa', '18.168.192.in-addr.arpa', '19.168.192.in-addr.arpa',
                '20.168.192.in-addr.arpa', '21.168.192.in-addr.arpa', '22.168.192.in-addr.arpa', '23.168.192.in-addr.arpa',
                '24.168.192.in-addr.arpa', '25.168.192.in-addr.arpa', '26.168.192.in-addr.arpa', '27.168.192.in-addr.arpa',
                '28.168.192.in-addr.arpa', '29.168.192.in-addr.arpa', '30.168.192.in-addr.arpa', '31.168.192.in-addr.arpa',
            ]],
            '/23 octet carry'           => ['10.0.255.0/23', ['254.0.10.in-addr.arpa', '255.0.10.in-addr.arpa']],
            '/24'                       => ['192.168.1.0/24', ['1.168.192.in-addr.arpa']],
            '/24 host bits'             => ['192.168.1.50/24', ['1.168.192.in-addr.arpa']],
            '/30 four /32 zones'        => ['192.0.2.4/30', [
                '4.2.0.192.in-addr.arpa', '5.2.0.192.in-addr.arpa', '6.2.0.192.in-addr.arpa', '7.2.0.192.in-addr.arpa',
            ]],
            '/31'                       => ['192.0.2.6/31', ['6.2.0.192.in-addr.arpa', '7.2.0.192.in-addr.arpa']],
            '/31 host bits'             => ['192.0.2.7/31', ['6.2.0.192.in-addr.arpa', '7.2.0.192.in-addr.arpa']],
            '/31 lower endpoint'        => ['0.0.0.0/31', ['0.0.0.0.in-addr.arpa', '1.0.0.0.in-addr.arpa']],
            '/31 upper endpoint'        => ['255.255.255.254/31', ['254.255.255.255.in-addr.arpa', '255.255.255.255.in-addr.arpa']],
            '/32'                       => ['192.0.2.7/32', ['7.2.0.192.in-addr.arpa']],
            '/32 lower endpoint'        => ['0.0.0.0/32', ['0.0.0.0.in-addr.arpa']],
            '/32 upper endpoint'        => ['255.255.255.255/32', ['255.255.255.255.in-addr.arpa']],
        ];
    }

    /**
     * reverseZones splits to the next octet boundary: count, first and last origin
     *
     * @param string $cidr
     * @param int    $expectedCount
     * @param string $expectedFirst
     * @param string $expectedLast
     */
    #[Test]
    #[DataProvider('dataProviderForLargeReverseZoneSplits')]
    public function reverseZonesSplitsToNextOctetBoundary(string $cidr, int $expectedCount, string $expectedFirst, string $expectedLast): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $zones = $subnet->reverseZones();

        // Then
        $this->assertSame(
            [$expectedCount, $expectedFirst, $expectedLast],
            [\count($zones), $zones[0], $zones[\count($zones) - 1]]
        );
    }

    /**
     * @return array<string, array{string, int, string, string}>
     */
    public static function dataProviderForLargeReverseZoneSplits(): array
    {
        return [
            '/1 lower half'      => ['0.0.0.0/1', 128, '0.in-addr.arpa', '127.in-addr.arpa'],
            '/1 upper half'      => ['255.255.255.255/1', 128, '128.in-addr.arpa', '255.in-addr.arpa'],
            '/9'                 => ['10.128.0.0/9', 128, '128.10.in-addr.arpa', '255.10.in-addr.arpa'],
            '/17'                => ['172.16.0.0/17', 128, '0.16.172.in-addr.arpa', '127.16.172.in-addr.arpa'],
            '/25 never the /24'  => ['192.0.2.0/25', 128, '0.2.0.192.in-addr.arpa', '127.2.0.192.in-addr.arpa'],
            '/25 upper endpoint' => ['255.255.255.128/25', 128, '128.255.255.255.in-addr.arpa', '255.255.255.255.in-addr.arpa'],
        ];
    }

    /**
     * reverseZones is an ordered, duplicate-free, exact cover of the network by
     * zones at the next octet boundary, independent of input host bits
     */
    #[Test]
    public function reverseZonesExactlyCoverNetworkInOrder(): void
    {
        // Given
        $cidrs = [
            '0.0.0.0/0', '0.0.0.0/1', '128.0.0.0/1', '0.0.0.0/7', '254.0.0.0/7', '10.0.0.0/8',
            '10.128.0.0/9', '9.255.0.0/15', '172.16.0.0/16', '172.16.128.0/17', '10.0.254.0/23',
            '192.168.16.0/20', '192.168.1.0/24', '10.0.255.128/25', '192.0.2.8/29', '192.0.2.13/29',
            '192.0.2.14/30', '192.0.2.7/31', '0.0.0.0/31', '255.255.255.255/31', '192.0.2.7/32',
            '0.0.0.0/32', '255.255.255.255/32', '255.255.255.0/24', '255.255.0.0/16', '255.0.0.0/8',
        ];

        foreach ($cidrs as $cidr) {
            $subnet = Subnet::fromCidr($cidr);
            $zonePrefix = 8 * \intdiv($subnet->networkSize() + 7, 8);

            // When
            $zones = $subnet->reverseZones();
            $blocks = \array_map([self::class, 'reverseZoneToSubnet'], $zones);

            // Then
            $this->assertSame(\array_values(\array_unique($zones)), $zones, "{$cidr} has duplicate zones");
            $this->assertSame($zones, Subnet::fromCidr($subnet->networkCidr())->reverseZones(), "{$cidr} depends on host bits");
            $this->assertSame(
                $subnet->addressCount(),
                \array_sum(\array_map(static fn (Subnet $block): int => $block->addressCount(), $blocks)),
                "{$cidr} zones do not cover the network exactly"
            );
            foreach ($blocks as $i => $block) {
                $this->assertStringEndsNotWith('.', $zones[$i], "{$cidr} zone has a trailing dot");
                $this->assertSame($zonePrefix, $block->networkSize(), "{$cidr} zone {$zones[$i]} is not at the next octet boundary");
                $this->assertTrue($subnet->contains($block), "{$cidr} zone {$zones[$i]} lies outside the network");
            }
            $sorted = $blocks;
            \usort($sorted, static fn (Subnet $a, Subnet $b): int => $a->compare($b));
            $this->assertSame(
                \array_map(static fn (Subnet $block): string => $block->cidr(), $sorted),
                \array_map(static fn (Subnet $block): string => $block->cidr(), $blocks),
                "{$cidr} zones are not in numeric network order"
            );
        }
    }

    /**
     * Independent oracle: parse a reverse-zone origin back into its network block.
     */
    private static function reverseZoneToSubnet(string $zone): Subnet
    {
        $labels = \explode('.', \substr($zone, 0, -\strlen('in-addr.arpa')));
        $octets = \array_reverse(\array_filter($labels, static fn (string $label): bool => $label !== ''));

        return new Subnet(\implode('.', \array_pad($octets, 4, '0')), 8 * \count($octets));
    }

    /**
     * @return array[]
     */
    public static function invalidIPV4Provider(): array
    {
        $invalid = 'New prefix must be larger than current prefix';
        $max     = 'New prefix cannot exceed 32';

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
            ['1.2.3.4', 32, 100, $max],
        ];
    }

    /**
     * @param string $inputString
     * @param int    $networkSize
     * @param int    $newNetworkSize
     * @param mixed  $expectedMessage
     */
    #[DataProvider('invalidIPV4Provider')]
    public function testInvalidSplitIPV4(string $inputString, int $networkSize, int $newNetworkSize, string $expectedMessage)
    {
        // Given
        $subnet = new Subnet($inputString, $networkSize);

        // When
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        // Then
        $subnet->split($newNetworkSize);
    }

    public static function validIPV4Provider()
    {
        return [
            [
                '10.0.0.0',
                31,
                32,
                [
                    '10.0.0.0/32',
                    '10.0.0.1/32',
                ],
            ],
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
            // High IP address tests (>= 128.0.0.0) - signed integer edge cases
            [
                '128.0.0.0',
                1,
                2,
                [
                    '128.0.0.0/2',
                    '192.0.0.0/2',
                ],
            ],
            [
                '200.0.0.0',
                8,
                10,
                [
                    '200.0.0.0/10',
                    '200.64.0.0/10',
                    '200.128.0.0/10',
                    '200.192.0.0/10',
                ],
            ],
            [
                '200.100.0.0',
                16,
                18,
                [
                    '200.100.0.0/18',
                    '200.100.64.0/18',
                    '200.100.128.0/18',
                    '200.100.192.0/18',
                ],
            ],
            [
                '255.255.255.0',
                24,
                26,
                [
                    '255.255.255.0/26',
                    '255.255.255.64/26',
                    '255.255.255.128/26',
                    '255.255.255.192/26',
                ],
            ],
            [
                '255.255.255.252',
                30,
                32,
                [
                    '255.255.255.252/32',
                    '255.255.255.253/32',
                    '255.255.255.254/32',
                    '255.255.255.255/32',
                ],
            ],
            [
                '224.0.0.0',
                4,
                6,
                [
                    '224.0.0.0/6',
                    '228.0.0.0/6',
                    '232.0.0.0/6',
                    '236.0.0.0/6',
                ],
            ],
            [
                '240.0.0.0',
                4,
                5,
                [
                    '240.0.0.0/5',
                    '248.0.0.0/5',
                ],
            ],
        ];
    }

    /**
     * @param string $inputString
     * @param int    $networkSize
     * @param int    $newNetworkSize
     * @param array  $expectedSubnets
     */
    #[DataProvider('validIPV4Provider')]
    public function testValidSplitIPV4(string $inputString, int $networkSize, int $newNetworkSize, array $expectedSubnets)
    {
        // Given
        $subnet = new Subnet($inputString, $networkSize);

        // When
        $splitSubnets = $subnet->split($newNetworkSize);

        // Then
        $splitSubnetCidrNotations = \array_map(
            function ($newSplitSubnet) {
                return  $newSplitSubnet->cidr();
            },
            $splitSubnets
        );
        $this->assertSame($expectedSubnets, $splitSubnetCidrNotations);
    }

    /**
     * @param string $inputString
     * @param int    $networkSize
     * @param int    $newNetworkSize
     * @param array  $expectedSubnets
     */
    #[Test]
    #[DataProvider('validIPV4Provider')]
    public function splitIteratorYieldsSameSubnetsAsSplit(string $inputString, int $networkSize, int $newNetworkSize, array $expectedSubnets): void
    {
        // Given
        $subnet = new Subnet($inputString, $networkSize);

        // When
        $cidrs = \array_map(
            fn (Subnet $split) => $split->cidr(),
            \iterator_to_array($subnet->splitIterator($newNetworkSize), false)
        );

        // Then
        $this->assertSame($expectedSubnets, $cidrs);
    }

    /**
     * @param string $inputString
     * @param int    $networkSize
     * @param int    $newNetworkSize
     * @param string $expectedMessage
     */
    #[Test]
    #[DataProvider('invalidIPV4Provider')]
    public function splitIteratorThrowsOnCallWithoutIterating(string $inputString, int $networkSize, int $newNetworkSize, string $expectedMessage): void
    {
        // Given
        $subnet = new Subnet($inputString, $networkSize);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        // When - the generator is never advanced
        $subnet->splitIterator($newNetworkSize);
    }

    #[Test]
    #[DataProvider('dataProviderForIndexedSubnets')]
    public function testSubnetAtReturnsCanonicalChild(string $cidr, int $newPrefix, int $index, string $expectedCidr): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $child = $subnet->subnetAt($newPrefix, $index);

        // Then
        $this->assertSame($expectedCidr, $child->cidr());
        $this->assertSame($child->networkCidr(), $child->cidr());
        $this->assertTrue($subnet->contains($child));
        $this->assertSame($cidr, $subnet->cidr());
    }

    /**
     * @return array<string, array{string, int, int, string}>
     */
    public static function dataProviderForIndexedSubnets(): array
    {
        return [
            'first child of host-bit parent'  => ['192.0.2.200/24', 26, 0, '192.0.2.0/26'],
            'second child of host-bit parent' => ['192.0.2.200/24', 26, 1, '192.0.2.64/26'],
            'third child of host-bit parent'  => ['192.0.2.200/24', 26, 2, '192.0.2.128/26'],
            'last child of host-bit parent'   => ['192.0.2.200/24', 26, 3, '192.0.2.192/26'],
            '/30 first /31'                   => ['192.0.2.7/30', 31, 0, '192.0.2.4/31'],
            '/30 last /31'                    => ['192.0.2.7/30', 31, 1, '192.0.2.6/31'],
            '/31 first /32'                   => ['192.0.2.7/31', 32, 0, '192.0.2.6/32'],
            '/31 last /32'                    => ['192.0.2.7/31', 32, 1, '192.0.2.7/32'],
            '/0 first /1'                     => ['203.0.113.7/0', 1, 0, '0.0.0.0/1'],
            '/0 last /1'                      => ['203.0.113.7/0', 1, 1, '128.0.0.0/1'],
            '/0 first /32'                    => ['0.0.0.0/0', 32, 0, '0.0.0.0/32'],
            '/0 upper half /32'               => ['0.0.0.0/0', 32, 2_147_483_648, '128.0.0.0/32'],
            '/0 last /32'                     => ['203.0.113.7/0', 32, 4_294_967_295, '255.255.255.255/32'],
            '/0 last /24'                     => ['0.0.0.0/0', 24, 16_777_215, '255.255.255.0/24'],
            'nonzero network last /32'        => ['255.255.255.255/1', 32, 2_147_483_647, '255.255.255.255/32'],
            'last /30 to /31'                 => ['255.255.255.255/30', 31, 1, '255.255.255.254/31'],
        ];
    }

    /**
     * @param list<string> $expectedSubnets
     */
    #[Test]
    #[DataProvider('validIPV4Provider')]
    public function testSubnetAtMatchesEveryChildOfSplit(string $ip, int $prefix, int $newPrefix, array $expectedSubnets): void
    {
        // Given
        $subnet = new Subnet($ip, $prefix);

        // When
        $children = \iterator_to_array($subnet->splitIterator($newPrefix));
        $indexedCidrs = \array_map(
            static fn (int $index): string => $subnet->subnetAt($newPrefix, $index)->cidr(),
            \array_keys($children)
        );
        $splitCidrs = \array_map(static fn (Subnet $child): string => $child->cidr(), \array_values($children));

        // Then
        $this->assertSame($expectedSubnets, $indexedCidrs);
        $this->assertSame($splitCidrs, $indexedCidrs);
    }

    #[Test]
    #[DataProvider('dataProviderForInvalidSubnetIndices')]
    public function testSubnetAtRejectsIndicesOutsideChildCount(string $cidr, int $newPrefix, int $index): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // Then
        $this->expectException(\OutOfRangeException::class);

        // When
        $subnet->subnetAt($newPrefix, $index);
    }

    /**
     * @return array<string, array{string, int, int}>
     */
    public static function dataProviderForInvalidSubnetIndices(): array
    {
        return [
            'negative index'        => ['192.0.2.200/24', 26, -1],
            'index equal to count'  => ['192.0.2.200/24', 26, 4],
            'index beyond count'    => ['192.0.2.200/24', 26, 5],
            'minimum integer'       => ['192.0.2.200/24', 26, \PHP_INT_MIN],
            'maximum integer'       => ['192.0.2.200/24', 26, \PHP_INT_MAX],
            '/31 past last /32'     => ['255.255.255.255/31', 32, 2],
            '/0 negative /32 index' => ['0.0.0.0/0', 32, -1],
            '/0 past last /32'      => ['0.0.0.0/0', 32, 4_294_967_296],
            '/0 maximum /32 index'  => ['0.0.0.0/0', 32, \PHP_INT_MAX],
            '/0 maximum /1 index'   => ['0.0.0.0/0', 1, \PHP_INT_MAX],
            '/0 minimum /1 index'   => ['0.0.0.0/0', 1, \PHP_INT_MIN],
            '/0 past last /1'       => ['0.0.0.0/0', 1, 2],
        ];
    }

    #[Test]
    #[DataProvider('invalidIPV4Provider')]
    #[DataProvider('dataProviderForInvalidChildPrefixes')]
    public function testSubnetAtValidatesPrefixBeforeIndex(string $ip, int $prefix, int $newPrefix, string $expectedMessage): void
    {
        // Given
        $subnet = new Subnet($ip, $prefix);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        // When - both prefix and index are invalid
        $subnet->subnetAt($newPrefix, \PHP_INT_MAX);
    }

    /**
     * @return array<string, array{string, int, int, string}>
     */
    public static function dataProviderForInvalidChildPrefixes(): array
    {
        $invalid = 'New prefix must be larger than current prefix';
        $max = 'New prefix cannot exceed 32';

        return [
            'coarser prefix'          => ['192.0.2.1', 24, 23, $invalid],
            'zero prefix'             => ['192.0.2.1', 24, 0, $invalid],
            'negative prefix'         => ['192.0.2.1', 24, -1, $invalid],
            'minimum integer prefix'  => ['192.0.2.1', 24, \PHP_INT_MIN, $invalid],
            'maximum integer prefix'  => ['192.0.2.1', 24, \PHP_INT_MAX, $max],
            '/0 equal prefix'         => ['0.0.0.0', 0, 0, $invalid],
            '/0 prefix exceeds IPv4'  => ['0.0.0.0', 0, 33, $max],
            '/32 prefix exceeds IPv4' => ['255.255.255.255', 32, 33, $max],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForSupernets')]
    public function testSupernetReturnsCanonicalEnclosingBlock(string $cidr, int $prefix, string $expectedCidr): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $supernet = $subnet->supernet($prefix);

        // Then
        $this->assertSame($expectedCidr, $supernet->cidr());
        $this->assertSame($prefix, $supernet->networkSize());
        $this->assertSame($supernet->networkCidr(), $supernet->cidr());
        $this->assertSame($supernet->networkAddress()->asQuads(), $supernet->ipAddress()->asQuads());
        $this->assertTrue($supernet->contains($subnet));
        $this->assertSame($cidr, $subnet->cidr());
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function dataProviderForSupernets(): array
    {
        return [
            '/24 to /16'                 => ['192.168.1.0/24', 16, '192.168.0.0/16'],
            '/24 to /8'                  => ['192.168.1.0/24', 8, '192.0.0.0/8'],
            '/24 to /0'                  => ['192.168.1.0/24', 0, '0.0.0.0/0'],
            '/24 to /23'                 => ['192.168.1.0/24', 23, '192.168.0.0/23'],
            '/24 equal prefix'           => ['192.168.1.0/24', 24, '192.168.1.0/24'],
            'host bits set to /16'       => ['192.168.1.77/24', 16, '192.168.0.0/16'],
            'host bits set equal prefix' => ['192.168.1.77/24', 24, '192.168.1.0/24'],
            '/32 to /31'                 => ['10.0.0.5/32', 31, '10.0.0.4/31'],
            '/32 equal prefix'           => ['10.0.0.5/32', 32, '10.0.0.5/32'],
            '/32 to /0'                  => ['255.255.255.255/32', 0, '0.0.0.0/0'],
            '/31 to /30'                 => ['10.0.0.6/31', 30, '10.0.0.4/30'],
            '/31 host bits set to /30'   => ['10.0.0.7/31', 30, '10.0.0.4/30'],
            '/31 host bits set equal'    => ['10.0.0.7/31', 31, '10.0.0.6/31'],
            '/0 equal prefix'            => ['203.0.113.7/0', 0, '0.0.0.0/0'],
            'top of address space to /1' => ['255.255.255.255/31', 1, '128.0.0.0/1'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForSupernetRoundTrips')]
    public function testSupernetContainsOriginalAtEveryValidPrefix(string $cidr): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);
        $prefixes = \range(0, $subnet->networkSize());

        // When
        $supernets = \array_map(static fn (int $prefix): Subnet => $subnet->supernet($prefix), $prefixes);

        // Then
        $this->assertSame($prefixes, \array_map(static fn (Subnet $supernet): int => $supernet->networkSize(), $supernets));
        $this->assertSame(
            \array_fill(0, \count($prefixes), true),
            \array_map(static fn (Subnet $supernet): bool => $supernet->contains($subnet), $supernets)
        );
        $this->assertSame(
            \array_map(static fn (Subnet $supernet): string => $supernet->networkCidr(), $supernets),
            \array_map(static fn (Subnet $supernet): string => $supernet->cidr(), $supernets)
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dataProviderForSupernetRoundTrips(): array
    {
        return [
            '/24'               => ['192.168.1.0/24'],
            '/24 host bits set' => ['192.168.1.77/24'],
            '/31'               => ['10.0.0.7/31'],
            '/32'               => ['10.0.0.5/32'],
            '/0'                => ['203.0.113.7/0'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForInvalidSupernetPrefixes')]
    public function testSupernetRejectsPrefixOutsideRange(string $cidr, int $prefix, string $expectedMessage): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        // When
        $subnet->supernet($prefix);
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function dataProviderForInvalidSupernetPrefixes(): array
    {
        return [
            'current plus one'       => ['192.168.1.0/24', 25, 'Supernet prefix must be between 0 and 24, got 25'],
            'negative prefix'        => ['192.168.1.0/24', -1, 'Supernet prefix must be between 0 and 24, got -1'],
            'prefix exceeds IPv4'    => ['192.168.1.0/24', 33, 'Supernet prefix must be between 0 and 24, got 33'],
            'minimum integer prefix' => ['192.168.1.0/24', \PHP_INT_MIN, 'Supernet prefix must be between 0 and 24, got ' . \PHP_INT_MIN],
            'maximum integer prefix' => ['192.168.1.0/24', \PHP_INT_MAX, 'Supernet prefix must be between 0 and 24, got ' . \PHP_INT_MAX],
            '/32 prefix exceeds'     => ['10.0.0.5/32', 33, 'Supernet prefix must be between 0 and 32, got 33'],
            '/31 current plus one'   => ['10.0.0.6/31', 32, 'Supernet prefix must be between 0 and 31, got 32'],
            '/0 current plus one'    => ['0.0.0.0/0', 1, 'Supernet prefix must be between 0 and 0, got 1'],
            '/0 negative prefix'     => ['0.0.0.0/0', -1, 'Supernet prefix must be between 0 and 0, got -1'],
        ];
    }

    #[Test]
    public function splitIteratorIsLazyForEntireAddressSpace(): void
    {
        // Given - /0 into /32 would be 4.3 billion subnets if materialized
        $subnet = new Subnet('0.0.0.0', 0);

        // When
        $first = [];
        foreach ($subnet->splitIterator(32) as $split) {
            $first[] = $split->cidr();
            if (\count($first) === 3) {
                break;
            }
        }

        // Then
        $this->assertSame(['0.0.0.0/32', '0.0.0.1/32', '0.0.0.2/32'], $first);
    }
}
