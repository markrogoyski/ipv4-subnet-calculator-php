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

    /**
     * @testCase     getIPAddress
     * @dataProvider dataProviderForIpAddresses
     * @param        string $ip_address
     * @param        int    $network_size
     */
    public function testGetIpAddress($ip_address, $network_size)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertSame($ip_address, $sub->getIPAddress());
    }

    /**
     * @testCase     getNetworkSize
     * @dataProvider dataProviderForIpAddresses
     * @param        string $ip_address
     * @param        int    $network_size
     */
    public function testGetNetworkSize($ip_address, $network_size)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertSame($network_size, $sub->getNetworkSize());
    }

    /**
     * @return array [ip_address, network_size]
     */
    public function dataProviderForIpAddresses()
    {
        return [
            ['192.168.112.203', 1],
            ['192.168.84.233', 2],
            ['10.10.122.113', 3],
            ['255.255.255.255', 4],
            ['192.168.112.207', 5],
            ['192.128.0.1', 6],
            ['128.0.0.0', 7],
            ['192.168.112.203', 8],
            ['192.168.112.203', 9],
            ['192.168.112.203', 10],
            ['192.168.112.203', 11],
            ['192.168.112.203', 12],
            ['192.168.112.203', 13],
            ['192.168.112.203', 14],
            ['192.168.112.203', 15],
            ['192.168.112.203', 16],
            ['192.168.112.203', 17],
            ['192.168.112.203', 18],
            ['192.168.112.203', 19],
            ['192.168.112.203', 20],
            ['192.168.112.203', 21],
            ['192.168.112.203', 22],
            ['192.168.112.203', 23],
            ['192.168.112.203', 24],
            ['192.168.112.203', 25],
            ['192.168.112.203', 26],
            ['192.168.112.203', 27],
            ['192.168.112.203', 28],
            ['192.168.112.203', 29],
            ['192.168.112.203', 30],
            ['192.168.112.203', 31],
            ['192.168.112.203', 32],
        ];
    }

    /**
     * @testCase     getNumberIpAddresses returns the number of IP addresses
     * @dataProvider dataProviderForNumberOfAddresses
     * @param        string $ip_address
     * @param        int    $network_size
     */
    public function testGetNumberIPAddresses($ip_address, $network_size, $number_addresses)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertEquals($number_addresses, $sub->getNumberIpAddresses());
    }

    /**
     * @return array [ip_address, network_size, number_addresses]
     */
    public function dataProviderForNumberOfAddresses()
    {
        return [
            ['192.168.112.203', 1,  2147483648],
            ['192.168.112.203', 2, 1073741824],
            ['192.168.112.203', 3, 536870912],
            ['192.168.112.203', 4, 268435456],
            ['192.168.112.203', 5, 134217728],
            ['192.168.112.203', 6, 67108864],
            ['192.168.112.203', 7, 33554432],
            ['192.168.112.203', 8, 16777216],
            ['192.168.112.203', 9, 8388608],
            ['192.168.112.203', 10, 4194304],
            ['192.168.112.203', 11, 2097152],
            ['192.168.112.203', 12, 1048576],
            ['192.168.112.203', 13, 524288],
            ['192.168.112.203', 14, 262144],
            ['192.168.112.203', 15, 131072],
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
     * @testCase     getNumberAddressableHosts returns the number of IP addresses
     * @dataProvider dataProviderForNumberOfAddressableHosts
     * @param        string $ip_address
     * @param        int    $network_size
     */
    public function testGetNumberAddressableHosts($ip_address, $network_size, $number_addressable_hosts)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertEquals($number_addressable_hosts, $sub->getNumberAddressableHosts());
    }

    /**
     * @return array [ip_address, network_size, number_addressable_hosts]
     */
    public function dataProviderForNumberOfAddressableHosts()
    {
        return [
            ['192.168.112.203', 1, 2147483646],
            ['192.168.112.203', 2, 1073741822],
            ['192.168.112.203', 3, 536870910],
            ['192.168.112.203', 4, 268435454],
            ['192.168.112.203', 5, 134217726],
            ['192.168.112.203', 6, 67108862],
            ['192.168.112.203', 7, 33554430],
            ['192.168.112.203', 8, 16777214],
            ['192.168.112.203', 9, 8388606],
            ['192.168.112.203', 10, 4194302],
            ['192.168.112.203', 11, 2097150],
            ['192.168.112.203', 12, 1048574],
            ['192.168.112.203', 13, 524286],
            ['192.168.112.203', 14, 262142],
            ['192.168.112.203', 15, 131070],
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
            ['192.168.112.203', 32, 1],
        ];
    }

    /**
     * @testCase     getIpAddressRange returns the lower and upper IP addresses in the range
     * @dataProvider dataProviderForIpAddressRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $lower_ip
     * @param        string $upper_ip
     */
    public function testGetIpAddressRange($ip_address, $network_size, $lower_ip, $upper_ip)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertEquals($lower_ip, $sub->getIpAddressRange()[0]);
        $this->assertEquals($upper_ip, $sub->getIPAddressRange()[1]);
    }

    /**
     * @return array [ip_address, network_size, lower_ip, upper_ip]
     */
    public function dataProviderForIpAddressRange()
    {
        return [
            ['192.168.112.203', 1, '128.0.0.0', '255.255.255.255'],
            ['192.168.112.203', 2, '192.0.0.0', '255.255.255.255'],
            ['192.168.112.203', 3, '192.0.0.0', '223.255.255.255'],
            ['192.168.112.203', 4, '192.0.0.0', '207.255.255.255'],
            ['192.168.112.203', 5, '192.0.0.0', '199.255.255.255'],
            ['192.168.112.203', 6, '192.0.0.0', '195.255.255.255'],
            ['192.168.112.203', 7, '192.0.0.0', '193.255.255.255'],
            ['192.168.112.203', 8, '192.0.0.0', '192.255.255.255'],
            ['192.168.112.203', 9, '192.128.0.0', '192.255.255.255'],
            ['192.168.112.203', 10, '192.128.0.0', '192.191.255.255'],
            ['192.168.112.203', 11, '192.160.0.0', '192.191.255.255'],
            ['192.168.112.203', 12, '192.160.0.0', '192.175.255.255'],
            ['192.168.112.203', 13, '192.168.0.0', '192.175.255.255'],
            ['192.168.112.203', 14, '192.168.0.0', '192.171.255.255'],
            ['192.168.112.203', 15, '192.168.0.0', '192.169.255.255'],
            ['192.168.112.203', 16, '192.168.0.0', '192.168.255.255'],
            ['192.168.112.203', 17, '192.168.0.0', '192.168.127.255'],
            ['192.168.112.203', 18, '192.168.64.0', '192.168.127.255'],
            ['192.168.112.203', 19, '192.168.96.0', '192.168.127.255'],
            ['192.168.112.203', 20, '192.168.112.0', '192.168.127.255'],
            ['192.168.112.203', 21, '192.168.112.0', '192.168.119.255'],
            ['192.168.112.203', 22, '192.168.112.0', '192.168.115.255'],
            ['192.168.112.203', 23, '192.168.112.0', '192.168.113.255'],
            ['192.168.112.203', 24, '192.168.112.0', '192.168.112.255'],
            ['192.168.112.203', 25, '192.168.112.128', '192.168.112.255'],
            ['192.168.112.203', 26, '192.168.112.192', '192.168.112.255'],
            ['192.168.112.203', 27, '192.168.112.192', '192.168.112.223'],
            ['192.168.112.203', 28, '192.168.112.192', '192.168.112.207'],
            ['192.168.112.203', 29, '192.168.112.200', '192.168.112.207'],
            ['192.168.112.203', 30, '192.168.112.200', '192.168.112.203'],
            ['192.168.112.203', 31, '192.168.112.202', '192.168.112.203'],
            ['192.168.112.203', 32, '192.168.112.203', '192.168.112.203'],
        ];
    }

    /**
     * @testCase     getAddressablehostRange returns the lower and upper addressable hosts
     * @dataProvider dataProviderForAddressableHostRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $minHost
     * @param        string $maxHost
     */
    public function testGetAddressableHostRange($ip_address, $network_size, $minHost, $maxHost)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertEquals($minHost, $sub->getAddressablehostRange()[0]);
        $this->assertEquals($maxHost, $sub->getAddressablehostRange()[1]);
    }

    /**
     * @testCase     getMinHost returns the lower addressable host
     * @dataProvider dataProviderForAddressableHostRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $minHost
     * @param        string $_
     */
    public function testGetMinHost($ip_address, $network_size, $minHost, $_)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertEquals($minHost, $sub->getMinHost());
    }

    /**
     * @testCase     getMaxHost returns the upper addressable host
     * @dataProvider dataProviderForAddressableHostRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $_
     * @param        string $maxHost
     */
    public function testGetMaxHost($ip_address, $network_size, $_, $maxHost)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertEquals($maxHost, $sub->getMaxHost());
    }

    /**
     * @return array [ip_address, network_size, minHost, maxHost]
     */
    public function dataProviderForAddressableHostRange()
    {
        return [
            ['192.168.112.203', 1, '128.0.0.1', '255.255.255.254'],
            ['192.168.112.203', 2, '192.0.0.1', '255.255.255.254'],
            ['192.168.112.203', 3, '192.0.0.1', '223.255.255.254'],
            ['192.168.112.203', 4, '192.0.0.1', '207.255.255.254'],
            ['192.168.112.203', 5, '192.0.0.1', '199.255.255.254'],
            ['192.168.112.203', 6, '192.0.0.1', '195.255.255.254'],
            ['192.168.112.203', 7, '192.0.0.1', '193.255.255.254'],
            ['192.168.112.203', 8, '192.0.0.1', '192.255.255.254'],
            ['192.168.112.203', 9, '192.128.0.1', '192.255.255.254'],
            ['192.168.112.203', 10, '192.128.0.1', '192.191.255.254'],
            ['192.168.112.203', 11, '192.160.0.1', '192.191.255.254'],
            ['192.168.112.203', 12, '192.160.0.1', '192.175.255.254'],
            ['192.168.112.203', 13, '192.168.0.1', '192.175.255.254'],
            ['192.168.112.203', 14, '192.168.0.1', '192.171.255.254'],
            ['192.168.112.203', 15, '192.168.0.1', '192.169.255.254'],
            ['192.168.112.203', 16, '192.168.0.1', '192.168.255.254'],
            ['192.168.112.203', 17, '192.168.0.1', '192.168.127.254'],
            ['192.168.112.203', 18, '192.168.64.1', '192.168.127.254'],
            ['192.168.112.203', 19, '192.168.96.1', '192.168.127.254'],
            ['192.168.112.203', 20, '192.168.112.1', '192.168.127.254'],
            ['192.168.112.203', 21, '192.168.112.1', '192.168.119.254'],
            ['192.168.112.203', 22, '192.168.112.1', '192.168.115.254'],
            ['192.168.112.203', 23, '192.168.112.1', '192.168.113.254'],
            ['192.168.112.203', 24, '192.168.112.1', '192.168.112.254'],
            ['192.168.112.203', 25, '192.168.112.129', '192.168.112.254'],
            ['192.168.112.203', 26, '192.168.112.193', '192.168.112.254'],
            ['192.168.112.203', 27, '192.168.112.193', '192.168.112.222'],
            ['192.168.112.203', 28, '192.168.112.193', '192.168.112.206'],
            ['192.168.112.203', 29, '192.168.112.201', '192.168.112.206'],
            ['192.168.112.203', 30, '192.168.112.201', '192.168.112.202'],
            ['192.168.112.203', 31, '192.168.112.203', '192.168.112.203'],
            ['192.168.112.203', 32, '192.168.112.203', '192.168.112.203'],
        ];
    }

    /**
     * @testCase     getMinHostQuads returns an array of quads
     * @dataProvider dataProviderForGetMinHostQuads
     * @param string $ip_address
     * @param int    $network_size
     * @param array  $quads
     */
    public function testGetMinHostQuads($ip_address, $network_size, array $quads)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertEquals($quads, $sub->getMinHostQuads());
    }

    /**
     * @return array
     */
    public function dataProviderForGetMinHostQuads()
    {
        return [
            ['192.168.112.203', 1, [128, 0, 0, 1]],
            ['192.168.112.203', 2, [192, 0, 0, 1]],
            ['192.168.112.203', 24, [192, 168, 112, 1]],
            ['192.168.112.203', 25, [192, 168, 112, 129]],
            ['192.168.112.203', 26, [192, 168, 112, 193]],
            ['192.168.112.203', 27, [192, 168, 112, 193]],
            ['192.168.112.203', 28, [192, 168, 112, 193]],
            ['192.168.112.203', 29, [192, 168, 112, 201]],
            ['192.168.112.203', 30, [192, 168, 112, 201]],
            ['192.168.112.203', 31, [192, 168, 112, 203]],
            ['192.168.112.203', 32, [192, 168, 112, 203]],
        ];
    }

    /**
     * @testCase     getMaxHostQuads returns an array of quads
     * @dataProvider dataProviderForGetMaxHostQuads
     * @param string $ip_address
     * @param int    $network_size
     * @param array  $quads
     */
    public function testGetMaxHostQuads($ip_address, $network_size, array $quads)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertEquals($quads, $sub->getMaxHostQuads());
    }

    /**
     * @return array
     */
    public function dataProviderForGetMaxHostQuads()
    {
        return [
            ['192.168.112.203', 1, [255, 255, 255, 254]],
            ['192.168.112.203', 2, [255, 255, 255, 254]],
            ['192.168.112.203', 24, [192, 168, 112, 254]],
            ['192.168.112.203', 25, [192, 168, 112, 254]],
            ['192.168.112.203', 26, [192, 168, 112, 254]],
            ['192.168.112.203', 27, [192, 168, 112, 222]],
            ['192.168.112.203', 28, [192, 168, 112, 206]],
            ['192.168.112.203', 29, [192, 168, 112, 206]],
            ['192.168.112.203', 30, [192, 168, 112, 202]],
            ['192.168.112.203', 31, [192, 168, 112, 203]],
            ['192.168.112.203', 32, [192, 168, 112, 203]],
        ];
    }

    /**
     * @testCase     getMinHostHex returns a string of hex
     * @dataProvider dataProviderForGetMinHostHex
     * @param string $ip_address
     * @param int    $network_size
     * @param string $hex
     */
    public function testGetMinHostHex($ip_address, $network_size, $hex)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertEquals($hex, $sub->getMinHostHex());
    }

    /**
     * @return array
     */
    public function dataProviderForGetMinHostHex()
    {
        return [
            ['192.168.112.203', 1, '80000001'],
            ['192.168.112.203', 2, 'C0000001'],
            ['192.168.112.203', 24, 'C0A87001'],
            ['192.168.112.203', 25, 'C0A87081'],
            ['192.168.112.203', 26, 'C0A870C1'],
            ['192.168.112.203', 27, 'C0A870C1'],
            ['192.168.112.203', 28, 'C0A870C1'],
            ['192.168.112.203', 29, 'C0A870C9'],
            ['192.168.112.203', 30, 'C0A870C9'],
            ['192.168.112.203', 31, 'C0A870CB'],
            ['192.168.112.203', 32, 'C0A870CB'],
        ];
    }

    /**
     * @testCase     getMaxHostHex returns a string of hex
     * @dataProvider dataProviderForGetMaxHostHex
     * @param string $ip_address
     * @param int    $network_size
     * @param string $hex
     */
    public function testGetMaxHostHex($ip_address, $network_size, $hex)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertEquals($hex, $sub->getMaxHostHex());
    }

    /**
     * @return array
     */
    public function dataProviderForGetMaxHostHex()
    {
        return [
            ['192.168.112.203', 1, 'FFFFFFFE'],
            ['192.168.112.203', 2, 'FFFFFFFE'],
            ['192.168.112.203', 24, 'C0A870FE'],
            ['192.168.112.203', 25, 'C0A870FE'],
            ['192.168.112.203', 26, 'C0A870FE'],
            ['192.168.112.203', 27, 'C0A870DE'],
            ['192.168.112.203', 28, 'C0A870CE'],
            ['192.168.112.203', 29, 'C0A870CE'],
            ['192.168.112.203', 30, 'C0A870CA'],
            ['192.168.112.203', 31, 'C0A870CB'],
            ['192.168.112.203', 32, 'C0A870CB'],
        ];
    }

    /**
     * @testCase     getMinHostBinary returns a string of binary
     * @dataProvider dataProviderForGetMinHostBinary
     * @param string $ip_address
     * @param int    $network_size
     * @param string $binary
     */
    public function testGetMinHostBinary($ip_address, $network_size, $binary)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertEquals($binary, $sub->getMinHostBinary());
    }

    /**
     * @return array
     */
    public function dataProviderForGetMinHostBinary()
    {
        return [
            ['192.168.112.203', 1, '10000000000000000000000000000001'],
            ['192.168.112.203', 2, '11000000000000000000000000000001'],
            ['192.168.112.203', 24, '11000000101010000111000000000001'],
            ['192.168.112.203', 25, '11000000101010000111000010000001'],
            ['192.168.112.203', 26, '11000000101010000111000011000001'],
            ['192.168.112.203', 27, '11000000101010000111000011000001'],
            ['192.168.112.203', 28, '11000000101010000111000011000001'],
            ['192.168.112.203', 29, '11000000101010000111000011001001'],
            ['192.168.112.203', 30, '11000000101010000111000011001001'],
            ['192.168.112.203', 31, '11000000101010000111000011001011'],
            ['192.168.112.203', 32, '11000000101010000111000011001011'],
        ];
    }

    /**
     * @testCase     getMaxHostBinary returns a string of binary
     * @dataProvider dataProviderForGetMaxHostBinary
     * @param string $ip_address
     * @param int    $network_size
     * @param string $binary
     */
    public function testGetMaxHostBinary($ip_address, $network_size, $binary)
    {
        $sub = new SubnetCalculator($ip_address, $network_size);
        $this->assertEquals($binary, $sub->getMaxHostBinary());
    }

    /**
     * @return array
     */
    public function dataProviderForGetMaxHostBinary()
    {
        return [
            ['192.168.112.203', 1, '11111111111111111111111111111110'],
            ['192.168.112.203', 2, '11111111111111111111111111111110'],
            ['192.168.112.203', 24, '11000000101010000111000011111110'],
            ['192.168.112.203', 25, '11000000101010000111000011111110'],
            ['192.168.112.203', 26, '11000000101010000111000011111110'],
            ['192.168.112.203', 27, '11000000101010000111000011011110'],
            ['192.168.112.203', 28, '11000000101010000111000011001110'],
            ['192.168.112.203', 29, '11000000101010000111000011001110'],
            ['192.168.112.203', 30, '11000000101010000111000011001010'],
            ['192.168.112.203', 31, '11000000101010000111000011001011'],
            ['192.168.112.203', 32, '11000000101010000111000011001011'],
        ];
    }

    public function testGetIPAddressRange2()
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
