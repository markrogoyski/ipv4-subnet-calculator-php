<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class SubnetReportsTest extends \PHPUnit\Framework\TestCase
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
     * toArray returns proper structure
     */
    #[Test]
    public function testGetSubnetArrayReport(): void
    {
        // When
        $report = $this->sub->toArray();

        // Then
        $this->assertIsArray($report);
        $this->assertArrayHasKey('cidr', $report);
        $this->assertArrayHasKey('ip_address', $report);
        $this->assertArrayHasKey('address_type', $report);
        $this->assertArrayHasKey('network_class', $report);
        $this->assertArrayHasKey('subnet_mask', $report);
        $this->assertArrayHasKey('wildcard_mask', $report);
        $this->assertArrayHasKey('network_portion', $report);
        $this->assertArrayHasKey('host_portion', $report);
        $this->assertArrayHasKey('network_size', $report);
        $this->assertArrayHasKey('address_count', $report);
        $this->assertArrayHasKey('host_count', $report);
        $this->assertArrayHasKey('network_address', $report);
        $this->assertArrayHasKey('broadcast_address', $report);
        $this->assertArrayHasKey('min_host', $report);
        $this->assertArrayHasKey('max_host', $report);
        $this->assertArrayHasKey('arpa_domain', $report);
    }

    /**
     * toArray arpa_domain reflects the input IP address
     */
    #[Test]
    public function arrayReportArpaDomainUsesInputIpAddress(): void
    {
        // Given
        $subnet = $this->sub;

        // When
        $report = $subnet->toArray();

        // Then
        $this->assertSame('203.112.168.192.in-addr.arpa', $report['arpa_domain']);
    }

    /**
     * toArray reports every field with its exact value, in order
     *
     * @param array<string, mixed> $expected
     */
    #[Test]
    #[DataProvider('dataProviderForArrayReport')]
    public function arrayReportMatchesExpectedValues(string $ip, int $prefix, array $expected): void
    {
        // Given
        $subnet = new Subnet($ip, $prefix);

        // When
        $report = $subnet->toArray();

        // Then
        $this->assertSame($expected, $report);
    }

    /**
     * @return array<string, array{ip: string, prefix: int, expected: array<string, mixed>}>
     */
    public static function dataProviderForArrayReport(): array
    {
        return [
            '/23 with host bits set' => [
                'ip'       => '192.168.112.203',
                'prefix'   => 23,
                'expected' => [
                    'cidr'              => '192.168.112.0/23',
                    'ip_address'        => [
                        'quads'   => '192.168.112.203',
                        'hex'     => 'C0A870CB',
                        'binary'  => '11000000101010000111000011001011',
                        'integer' => 3232264395,
                    ],
                    'address_type'      => 'private',
                    'network_class'     => [
                        'class'          => 'C',
                        'default_mask'   => '255.255.255.0',
                        'default_prefix' => 24,
                        'is_classful'    => false,
                    ],
                    'subnet_mask'       => [
                        'quads'   => '255.255.254.0',
                        'hex'     => 'FFFFFE00',
                        'binary'  => '11111111111111111111111000000000',
                        'integer' => 4294966784,
                    ],
                    'wildcard_mask'     => [
                        'quads'   => '0.0.1.255',
                        'hex'     => '000001FF',
                        'binary'  => '00000000000000000000000111111111',
                        'integer' => 511,
                    ],
                    'network_portion'   => [
                        'quads'   => '192.168.112.0',
                        'hex'     => 'C0A87000',
                        'binary'  => '11000000101010000111000000000000',
                        'integer' => 3232264192,
                    ],
                    'host_portion'      => [
                        'quads'   => '0.0.0.203',
                        'hex'     => '000000CB',
                        'binary'  => '00000000000000000000000011001011',
                        'integer' => 203,
                    ],
                    'network_size'      => 23,
                    'address_count'     => 512,
                    'host_count'        => 510,
                    'network_address'   => '192.168.112.0',
                    'broadcast_address' => '192.168.113.255',
                    'min_host'          => '192.168.112.1',
                    'max_host'          => '192.168.113.254',
                    'arpa_domain'       => '203.112.168.192.in-addr.arpa',
                ],
            ],
            '/31 point-to-point (RFC 3021)' => [
                'ip'       => '192.168.1.0',
                'prefix'   => 31,
                'expected' => [
                    'cidr'              => '192.168.1.0/31',
                    'ip_address'        => [
                        'quads'   => '192.168.1.0',
                        'hex'     => 'C0A80100',
                        'binary'  => '11000000101010000000000100000000',
                        'integer' => 3232235776,
                    ],
                    'address_type'      => 'private',
                    'network_class'     => [
                        'class'          => 'C',
                        'default_mask'   => '255.255.255.0',
                        'default_prefix' => 24,
                        'is_classful'    => false,
                    ],
                    'subnet_mask'       => [
                        'quads'   => '255.255.255.254',
                        'hex'     => 'FFFFFFFE',
                        'binary'  => '11111111111111111111111111111110',
                        'integer' => 4294967294,
                    ],
                    'wildcard_mask'     => [
                        'quads'   => '0.0.0.1',
                        'hex'     => '00000001',
                        'binary'  => '00000000000000000000000000000001',
                        'integer' => 1,
                    ],
                    'network_portion'   => [
                        'quads'   => '192.168.1.0',
                        'hex'     => 'C0A80100',
                        'binary'  => '11000000101010000000000100000000',
                        'integer' => 3232235776,
                    ],
                    'host_portion'      => [
                        'quads'   => '0.0.0.0',
                        'hex'     => '00000000',
                        'binary'  => '00000000000000000000000000000000',
                        'integer' => 0,
                    ],
                    'network_size'      => 31,
                    'address_count'     => 2,
                    'host_count'        => 2,
                    'network_address'   => '192.168.1.0',
                    'broadcast_address' => '192.168.1.1',
                    'min_host'          => '192.168.1.0',
                    'max_host'          => '192.168.1.1',
                    'arpa_domain'       => '0.1.168.192.in-addr.arpa',
                ],
            ],
            '/32 single host' => [
                'ip'       => '192.168.1.1',
                'prefix'   => 32,
                'expected' => [
                    'cidr'              => '192.168.1.1/32',
                    'ip_address'        => [
                        'quads'   => '192.168.1.1',
                        'hex'     => 'C0A80101',
                        'binary'  => '11000000101010000000000100000001',
                        'integer' => 3232235777,
                    ],
                    'address_type'      => 'private',
                    'network_class'     => [
                        'class'          => 'C',
                        'default_mask'   => '255.255.255.0',
                        'default_prefix' => 24,
                        'is_classful'    => false,
                    ],
                    'subnet_mask'       => [
                        'quads'   => '255.255.255.255',
                        'hex'     => 'FFFFFFFF',
                        'binary'  => '11111111111111111111111111111111',
                        'integer' => 4294967295,
                    ],
                    'wildcard_mask'     => [
                        'quads'   => '0.0.0.0',
                        'hex'     => '00000000',
                        'binary'  => '00000000000000000000000000000000',
                        'integer' => 0,
                    ],
                    'network_portion'   => [
                        'quads'   => '192.168.1.1',
                        'hex'     => 'C0A80101',
                        'binary'  => '11000000101010000000000100000001',
                        'integer' => 3232235777,
                    ],
                    'host_portion'      => [
                        'quads'   => '0.0.0.0',
                        'hex'     => '00000000',
                        'binary'  => '00000000000000000000000000000000',
                        'integer' => 0,
                    ],
                    'network_size'      => 32,
                    'address_count'     => 1,
                    'host_count'        => 1,
                    'network_address'   => '192.168.1.1',
                    'broadcast_address' => '192.168.1.1',
                    'min_host'          => '192.168.1.1',
                    'max_host'          => '192.168.1.1',
                    'arpa_domain'       => '1.1.168.192.in-addr.arpa',
                ],
            ],
        ];
    }

    /**
     * toJson returns valid JSON string
     */
    #[Test]
    public function testGetSubnetJsonReport(): void
    {
        // When
        $json = $this->sub->toJson();

        // Then
        $this->assertIsString($json);
    }

    /**
     * toJson leaves slashes in CIDR notation unescaped
     */
    #[Test]
    public function testGetSubnetJsonReportDoesNotEscapeSlashes(): void
    {
        // When
        $json = $this->sub->toJson();

        // Then
        $this->assertStringContainsString('"cidr": "192.168.112.0/23"', $json);
        $this->assertStringNotContainsString('\/', $json);
    }

    /**
     * toPrintable outputs the expected report format
     */
    #[Test]
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
                Wildcard [ ] Mask:   .+                                             \n
                Network [ ] Portion: .+                                             \n
                Host [ ] Portion:    .+                                             \n
                                                                                    \n
                Address [ ] Type:                        \s+ .+?                    \n
                Network [ ] Class:                       \s+ [A-E]                  \n
                (Classful:                               \s+ .+?                    \n)?
                Number [ ] of [ ] IP [ ] Addresses:      \s+ \d+                    \n
                Number [ ] of [ ] Addressable [ ] Hosts: \s+ \d+                    \n
                IP [ ] Address [ ] Range:                \s+ .+?                    \n
                Broadcast [ ] Address:                   \s+ .+?                    \n
                Min [ ] Host:                            \s+ .+?                    \n
                Max [ ] Host:                            \s+ .+?                    \n
                ARPA [ ] Domain:                         \s+ .+?                    \n
                $
            /xms
        ');

        // When
        echo $this->sub->toPrintable();
    }

    /**
     * toPrintable returns string
     */
    #[Test]
    public function testGetPrintableReport(): void
    {
        // When
        $report = $this->sub->toPrintable();

        // Then
        $this->assertIsString($report);
    }

    /**
     * toPrintable renders every row with its exact values and column widths
     */
    #[Test]
    #[DataProvider('dataProviderForPrintableReport')]
    public function printableReportMatchesExpectedOutput(string $ip, int $prefix, string $expected): void
    {
        // Given
        $subnet = new Subnet($ip, $prefix);

        // When
        $report = $subnet->toPrintable();

        // Then
        $this->assertSame($expected, $report);
    }

    /**
     * @return array<string, array{ip: string, prefix: int, expected: string}>
     */
    public static function dataProviderForPrintableReport(): array
    {
        return [
            '/23 with host bits set' => [
                'ip'       => '192.168.112.203',
                'prefix'   => 23,
                'expected' => <<<'REPORT'
                    192.168.112.0/23             Quads      Hex                           Binary    Integer
                    ------------------ --------------- -------- -------------------------------- ----------
                    IP Address:        192.168.112.203 C0A870CB 11000000101010000111000011001011 3232264395
                    Subnet Mask:         255.255.254.0 FFFFFE00 11111111111111111111111000000000 4294966784
                    Wildcard Mask:           0.0.1.255 000001FF 00000000000000000000000111111111        511
                    Network Portion:     192.168.112.0 C0A87000 11000000101010000111000000000000 3232264192
                    Host Portion:            0.0.0.203 000000CB 00000000000000000000000011001011        203

                    Address Type:                private
                    Network Class:               C
                    Classful:                    No (subnetted/supernetted)
                    Number of IP Addresses:      512
                    Number of Addressable Hosts: 510
                    IP Address Range:            192.168.112.0 - 192.168.113.255
                    Broadcast Address:           192.168.113.255
                    Min Host:                    192.168.112.1
                    Max Host:                    192.168.113.254
                    ARPA Domain:                 203.112.168.192.in-addr.arpa
                    REPORT . "\n",
            ],
            '/31 point-to-point (RFC 3021)' => [
                'ip'       => '192.168.1.0',
                'prefix'   => 31,
                'expected' => <<<'REPORT'
                    192.168.1.0/31               Quads      Hex                           Binary    Integer
                    ------------------ --------------- -------- -------------------------------- ----------
                    IP Address:            192.168.1.0 C0A80100 11000000101010000000000100000000 3232235776
                    Subnet Mask:       255.255.255.254 FFFFFFFE 11111111111111111111111111111110 4294967294
                    Wildcard Mask:             0.0.0.1 00000001 00000000000000000000000000000001          1
                    Network Portion:       192.168.1.0 C0A80100 11000000101010000000000100000000 3232235776
                    Host Portion:              0.0.0.0 00000000 00000000000000000000000000000000          0

                    Address Type:                private
                    Network Class:               C
                    Classful:                    No (subnetted/supernetted)
                    Number of IP Addresses:      2
                    Number of Addressable Hosts: 2
                    IP Address Range:            192.168.1.0 - 192.168.1.1
                    Broadcast Address:           192.168.1.1
                    Min Host:                    192.168.1.0
                    Max Host:                    192.168.1.1
                    ARPA Domain:                 0.1.168.192.in-addr.arpa
                    REPORT . "\n",
            ],
            '/32 single host' => [
                'ip'       => '192.168.1.1',
                'prefix'   => 32,
                'expected' => <<<'REPORT'
                    192.168.1.1/32               Quads      Hex                           Binary    Integer
                    ------------------ --------------- -------- -------------------------------- ----------
                    IP Address:            192.168.1.1 C0A80101 11000000101010000000000100000001 3232235777
                    Subnet Mask:       255.255.255.255 FFFFFFFF 11111111111111111111111111111111 4294967295
                    Wildcard Mask:             0.0.0.0 00000000 00000000000000000000000000000000          0
                    Network Portion:       192.168.1.1 C0A80101 11000000101010000000000100000001 3232235777
                    Host Portion:              0.0.0.0 00000000 00000000000000000000000000000000          0

                    Address Type:                private
                    Network Class:               C
                    Classful:                    No (subnetted/supernetted)
                    Number of IP Addresses:      1
                    Number of Addressable Hosts: 1
                    IP Address Range:            192.168.1.1 - 192.168.1.1
                    Broadcast Address:           192.168.1.1
                    Min Host:                    192.168.1.1
                    Max Host:                    192.168.1.1
                    ARPA Domain:                 1.1.168.192.in-addr.arpa
                    REPORT . "\n",
            ],
        ];
    }

    /**
     * Value objects returned by Subnet encode to their values, not {}
     */
    #[Test]
    public function testJsonEncodeOfReturnedValueObjects(): void
    {
        // Given
        $subnet = new Subnet('192.168.1.100', 24);
        $payload = [
            'cidr'     => $subnet->networkCidr(),
            'network'  => $subnet->networkAddress(),
            'mask'     => $subnet->mask(),
            'wildcard' => $subnet->wildcardMask(),
            'hosts'    => $subnet->hostRange(),
        ];

        // When
        $json = \json_encode($payload, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);

        // Then
        $this->assertSame(
            '{"cidr":"192.168.1.0/24","network":"192.168.1.0","mask":"255.255.255.0","wildcard":"0.0.0.255",'
            . '"hosts":{"start":"192.168.1.1","end":"192.168.1.254"}}',
            $json
        );
    }

    /**
     * \JsonSerializable interface
     */
    #[Test]
    public function testJsonSerializableInterface(): void
    {
        // When
        $json = \json_encode($this->sub);

        // Then
        $this->assertIsString($json);

        // And
        $decoded = \json_decode($json, true);
        $this->assertArrayHasKey('cidr', $decoded);
        $this->assertArrayHasKey('ip_address', $decoded);
        $this->assertArrayHasKey('address_type', $decoded);
        $this->assertArrayHasKey('network_class', $decoded);
        $this->assertArrayHasKey('subnet_mask', $decoded);
        $this->assertArrayHasKey('wildcard_mask', $decoded);
        $this->assertArrayHasKey('network_portion', $decoded);
        $this->assertArrayHasKey('host_portion', $decoded);
        $this->assertArrayHasKey('network_size', $decoded);
        $this->assertArrayHasKey('address_count', $decoded);
        $this->assertArrayHasKey('host_count', $decoded);
        $this->assertArrayHasKey('network_address', $decoded);
        $this->assertArrayHasKey('broadcast_address', $decoded);
        $this->assertArrayHasKey('min_host', $decoded);
        $this->assertArrayHasKey('max_host', $decoded);
        $this->assertArrayHasKey('arpa_domain', $decoded);
    }

    /**
     * __toString returns canonical CIDR notation using the network address
     */
    #[Test]
    #[DataProvider('dataProviderForToString')]
    public function toStringReturnsNetworkCidr(string $ip, int $prefix, string $expected): void
    {
        // Given
        $subnet = new Subnet($ip, $prefix);

        // When
        $result = (string) $subnet;

        // Then
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function dataProviderForToString(): array
    {
        return [
            'host IP normalized to network' => ['192.168.112.203', 23, '192.168.112.0/23'],
            'network IP unchanged'          => ['192.168.112.0', 23, '192.168.112.0/23'],
            '/0'                            => ['10.1.2.3', 0, '0.0.0.0/0'],
            '/31 upper address'             => ['10.0.0.1', 31, '10.0.0.0/31'],
            '/32'                           => ['10.0.0.1', 32, '10.0.0.1/32'],
        ];
    }

    /**
     * Equal subnets given by different host IPs have the same string form
     */
    #[Test]
    public function equalSubnetsHaveIdenticalStringForms(): void
    {
        // Given
        $a = Subnet::fromCidr('192.168.1.10/24');
        $b = Subnet::fromCidr('192.168.1.200/24');

        // When
        $keyed = [(string) $a => $a, (string) $b => $b];

        // Then
        $this->assertTrue($a->equals($b));
        $this->assertSame(['192.168.1.0/24'], \array_keys($keyed));
    }

    /**
     * __toString agrees with the toArray() cidr field
     */
    #[Test]
    public function toStringMatchesArrayReportCidr(): void
    {
        // Given
        $subnet = Subnet::fromCidr('172.16.5.99/20');

        // When
        $string = (string) $subnet;
        $report = $subnet->toArray();

        // Then
        $this->assertSame($report['cidr'], $string);
    }

    /**
     * toArray reports counts and host bounds for /0, /31 and /32
     */
    #[Test]
    #[DataProvider('dataProviderForEdgeCasePrefixes')]
    public function arrayReportHandlesEdgeCasePrefixes(
        string $ip,
        int $prefix,
        int $expectedAddressCount,
        int $expectedHostCount,
        string $expectedMinHost,
        string $expectedMaxHost
    ): void {
        // Given
        $subnet = new Subnet($ip, $prefix);

        // When
        $report = $subnet->toArray();

        // Then
        $this->assertSame(
            [$expectedAddressCount, $expectedHostCount, $expectedMinHost, $expectedMaxHost],
            [$report['address_count'], $report['host_count'], $report['min_host'], $report['max_host']]
        );
    }

    /**
     * toPrintable reports counts and host bounds for /0, /31 and /32
     */
    #[Test]
    #[DataProvider('dataProviderForEdgeCasePrefixes')]
    public function printableReportHandlesEdgeCasePrefixes(
        string $ip,
        int $prefix,
        int $expectedAddressCount,
        int $expectedHostCount,
        string $expectedMinHost,
        string $expectedMaxHost
    ): void {
        // Given
        $subnet = new Subnet($ip, $prefix);

        // When
        $report = $subnet->toPrintable();

        // Then
        $this->assertStringContainsString(\sprintf("%-28s %d\n", 'Number of IP Addresses:', $expectedAddressCount), $report);
        $this->assertStringContainsString(\sprintf("%-28s %d\n", 'Number of Addressable Hosts:', $expectedHostCount), $report);
        $this->assertStringContainsString(\sprintf("%-28s %s\n", 'Min Host:', $expectedMinHost), $report);
        $this->assertStringContainsString(\sprintf("%-28s %s\n", 'Max Host:', $expectedMaxHost), $report);
    }

    /**
     * @return array<string, array{ip: string, prefix: int, expectedAddressCount: int, expectedHostCount: int, expectedMinHost: string, expectedMaxHost: string}>
     */
    public static function dataProviderForEdgeCasePrefixes(): array
    {
        return [
            '/0 entire IPv4 space' => [
                'ip'                   => '0.0.0.0',
                'prefix'               => 0,
                'expectedAddressCount' => 4294967296,
                'expectedHostCount'    => 4294967294,
                'expectedMinHost'      => '0.0.0.1',
                'expectedMaxHost'      => '255.255.255.254',
            ],
            '/31 point-to-point (RFC 3021)' => [
                'ip'                   => '192.168.1.0',
                'prefix'               => 31,
                'expectedAddressCount' => 2,
                'expectedHostCount'    => 2,
                'expectedMinHost'      => '192.168.1.0',
                'expectedMaxHost'      => '192.168.1.1',
            ],
            '/32 single host' => [
                'ip'                   => '192.168.1.1',
                'prefix'               => 32,
                'expectedAddressCount' => 1,
                'expectedHostCount'    => 1,
                'expectedMinHost'      => '192.168.1.1',
                'expectedMaxHost'      => '192.168.1.1',
            ],
        ];
    }

    /**
     * toPrintable includes the Classful: line for classes A, B and C
     */
    #[Test]
    #[DataProvider('dataProviderForClassfulLinePresent')]
    public function printableReportIncludesClassfulLineForClassesAThroughC(string $ip, int $prefix, string $expectedClassfulLine): void
    {
        // Given
        $subnet = new Subnet($ip, $prefix);

        // When
        $report = $subnet->toPrintable();

        // Then
        $this->assertStringContainsString($expectedClassfulLine, $report);
    }

    /**
     * @return array<string, array{ip: string, prefix: int, expectedClassfulLine: string}>
     */
    public static function dataProviderForClassfulLinePresent(): array
    {
        return [
            'class A classful'  => ['ip' => '10.0.0.0', 'prefix' => 8, 'expectedClassfulLine' => "Classful:                    Yes\n"],
            'class C subnetted' => ['ip' => '192.168.1.0', 'prefix' => 25, 'expectedClassfulLine' => "Classful:                    No (subnetted/supernetted)\n"],
        ];
    }

    /**
     * toPrintable omits the Classful: line for classes D and E, which have no default mask
     */
    #[Test]
    #[DataProvider('dataProviderForClassfulLineAbsent')]
    public function printableReportOmitsClassfulLineForClassesDAndE(string $ip, int $prefix): void
    {
        // Given
        $subnet = new Subnet($ip, $prefix);

        // When
        $report = $subnet->toPrintable();

        // Then
        $this->assertStringNotContainsString('Classful:', $report);
    }

    /**
     * @return array<string, array{ip: string, prefix: int}>
     */
    public static function dataProviderForClassfulLineAbsent(): array
    {
        return [
            'class D multicast' => ['ip' => '224.0.0.1', 'prefix' => 4],
            'class E reserved'  => ['ip' => '240.0.0.1', 'prefix' => 32],
        ];
    }
}
