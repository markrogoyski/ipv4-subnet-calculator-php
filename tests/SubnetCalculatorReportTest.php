<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4;

class SubnetCalculatorReportTest extends \PHPUnit\Framework\TestCase
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
}
