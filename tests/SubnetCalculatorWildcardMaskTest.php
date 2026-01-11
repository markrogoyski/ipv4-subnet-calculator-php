<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\SubnetCalculator;
use IPv4\SubnetCalculatorFactory;

/**
 * Tests for Wildcard Mask methods.
 *
 * Wildcard masks are the inverse of subnet masks, used in Cisco ACLs and OSPF
 * configurations. For a /24 (255.255.255.0), the wildcard mask is 0.0.0.255.
 *
 * @link https://www.cisco.com/c/en/us/support/docs/security/ios-firewall/23602-confaccesslists.html Cisco IOS ACL Configuration Guide
 */
class SubnetCalculatorWildcardMaskTest extends \PHPUnit\Framework\TestCase
{
    /* ***************************** *
     * getWildcardMask() TESTS
     * ***************************** */

    /**
     * @test         getWildcardMask returns wildcard mask as dotted quads
     * @dataProvider dataProviderForWildcardMask
     * @param        string $cidr         Subnet in CIDR notation
     * @param        string $expectedMask Expected wildcard mask as dotted quads
     */
    public function testGetWildcardMask(string $cidr, string $expectedMask): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->getWildcardMask();

        // Then
        $this->assertSame($expectedMask, $result, "Expected getWildcardMask() for {$cidr} to be '{$expectedMask}'");
    }

    /**
     * @return array[] [cidr, wildcardMask]
     */
    public function dataProviderForWildcardMask(): array
    {
        return [
            // Standard network sizes
            ['10.0.0.0/8', '0.255.255.255'],
            ['172.16.0.0/16', '0.0.255.255'],
            ['192.168.1.0/24', '0.0.0.255'],
            ['192.168.1.0/25', '0.0.0.127'],
            ['192.168.1.0/26', '0.0.0.63'],
            ['192.168.1.0/27', '0.0.0.31'],
            ['192.168.1.0/28', '0.0.0.15'],
            ['192.168.1.0/29', '0.0.0.7'],
            ['192.168.1.0/30', '0.0.0.3'],

            // RFC 3021 point-to-point and single host
            ['10.0.0.0/31', '0.0.0.1'],
            ['192.168.1.1/32', '0.0.0.0'],

            // Larger networks
            ['10.0.0.0/12', '0.15.255.255'],
            ['172.16.0.0/12', '0.15.255.255'],
            ['10.0.0.0/4', '15.255.255.255'],

            // Edge case: /1 (largest network)
            ['128.0.0.0/1', '127.255.255.255'],
        ];
    }

    /* ******************************** *
     * getWildcardMaskQuads() TESTS
     * ******************************** */

    /**
     * @test         getWildcardMaskQuads returns wildcard mask as array of quads
     * @dataProvider dataProviderForWildcardMaskQuads
     * @param        string   $cidr          Subnet in CIDR notation
     * @param        string[] $expectedQuads Expected wildcard mask as array of quads
     */
    public function testGetWildcardMaskQuads(string $cidr, array $expectedQuads): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->getWildcardMaskQuads();

        // Then
        $this->assertSame($expectedQuads, $result, "Expected getWildcardMaskQuads() for {$cidr}");
    }

    /**
     * @return array[] [cidr, wildcardMaskQuads]
     */
    public function dataProviderForWildcardMaskQuads(): array
    {
        return [
            ['10.0.0.0/8', ['0', '255', '255', '255']],
            ['172.16.0.0/16', ['0', '0', '255', '255']],
            ['192.168.1.0/24', ['0', '0', '0', '255']],
            ['192.168.1.0/30', ['0', '0', '0', '3']],
            ['10.0.0.0/31', ['0', '0', '0', '1']],
            ['192.168.1.1/32', ['0', '0', '0', '0']],
            ['10.0.0.0/12', ['0', '15', '255', '255']],
        ];
    }

    /* ****************************** *
     * getWildcardMaskHex() TESTS
     * ****************************** */

    /**
     * @test         getWildcardMaskHex returns wildcard mask as hexadecimal
     * @dataProvider dataProviderForWildcardMaskHex
     * @param        string $cidr        Subnet in CIDR notation
     * @param        string $expectedHex Expected wildcard mask as hex
     */
    public function testGetWildcardMaskHex(string $cidr, string $expectedHex): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->getWildcardMaskHex();

        // Then
        $this->assertSame($expectedHex, $result, "Expected getWildcardMaskHex() for {$cidr} to be '{$expectedHex}'");
    }

    /**
     * @return array[] [cidr, wildcardMaskHex]
     */
    public function dataProviderForWildcardMaskHex(): array
    {
        return [
            ['10.0.0.0/8', '00FFFFFF'],
            ['172.16.0.0/16', '0000FFFF'],
            ['192.168.1.0/24', '000000FF'],
            ['192.168.1.0/25', '0000007F'],
            ['192.168.1.0/30', '00000003'],
            ['10.0.0.0/31', '00000001'],
            ['192.168.1.1/32', '00000000'],
            ['128.0.0.0/1', '7FFFFFFF'],
        ];
    }

    /* ********************************* *
     * getWildcardMaskBinary() TESTS
     * ********************************* */

    /**
     * @test         getWildcardMaskBinary returns wildcard mask as binary
     * @dataProvider dataProviderForWildcardMaskBinary
     * @param        string $cidr           Subnet in CIDR notation
     * @param        string $expectedBinary Expected wildcard mask as binary
     */
    public function testGetWildcardMaskBinary(string $cidr, string $expectedBinary): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->getWildcardMaskBinary();

        // Then
        $this->assertSame($expectedBinary, $result, "Expected getWildcardMaskBinary() for {$cidr} to be '{$expectedBinary}'");
    }

    /**
     * @return array[] [cidr, wildcardMaskBinary]
     */
    public function dataProviderForWildcardMaskBinary(): array
    {
        return [
            ['10.0.0.0/8', '00000000111111111111111111111111'],
            ['172.16.0.0/16', '00000000000000001111111111111111'],
            ['192.168.1.0/24', '00000000000000000000000011111111'],
            ['192.168.1.0/25', '00000000000000000000000001111111'],
            ['192.168.1.0/30', '00000000000000000000000000000011'],
            ['10.0.0.0/31', '00000000000000000000000000000001'],
            ['192.168.1.1/32', '00000000000000000000000000000000'],
            ['128.0.0.0/1', '01111111111111111111111111111111'],
        ];
    }

    /* ********************************** *
     * getWildcardMaskInteger() TESTS
     * ********************************** */

    /**
     * @test         getWildcardMaskInteger returns wildcard mask as integer
     * @dataProvider dataProviderForWildcardMaskInteger
     * @param        string $cidr            Subnet in CIDR notation
     * @param        int    $expectedInteger Expected wildcard mask as integer
     */
    public function testGetWildcardMaskInteger(string $cidr, int $expectedInteger): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->getWildcardMaskInteger();

        // Then
        $this->assertSame($expectedInteger, $result, "Expected getWildcardMaskInteger() for {$cidr} to be {$expectedInteger}");
    }

    /**
     * @return array[] [cidr, wildcardMaskInteger]
     */
    public function dataProviderForWildcardMaskInteger(): array
    {
        return [
            ['10.0.0.0/8', 0x00FFFFFF],         // 16777215
            ['172.16.0.0/16', 0x0000FFFF],      // 65535
            ['192.168.1.0/24', 0x000000FF],     // 255
            ['192.168.1.0/25', 0x0000007F],     // 127
            ['192.168.1.0/30', 0x00000003],     // 3
            ['10.0.0.0/31', 0x00000001],        // 1
            ['192.168.1.1/32', 0x00000000],     // 0
            ['128.0.0.0/1', 0x7FFFFFFF],        // 2147483647
        ];
    }

    /* *********************** *
     * CONSISTENCY TESTS
     * *********************** */

    /**
     * @test Wildcard mask is the bitwise inverse of subnet mask
     */
    public function testWildcardMaskIsInverseOfSubnetMask(): void
    {
        $testCases = [
            '10.0.0.0/8',
            '172.16.0.0/16',
            '192.168.1.0/24',
            '192.168.1.0/30',
            '10.0.0.0/31',
            '192.168.1.1/32',
        ];

        foreach ($testCases as $cidr) {
            $subnet = SubnetCalculatorFactory::fromCidr($cidr);

            // Subnet mask OR wildcard mask should equal all 1s (0xFFFFFFFF)
            $subnetMaskInt = $subnet->getSubnetMaskInteger();
            $wildcardMaskInt = $subnet->getWildcardMaskInteger();

            // Use sprintf to handle unsigned integer comparison
            $combined = sprintf('%u', $subnetMaskInt | $wildcardMaskInt);

            $this->assertSame('4294967295', $combined, "For {$cidr}, subnet mask OR wildcard mask should be 0xFFFFFFFF");

            // Subnet mask AND wildcard mask should equal 0
            $this->assertSame(0, $subnetMaskInt & $wildcardMaskInt, "For {$cidr}, subnet mask AND wildcard mask should be 0");
        }
    }

    /**
     * @test All wildcard mask formats are consistent with each other
     */
    public function testAllFormatsAreConsistent(): void
    {
        $subnet = SubnetCalculatorFactory::fromCidr('192.168.1.0/24');

        // Expected values for /24
        $expectedDotted = '0.0.0.255';
        $expectedQuads = ['0', '0', '0', '255'];
        $expectedHex = '000000FF';
        $expectedBinary = '00000000000000000000000011111111';
        $expectedInteger = 255;

        $this->assertSame($expectedDotted, $subnet->getWildcardMask());
        $this->assertSame($expectedQuads, $subnet->getWildcardMaskQuads());
        $this->assertSame($expectedHex, $subnet->getWildcardMaskHex());
        $this->assertSame($expectedBinary, $subnet->getWildcardMaskBinary());
        $this->assertSame($expectedInteger, $subnet->getWildcardMaskInteger());
    }

    /**
     * @test Wildcard mask quads can be joined to form dotted notation
     */
    public function testQuadsJoinToFormDottedNotation(): void
    {
        $testCases = [
            '10.0.0.0/8',
            '172.16.0.0/16',
            '192.168.1.0/24',
            '192.168.1.0/30',
        ];

        foreach ($testCases as $cidr) {
            $subnet = SubnetCalculatorFactory::fromCidr($cidr);
            $quads = $subnet->getWildcardMaskQuads();
            $dottedFromQuads = implode('.', $quads);

            $this->assertSame($subnet->getWildcardMask(), $dottedFromQuads, "Quads for {$cidr} should join to form dotted notation");
        }
    }

    /* *********************** *
     * EDGE CASES
     * *********************** */

    /**
     * @test /31 and /32 edge cases work correctly
     */
    public function testSlash31And32EdgeCases(): void
    {
        // /31: Point-to-point link (RFC 3021)
        $subnet31 = SubnetCalculatorFactory::fromCidr('10.0.0.0/31');
        $this->assertSame('0.0.0.1', $subnet31->getWildcardMask());
        $this->assertSame(1, $subnet31->getWildcardMaskInteger());

        // /32: Single host
        $subnet32 = SubnetCalculatorFactory::fromCidr('192.168.1.1/32');
        $this->assertSame('0.0.0.0', $subnet32->getWildcardMask());
        $this->assertSame(0, $subnet32->getWildcardMaskInteger());
    }

    /**
     * @test Wildcard mask works regardless of IP address used
     */
    public function testWildcardMaskIsIndependentOfIPAddress(): void
    {
        // Same network size, different IPs - should have same wildcard mask
        $subnet1 = SubnetCalculatorFactory::fromCidr('192.168.1.0/24');
        $subnet2 = SubnetCalculatorFactory::fromCidr('10.20.30.40/24');

        $this->assertSame($subnet1->getWildcardMask(), $subnet2->getWildcardMask());
        $this->assertSame($subnet1->getWildcardMaskInteger(), $subnet2->getWildcardMaskInteger());
    }
}
