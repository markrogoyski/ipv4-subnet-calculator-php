<?php
namespace IPv4;

// Precalculated constants for network 192.168.112.203/23.
const IP_ADDRESS               = '192.168.112.203';
const NETWORK_SIZE             = 23;
const NUMBER_IP_ADDRESSES      = 512;
const NUMBER_ADDRESSABLE_HOSTS = 510;
const LOWER_IP_ADDRESS_RANGE   = '192.168.112.0';
const UPPER_IP_ADDRESS_RANGE   = '192.168.113.255';
const BROADCAST_ADDRESS        = '192.168.113.255';
const IP_ADDRESS_HEX           = 'C0A870CB';
const IP_ADDRESS_BINARY        = '11000000101010000111000011001011';
const SUBNET_MASK              = '255.255.254.0';
const SUBNET_MASK_HEX          = 'FFFFFE00';
const SUBNET_MASK_BINARY       = '11111111111111111111111000000000';
const NETWORK                  = '192.168.112.0';
const NETWORK_HEX              = 'C0A87000';
const NETWORK_BINARY           = '11000000101010000111000000000000';
const HOST                     = '0.0.0.203';
const HOST_HEX                 = '000000CB';
const HOST_BINARY              = '00000000000000000000000011001011';

class SubnetCalculatorTest extends \PHPUnit_Framework_TestCase
{
    
    public function setUp()
    {
        $this->sub = new SubnetCalculator('192.168.112.203', 23);
    }

    public function testGetIPAddress()
    {
        $this->assertEquals($this->sub->getIPAddress(), IP_ADDRESS);
    }

    public function testGetNetworkSize()
    {
        $this->assertEquals($this->sub->getNetworkSize(), NETWORK_SIZE);
    }

    public function testGetNumberIPAddresses()
    {
        $this->assertEquals($this->sub->getNumberIPAddresses(), NUMBER_IP_ADDRESSES);
    }

    public function testGetNumberAddressableHosts()
    {
        $this->assertEquals($this->sub->getNumberAddressableHosts(), NUMBER_ADDRESSABLE_HOSTS);
    }

    public function testGetNumberAddressableHostsEdgeCases()
    {
        $sub = new SubnetCalculator('192.168.112.203', 32);
        $this->assertEquals(1, $sub->getNumberAddressableHosts());

        $sub = new SubnetCalculator('192.168.112.203', 31);
        $this->assertEquals(2, $sub->getNumberAddressableHosts());
    }

    public function testGetIPAddressRange()
    {
        $this->assertEquals($this->sub->getIPAddressRange()[0], LOWER_IP_ADDRESS_RANGE);
        $this->assertEquals($this->sub->getIPAddressRange()[1], UPPER_IP_ADDRESS_RANGE);
    }

    public function testGetBroadcastAddress()
    {
        $this->assertEquals($this->sub->getBroadcastAddress(), BROADCAST_ADDRESS);
    }

    public function testGetIPAddressQuads()
    {
        $this->assertEquals($this->sub->getIPAddressQuads()[0], explode('.', IP_ADDRESS)[0]);
        $this->assertEquals($this->sub->getIPAddressQuads()[1], explode('.', IP_ADDRESS)[1]);
        $this->assertEquals($this->sub->getIPAddressQuads()[2], explode('.', IP_ADDRESS)[2]);
        $this->assertEquals($this->sub->getIPAddressQuads()[3], explode('.', IP_ADDRESS)[3]);
    }

    public function testGetIPAddressHex()
    {
        $this->assertEquals($this->sub->getIPAddressHex(), IP_ADDRESS_HEX);
    }

    public function testGetIPAddressBinary()
    {
        $this->assertEquals($this->sub->getIPAddressBinary(), IP_ADDRESS_BINARY);
    }

    public function testGetSubnetMask()
    {
        $this->assertEquals($this->sub->getSubnetMask(), SUBNET_MASK);
    }

    public function testGetSubnetMaskQuads()
    {
        $this->assertEquals($this->sub->getSubnetMaskQuads()[0], explode('.', SUBNET_MASK)[0]);
        $this->assertEquals($this->sub->getSubnetMaskQuads()[1], explode('.', SUBNET_MASK)[1]);
        $this->assertEquals($this->sub->getSubnetMaskQuads()[2], explode('.', SUBNET_MASK)[2]);
        $this->assertEquals($this->sub->getSubnetMaskQuads()[3], explode('.', SUBNET_MASK)[3]);
    }

    public function testGetSubnetMaskHex()
    {
        $this->assertEquals($this->sub->getSubnetMaskHex(), SUBNET_MASK_HEX);
    }

    public function testGetSubnetMaskBinary()
    {
        $this->assertEquals($this->sub->getSubnetMaskBinary(), SUBNET_MASK_BINARY);
    }

    public function testGetNetworkPortion()
    {
        $this->assertEquals($this->sub->getNetworkPortion(), NETWORK);
    }

    public function testGetNetworkPortionQuads()
    {
        $this->assertEquals($this->sub->getNetworkPortionQuads()[0], explode('.', NETWORK)[0]);
        $this->assertEquals($this->sub->getNetworkPortionQuads()[1], explode('.', NETWORK)[1]);
        $this->assertEquals($this->sub->getNetworkPortionQuads()[2], explode('.', NETWORK)[2]);
        $this->assertEquals($this->sub->getNetworkPortionQuads()[3], explode('.', NETWORK)[3]);
    }

    public function testGetNetworkPortionHex()
    {
        $this->assertEquals($this->sub->getNetworkPortionHex(), NETWORK_HEX);
    }

    public function testGetNetworkPortionBinary()
    {
        $this->assertEquals($this->sub->getNetworkPortionBinary(), NETWORK_BINARY);
    }

    public function testGetHostPortion()
    {
        $this->assertEquals($this->sub->getHostPortion(), HOST);
    }

    public function testGetHostPortionQuads()
    {
        $this->assertEquals($this->sub->getHostPortionQuads()[0], explode('.', HOST)[0]);
        $this->assertEquals($this->sub->getHostPortionQuads()[1], explode('.', HOST)[1]);
        $this->assertEquals($this->sub->getHostPortionQuads()[2], explode('.', HOST)[2]);
        $this->assertEquals($this->sub->getHostPortionQuads()[3], explode('.', HOST)[3]);
    }

    public function testGetHostPortionHex()
    {
        $this->assertEquals($this->sub->getHostPortionHex(), HOST_HEX);
    }

    public function testGetHostPortionBinary()
    {
        $this->assertEquals($this->sub->getHostPortionBinary(), HOST_BINARY);
    }

    public function testValidateInputExceptionOnBadIPAddress()
    {
        $this->expectException(\Exception::class);
        $sub = new SubnetCalculator('555.444.333.222', 23);
    }

    public function testValidateInputExceptionOnBadNetworkSize()
    {
        $this->expectException(\Exception::class);
        $sub = new SubnetCalculator('192.168.112.203', 40);
    }

    public function testGetSubnetArrayReport()
    {
        $report = $this->sub->getSubnetArrayReport();
        $this->assertTrue(is_array($report));
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
    }

    public function testGetSubnetJSONReport()
    {
        $json = $this->sub->getSubnetJSONReport();
        $this->assertTrue(is_string($json));
    }

    public function testPrintSubnetReport()
    {
        $this->expectOutputRegex('
            /
                ^
                \d+[.]\d+[.]\d+[.]\d+\/\d+ \s+ Quads \s+ Hex \s+ Binary \n
                .+?                                                     \n
                IP [ ] Address:      .+                                 \n
                Subnet [ ] Mask:     .+                                 \n
                Network [ ] Portion: .+                                 \n
                Host [ ] Portion:    .+                                 \n
                                                                        \n
                Number [ ] of [ ] IP [ ] Addresses:      \s+ \d+        \n
                Number [ ] of [ ] Addressable [ ] Hosts: \s+ \d+        \n
                IP [ ] Address [ ] Range:                \s+ .+?        \n
                Broadcast [ ] Address:                   \s+ .+?        \n
                $
            /xms
        ');
        $this->sub->printSubnetReport();
    }

    public function testGetPrintableReport()
    {
        $report = $this->sub->getPrintableReport();
        $this->assertTrue(is_string($report));
    }
}
