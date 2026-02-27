<?php

declare(strict_types=1);

namespace IPv4\Tests\Internal;

use IPv4\Subnet;
use IPv4\Internal\SubnetReport;
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
}
