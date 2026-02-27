<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for Wildcard Mask methods.
 *
 * Wildcard masks are the inverse of subnet masks, used in Cisco ACLs and OSPF
 * configurations. For a /24 (255.255.255.0), the wildcard mask is 0.0.0.255.
 *
 * @link https://www.cisco.com/c/en/us/support/docs/security/ios-firewall/23602-confaccesslists.html Cisco IOS ACL Configuration Guide
 */
class SubnetWildcardMaskTest extends \PHPUnit\Framework\TestCase
{
    /* ***************************** *
     * getWildcardMask() TESTS
     * ***************************** */

    /**
     * getWildcardMask returns wildcard mask as dotted quads
     * @param        string $cidr         Subnet in CIDR notation
     * @param        string $expectedMask Expected wildcard mask as dotted quads
     */
    #[Test]
    #[DataProvider('dataProviderForWildcardMask')]
    public function testGetWildcardMask(string $cidr, string $expectedMask): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $result = $subnet->wildcardMask()->asQuads();

        // Then
        $this->assertSame($expectedMask, $result, "Expected getWildcardMask() for {$cidr} to be '{$expectedMask}'");
    }

    /**
     * @return array[] [cidr, wildcardMask]
     */
    public static function dataProviderForWildcardMask(): array
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
     * getWildcardMaskQuads returns wildcard mask as array of quads
     * @param        string   $cidr          Subnet in CIDR notation
     * @param        string[] $expectedQuads Expected wildcard mask as array of quads
     */
    #[Test]
    #[DataProvider('dataProviderForWildcardMaskQuads')]
    public function testGetWildcardMaskQuads(string $cidr, array $expectedQuads): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $result = $subnet->wildcardMask()->asArray();

        // Then
        $this->assertSame($expectedQuads, $result, "Expected getWildcardMaskQuads() for {$cidr}");
    }

    /**
     * @return array[] [cidr, wildcardMaskQuads]
     */
    public static function dataProviderForWildcardMaskQuads(): array
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
     * getWildcardMaskHex returns wildcard mask as hexadecimal
     * @param        string $cidr        Subnet in CIDR notation
     * @param        string $expectedHex Expected wildcard mask as hex
     */
    #[Test]
    #[DataProvider('dataProviderForWildcardMaskHex')]
    public function testGetWildcardMaskHex(string $cidr, string $expectedHex): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $result = $subnet->wildcardMask()->asHex();

        // Then
        $this->assertSame($expectedHex, $result, "Expected getWildcardMaskHex() for {$cidr} to be '{$expectedHex}'");
    }

    /**
     * @return array[] [cidr, wildcardMaskHex]
     */
    public static function dataProviderForWildcardMaskHex(): array
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
     * getWildcardMaskBinary returns wildcard mask as binary
     * @param        string $cidr           Subnet in CIDR notation
     * @param        string $expectedBinary Expected wildcard mask as binary
     */
    #[Test]
    #[DataProvider('dataProviderForWildcardMaskBinary')]
    public function testGetWildcardMaskBinary(string $cidr, string $expectedBinary): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $result = $subnet->wildcardMask()->asBinary();

        // Then
        $this->assertSame($expectedBinary, $result, "Expected getWildcardMaskBinary() for {$cidr} to be '{$expectedBinary}'");
    }

    /**
     * @return array[] [cidr, wildcardMaskBinary]
     */
    public static function dataProviderForWildcardMaskBinary(): array
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
     * getWildcardMaskInteger returns wildcard mask as integer
     * @param        string $cidr            Subnet in CIDR notation
     * @param        int    $expectedInteger Expected wildcard mask as integer
     */
    #[Test]
    #[DataProvider('dataProviderForWildcardMaskInteger')]
    public function testGetWildcardMaskInteger(string $cidr, int $expectedInteger): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $result = $subnet->wildcardMask()->asInteger();

        // Then
        $this->assertSame($expectedInteger, $result, "Expected getWildcardMaskInteger() for {$cidr} to be {$expectedInteger}");
    }

    /**
     * @return array[] [cidr, wildcardMaskInteger]
     */
    public static function dataProviderForWildcardMaskInteger(): array
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
     * Wildcard mask is the bitwise inverse of subnet mask
     * @param        string $cidr Subnet in CIDR notation
     */
    #[Test]
    #[DataProvider('dataProviderForWildcardMaskInverse')]
    public function testWildcardMaskIsInverseOfSubnetMask(string $cidr): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $subnetMaskInt = $subnet->mask()->asInteger();
        $wildcardMaskInt = $subnet->wildcardMask()->asInteger();

        // Then - Subnet mask OR wildcard mask should equal all 1s (0xFFFFFFFF)
        $combined = sprintf('%u', $subnetMaskInt | $wildcardMaskInt);
        $this->assertSame('4294967295', $combined, "For {$cidr}, subnet mask OR wildcard mask should be 0xFFFFFFFF");

        // And - Subnet mask AND wildcard mask should equal 0
        $this->assertSame(0, $subnetMaskInt & $wildcardMaskInt, "For {$cidr}, subnet mask AND wildcard mask should be 0");
    }

    /**
     * @return array[] [cidr]
     */
    public static function dataProviderForWildcardMaskInverse(): array
    {
        return [
            ['10.0.0.0/8'],
            ['172.16.0.0/16'],
            ['192.168.1.0/24'],
            ['192.168.1.0/30'],
            ['10.0.0.0/31'],
            ['192.168.1.1/32'],
        ];
    }

    /**
     * All wildcard mask formats are consistent with each other
     */
    #[Test]
    public function testAllFormatsAreConsistent(): void
    {
        $subnet = Subnet::fromCidr('192.168.1.0/24');

        // Expected values for /24
        $expectedDotted = '0.0.0.255';
        $expectedQuads = ['0', '0', '0', '255'];
        $expectedHex = '000000FF';
        $expectedBinary = '00000000000000000000000011111111';
        $expectedInteger = 255;

        $this->assertSame($expectedDotted, $subnet->wildcardMask()->asQuads());
        $this->assertSame($expectedQuads, $subnet->wildcardMask()->asArray());
        $this->assertSame($expectedHex, $subnet->wildcardMask()->asHex());
        $this->assertSame($expectedBinary, $subnet->wildcardMask()->asBinary());
        $this->assertSame($expectedInteger, $subnet->wildcardMask()->asInteger());
    }

    /**
     * Wildcard mask quads can be joined to form dotted notation
     * @param        string $cidr Subnet in CIDR notation
     */
    #[Test]
    #[DataProvider('dataProviderForQuadsJoinToDottedNotation')]
    public function testQuadsJoinToFormDottedNotation(string $cidr): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $quads = $subnet->wildcardMask()->asArray();
        $dottedFromQuads = implode('.', $quads);

        // Then
        $this->assertSame($subnet->wildcardMask()->asQuads(), $dottedFromQuads, "Quads for {$cidr} should join to form dotted notation");
    }

    /**
     * @return array[] [cidr]
     */
    public static function dataProviderForQuadsJoinToDottedNotation(): array
    {
        return [
            ['10.0.0.0/8'],
            ['172.16.0.0/16'],
            ['192.168.1.0/24'],
            ['192.168.1.0/30'],
        ];
    }

    /* *********************** *
     * EDGE CASES
     * *********************** */

    /**
     * /31 and /32 edge cases work correctly
     */
    #[Test]
    public function testSlash31And32EdgeCases(): void
    {
        // /31: Point-to-point link (RFC 3021)
        $subnet31 = Subnet::fromCidr('10.0.0.0/31');
        $this->assertSame('0.0.0.1', $subnet31->wildcardMask()->asQuads());
        $this->assertSame(1, $subnet31->wildcardMask()->asInteger());

        // /32: Single host
        $subnet32 = Subnet::fromCidr('192.168.1.1/32');
        $this->assertSame('0.0.0.0', $subnet32->wildcardMask()->asQuads());
        $this->assertSame(0, $subnet32->wildcardMask()->asInteger());
    }

    /**
     * Wildcard mask works regardless of IP address used
     */
    #[Test]
    public function testWildcardMaskIsIndependentOfIPAddress(): void
    {
        // Same network size, different IPs - should have same wildcard mask
        $subnet1 = Subnet::fromCidr('192.168.1.0/24');
        $subnet2 = Subnet::fromCidr('10.20.30.40/24');

        $this->assertSame($subnet1->wildcardMask()->asQuads(), $subnet2->wildcardMask()->asQuads());
        $this->assertSame($subnet1->wildcardMask()->asInteger(), $subnet2->wildcardMask()->asInteger());
    }
}
