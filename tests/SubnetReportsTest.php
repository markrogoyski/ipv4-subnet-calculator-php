<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\Subnet;
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
     * __toString returns CIDR notation
     */
    #[Test]
    public function testToStringReturnsCidr(): void
    {
        // Given
        $subnet = new Subnet('192.168.112.203', 23);

        // When
        $result = (string) $subnet;

        // Then
        $this->assertSame('192.168.112.203/23', $result);
    }
}
