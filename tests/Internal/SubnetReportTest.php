<?php

declare(strict_types=1);

namespace IPv4\Tests\Internal;

use IPv4\Subnet;
use IPv4\Internal\SubnetReport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class SubnetReportTest extends \PHPUnit\Framework\TestCase
{
    /** @var Subnet */
    private $sub;

    /**
     * Set up
     */
    public function setUp(): void
    {
        $this->sub = new Subnet('192.168.112.203', 23);
    }

    /**
     * createArray
     */
    #[Test]
    public function testCreateArrayReport(): void
    {
        // When
        $report = SubnetReport::createArray($this->sub);

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
     * createJson
     */
    #[Test]
    public function testCreateJsonReport(): void
    {
        // When
        $json = SubnetReport::createJson($this->sub);

        // Then
        $this->assertIsString($json);
    }

    /**
     * createPrintable outputs the expected report format
     */
    #[Test]
    public function testPrintReport(): void
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
        echo SubnetReport::createPrintable($this->sub);
    }

    /**
     * createPrintable
     */
    #[Test]
    public function testCreatePrintableReport(): void
    {
        // When
        $report = SubnetReport::createPrintable($this->sub);

        // Then
        $this->assertIsString($report);
    }

    /**
     * createArray reports counts, host bounds and class info for edge-case prefixes
     *
     * @param array<string, mixed> $expected
     */
    #[Test]
    #[DataProvider('dataProviderForEdgeCaseSubnets')]
    public function createArrayReportsEdgeCaseSubnets(string $ip, int $prefix, array $expected, string $expectedSummary): void
    {
        // Given
        $subnet = new Subnet($ip, $prefix);

        // When
        $report = SubnetReport::createArray($subnet);

        // Then
        $this->assertSame($expected, [
            'network_class'     => $report['network_class'],
            'address_count'     => $report['address_count'],
            'host_count'        => $report['host_count'],
            'network_address'   => $report['network_address'],
            'broadcast_address' => $report['broadcast_address'],
            'min_host'          => $report['min_host'],
            'max_host'          => $report['max_host'],
        ]);
    }

    /**
     * createPrintable renders the summary section for edge-case prefixes, omitting Classful: for class D and E
     *
     * @param array<string, mixed> $expected
     */
    #[Test]
    #[DataProvider('dataProviderForEdgeCaseSubnets')]
    public function createPrintableRendersEdgeCaseSubnets(string $ip, int $prefix, array $expected, string $expectedSummary): void
    {
        // Given
        $subnet = new Subnet($ip, $prefix);

        // When
        $report = SubnetReport::createPrintable($subnet);

        // Then
        $this->assertStringEndsWith($expectedSummary, $report);
    }

    /**
     * @return array<string, array{ip: string, prefix: int, expected: array<string, mixed>, expectedSummary: string}>
     */
    public static function dataProviderForEdgeCaseSubnets(): array
    {
        return [
            '/0 entire IPv4 space' => [
                'ip'       => '0.0.0.0',
                'prefix'   => 0,
                'expected' => [
                    'network_class'     => ['class' => 'A', 'default_mask' => '255.0.0.0', 'default_prefix' => 8, 'is_classful' => false],
                    'address_count'     => 4294967296,
                    'host_count'        => 4294967294,
                    'network_address'   => '0.0.0.0',
                    'broadcast_address' => '255.255.255.255',
                    'min_host'          => '0.0.0.1',
                    'max_host'          => '255.255.255.254',
                ],
                'expectedSummary' => "Address Type:                this-network\n"
                    . "Network Class:               A\n"
                    . "Classful:                    No (subnetted/supernetted)\n"
                    . "Number of IP Addresses:      4294967296\n"
                    . "Number of Addressable Hosts: 4294967294\n"
                    . "IP Address Range:            0.0.0.0 - 255.255.255.255\n"
                    . "Broadcast Address:           255.255.255.255\n"
                    . "Min Host:                    0.0.0.1\n"
                    . "Max Host:                    255.255.255.254\n"
                    . "ARPA Domain:                 0.0.0.0.in-addr.arpa\n",
            ],
            '/8 classful' => [
                'ip'       => '10.0.0.0',
                'prefix'   => 8,
                'expected' => [
                    'network_class'     => ['class' => 'A', 'default_mask' => '255.0.0.0', 'default_prefix' => 8, 'is_classful' => true],
                    'address_count'     => 16777216,
                    'host_count'        => 16777214,
                    'network_address'   => '10.0.0.0',
                    'broadcast_address' => '10.255.255.255',
                    'min_host'          => '10.0.0.1',
                    'max_host'          => '10.255.255.254',
                ],
                'expectedSummary' => "Address Type:                private\n"
                    . "Network Class:               A\n"
                    . "Classful:                    Yes\n"
                    . "Number of IP Addresses:      16777216\n"
                    . "Number of Addressable Hosts: 16777214\n"
                    . "IP Address Range:            10.0.0.0 - 10.255.255.255\n"
                    . "Broadcast Address:           10.255.255.255\n"
                    . "Min Host:                    10.0.0.1\n"
                    . "Max Host:                    10.255.255.254\n"
                    . "ARPA Domain:                 0.0.0.10.in-addr.arpa\n",
            ],
            '/31 point-to-point (RFC 3021)' => [
                'ip'       => '192.168.1.0',
                'prefix'   => 31,
                'expected' => [
                    'network_class'     => ['class' => 'C', 'default_mask' => '255.255.255.0', 'default_prefix' => 24, 'is_classful' => false],
                    'address_count'     => 2,
                    'host_count'        => 2,
                    'network_address'   => '192.168.1.0',
                    'broadcast_address' => '192.168.1.1',
                    'min_host'          => '192.168.1.0',
                    'max_host'          => '192.168.1.1',
                ],
                'expectedSummary' => "Address Type:                private\n"
                    . "Network Class:               C\n"
                    . "Classful:                    No (subnetted/supernetted)\n"
                    . "Number of IP Addresses:      2\n"
                    . "Number of Addressable Hosts: 2\n"
                    . "IP Address Range:            192.168.1.0 - 192.168.1.1\n"
                    . "Broadcast Address:           192.168.1.1\n"
                    . "Min Host:                    192.168.1.0\n"
                    . "Max Host:                    192.168.1.1\n"
                    . "ARPA Domain:                 0.1.168.192.in-addr.arpa\n",
            ],
            '/32 single host' => [
                'ip'       => '192.168.1.1',
                'prefix'   => 32,
                'expected' => [
                    'network_class'     => ['class' => 'C', 'default_mask' => '255.255.255.0', 'default_prefix' => 24, 'is_classful' => false],
                    'address_count'     => 1,
                    'host_count'        => 1,
                    'network_address'   => '192.168.1.1',
                    'broadcast_address' => '192.168.1.1',
                    'min_host'          => '192.168.1.1',
                    'max_host'          => '192.168.1.1',
                ],
                'expectedSummary' => "Address Type:                private\n"
                    . "Network Class:               C\n"
                    . "Classful:                    No (subnetted/supernetted)\n"
                    . "Number of IP Addresses:      1\n"
                    . "Number of Addressable Hosts: 1\n"
                    . "IP Address Range:            192.168.1.1 - 192.168.1.1\n"
                    . "Broadcast Address:           192.168.1.1\n"
                    . "Min Host:                    192.168.1.1\n"
                    . "Max Host:                    192.168.1.1\n"
                    . "ARPA Domain:                 1.1.168.192.in-addr.arpa\n",
            ],
            'class D multicast (no Classful: line)' => [
                'ip'       => '224.0.0.1',
                'prefix'   => 4,
                'expected' => [
                    'network_class'     => ['class' => 'D', 'default_mask' => null, 'default_prefix' => null, 'is_classful' => false],
                    'address_count'     => 268435456,
                    'host_count'        => 268435454,
                    'network_address'   => '224.0.0.0',
                    'broadcast_address' => '239.255.255.255',
                    'min_host'          => '224.0.0.1',
                    'max_host'          => '239.255.255.254',
                ],
                'expectedSummary' => "Address Type:                multicast\n"
                    . "Network Class:               D\n"
                    . "Number of IP Addresses:      268435456\n"
                    . "Number of Addressable Hosts: 268435454\n"
                    . "IP Address Range:            224.0.0.0 - 239.255.255.255\n"
                    . "Broadcast Address:           239.255.255.255\n"
                    . "Min Host:                    224.0.0.1\n"
                    . "Max Host:                    239.255.255.254\n"
                    . "ARPA Domain:                 1.0.0.224.in-addr.arpa\n",
            ],
            'class E /32 (no Classful: line)' => [
                'ip'       => '240.0.0.1',
                'prefix'   => 32,
                'expected' => [
                    'network_class'     => ['class' => 'E', 'default_mask' => null, 'default_prefix' => null, 'is_classful' => false],
                    'address_count'     => 1,
                    'host_count'        => 1,
                    'network_address'   => '240.0.0.1',
                    'broadcast_address' => '240.0.0.1',
                    'min_host'          => '240.0.0.1',
                    'max_host'          => '240.0.0.1',
                ],
                'expectedSummary' => "Address Type:                reserved\n"
                    . "Network Class:               E\n"
                    . "Number of IP Addresses:      1\n"
                    . "Number of Addressable Hosts: 1\n"
                    . "IP Address Range:            240.0.0.1 - 240.0.0.1\n"
                    . "Broadcast Address:           240.0.0.1\n"
                    . "Min Host:                    240.0.0.1\n"
                    . "Max Host:                    240.0.0.1\n"
                    . "ARPA Domain:                 1.0.0.240.in-addr.arpa\n",
            ],
        ];
    }
}
