<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\SubnetCalculator;
use IPv4\SubnetCalculatorFactory;

/**
 * Tests for Private/Reserved IP Range Detection methods.
 *
 * These methods detect whether an IP/subnet falls within IANA special-purpose
 * address ranges. Useful for security validation, routing decisions, and
 * network classification.
 *
 * @link https://www.iana.org/assignments/iana-ipv4-special-registry/iana-ipv4-special-registry.xhtml IANA IPv4 Special-Purpose Address Registry
 */
class SubnetCalculatorRangeTypeTest extends \PHPUnit\Framework\TestCase
{
    /* ****************** *
     * isPrivate() TESTS
     * ****************** */

    /**
     * @test         isPrivate returns true for RFC 1918 private addresses
     * @dataProvider dataProviderForIsPrivate
     * @param        string $cidr     Subnet in CIDR notation
     * @param        bool   $expected Whether the address is private
     */
    public function testIsPrivate(string $cidr, bool $expected): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->isPrivate();

        // Then
        $this->assertSame($expected, $result, "Expected isPrivate() for {$cidr} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [cidr, isPrivate]
     */
    public function dataProviderForIsPrivate(): array
    {
        return [
            // 10.0.0.0/8 range (RFC 1918 Class A private)
            ['10.0.0.0/8', true],
            ['10.0.0.1/32', true],
            ['10.255.255.255/32', true],
            ['10.128.0.0/16', true],

            // 172.16.0.0/12 range (RFC 1918 Class B private)
            ['172.16.0.0/12', true],
            ['172.16.0.0/16', true],
            ['172.31.255.255/32', true],
            ['172.20.0.0/24', true],

            // Boundaries of 172.16.0.0/12
            ['172.15.255.255/32', false], // Just before private range
            ['172.32.0.0/32', false],     // Just after private range

            // 192.168.0.0/16 range (RFC 1918 Class C private)
            ['192.168.0.0/16', true],
            ['192.168.1.0/24', true],
            ['192.168.255.255/32', true],
            ['192.168.0.1/32', true],

            // Boundaries of 192.168.0.0/16
            ['192.167.255.255/32', false], // Just before private range
            ['192.169.0.0/32', false],     // Just after private range

            // Public addresses
            ['8.8.8.8/32', false],         // Google DNS
            ['1.1.1.1/32', false],         // Cloudflare DNS
            ['208.67.222.222/32', false],  // OpenDNS
        ];
    }

    /* ****************** *
     * isPublic() TESTS
     * ****************** */

    /**
     * @test         isPublic returns true for publicly routable addresses
     * @dataProvider dataProviderForIsPublic
     * @param        string $cidr     Subnet in CIDR notation
     * @param        bool   $expected Whether the address is public
     */
    public function testIsPublic(string $cidr, bool $expected): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->isPublic();

        // Then
        $this->assertSame($expected, $result, "Expected isPublic() for {$cidr} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [cidr, isPublic]
     */
    public function dataProviderForIsPublic(): array
    {
        return [
            // Public addresses
            ['8.8.8.8/32', true],          // Google DNS
            ['1.1.1.1/32', true],          // Cloudflare DNS
            ['208.67.222.222/32', true],   // OpenDNS
            ['93.184.216.34/32', true],    // example.com
            ['151.101.1.140/32', true],    // Reddit

            // Private addresses (not public)
            ['10.0.0.1/32', false],
            ['172.16.0.1/32', false],
            ['192.168.1.1/32', false],

            // Special ranges (not public)
            ['127.0.0.1/32', false],       // Loopback
            ['169.254.1.1/32', false],     // Link-local
            ['224.0.0.1/32', false],       // Multicast
            ['100.64.0.1/32', false],      // CGN
            ['192.0.2.1/32', false],       // Documentation
            ['198.18.0.1/32', false],      // Benchmarking
            ['240.0.0.1/32', false],       // Reserved
            ['255.255.255.255/32', false], // Limited broadcast
            ['0.0.0.1/32', false],         // "This" network
        ];
    }

    /* ******************** *
     * isLoopback() TESTS
     * ******************** */

    /**
     * @test         isLoopback returns true for 127.0.0.0/8 addresses
     * @dataProvider dataProviderForIsLoopback
     * @param        string $cidr     Subnet in CIDR notation
     * @param        bool   $expected Whether the address is loopback
     */
    public function testIsLoopback(string $cidr, bool $expected): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->isLoopback();

        // Then
        $this->assertSame($expected, $result, "Expected isLoopback() for {$cidr} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [cidr, isLoopback]
     */
    public function dataProviderForIsLoopback(): array
    {
        return [
            // Loopback range 127.0.0.0/8
            ['127.0.0.0/8', true],
            ['127.0.0.1/32', true],        // localhost
            ['127.255.255.255/32', true],
            ['127.1.2.3/32', true],

            // Boundaries
            ['126.255.255.255/32', false], // Just before loopback
            ['128.0.0.0/32', false],       // Just after loopback

            // Other addresses
            ['192.168.1.1/32', false],
            ['10.0.0.1/32', false],
        ];
    }

    /* ********************* *
     * isLinkLocal() TESTS
     * ********************* */

    /**
     * @test         isLinkLocal returns true for 169.254.0.0/16 addresses
     * @dataProvider dataProviderForIsLinkLocal
     * @param        string $cidr     Subnet in CIDR notation
     * @param        bool   $expected Whether the address is link-local
     */
    public function testIsLinkLocal(string $cidr, bool $expected): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->isLinkLocal();

        // Then
        $this->assertSame($expected, $result, "Expected isLinkLocal() for {$cidr} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [cidr, isLinkLocal]
     */
    public function dataProviderForIsLinkLocal(): array
    {
        return [
            // Link-local range 169.254.0.0/16
            ['169.254.0.0/16', true],
            ['169.254.0.1/32', true],
            ['169.254.255.255/32', true],
            ['169.254.128.1/32', true],

            // Boundaries
            ['169.253.255.255/32', false], // Just before link-local
            ['169.255.0.0/32', false],     // Just after link-local

            // Other addresses
            ['192.168.1.1/32', false],
            ['10.0.0.1/32', false],
        ];
    }

    /* ********************* *
     * isMulticast() TESTS
     * ********************* */

    /**
     * @test         isMulticast returns true for 224.0.0.0/4 addresses
     * @dataProvider dataProviderForIsMulticast
     * @param        string $cidr     Subnet in CIDR notation
     * @param        bool   $expected Whether the address is multicast
     */
    public function testIsMulticast(string $cidr, bool $expected): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->isMulticast();

        // Then
        $this->assertSame($expected, $result, "Expected isMulticast() for {$cidr} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [cidr, isMulticast]
     */
    public function dataProviderForIsMulticast(): array
    {
        return [
            // Multicast range 224.0.0.0/4 (224.0.0.0 - 239.255.255.255)
            ['224.0.0.0/4', true],
            ['224.0.0.1/32', true],        // All Hosts
            ['224.0.0.2/32', true],        // All Routers
            ['239.255.255.255/32', true],  // End of multicast range
            ['232.0.0.0/8', true],         // Source-specific multicast

            // Boundaries
            ['223.255.255.255/32', false], // Just before multicast
            ['240.0.0.0/32', false],       // Just after multicast (reserved)

            // Other addresses
            ['192.168.1.1/32', false],
            ['10.0.0.1/32', false],
        ];
    }

    /* ************************** *
     * isCarrierGradeNat() TESTS
     * ************************** */

    /**
     * @test         isCarrierGradeNat returns true for 100.64.0.0/10 addresses (RFC 6598)
     * @dataProvider dataProviderForIsCarrierGradeNat
     * @param        string $cidr     Subnet in CIDR notation
     * @param        bool   $expected Whether the address is CGN
     */
    public function testIsCarrierGradeNat(string $cidr, bool $expected): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->isCarrierGradeNat();

        // Then
        $this->assertSame($expected, $result, "Expected isCarrierGradeNat() for {$cidr} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [cidr, isCarrierGradeNat]
     */
    public function dataProviderForIsCarrierGradeNat(): array
    {
        return [
            // CGN range 100.64.0.0/10 (100.64.0.0 - 100.127.255.255)
            ['100.64.0.0/10', true],
            ['100.64.0.1/32', true],
            ['100.127.255.255/32', true],
            ['100.100.0.0/24', true],

            // Boundaries
            ['100.63.255.255/32', false],  // Just before CGN
            ['100.128.0.0/32', false],     // Just after CGN

            // Other addresses
            ['192.168.1.1/32', false],
            ['10.0.0.1/32', false],
        ];
    }

    /* ************************* *
     * isDocumentation() TESTS
     * ************************* */

    /**
     * @test         isDocumentation returns true for RFC 5737 documentation addresses
     * @dataProvider dataProviderForIsDocumentation
     * @param        string $cidr     Subnet in CIDR notation
     * @param        bool   $expected Whether the address is documentation
     */
    public function testIsDocumentation(string $cidr, bool $expected): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->isDocumentation();

        // Then
        $this->assertSame($expected, $result, "Expected isDocumentation() for {$cidr} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [cidr, isDocumentation]
     */
    public function dataProviderForIsDocumentation(): array
    {
        return [
            // TEST-NET-1: 192.0.2.0/24
            ['192.0.2.0/24', true],
            ['192.0.2.1/32', true],
            ['192.0.2.255/32', true],

            // TEST-NET-2: 198.51.100.0/24
            ['198.51.100.0/24', true],
            ['198.51.100.1/32', true],
            ['198.51.100.255/32', true],

            // TEST-NET-3: 203.0.113.0/24
            ['203.0.113.0/24', true],
            ['203.0.113.1/32', true],
            ['203.0.113.255/32', true],

            // Boundaries of TEST-NET-1
            ['192.0.1.255/32', false],     // Just before TEST-NET-1
            ['192.0.3.0/32', false],       // Just after TEST-NET-1

            // Boundaries of TEST-NET-2
            ['198.51.99.255/32', false],   // Just before TEST-NET-2
            ['198.51.101.0/32', false],    // Just after TEST-NET-2

            // Boundaries of TEST-NET-3
            ['203.0.112.255/32', false],   // Just before TEST-NET-3
            ['203.0.114.0/32', false],     // Just after TEST-NET-3

            // Other addresses
            ['192.168.1.1/32', false],
            ['10.0.0.1/32', false],
        ];
    }

    /* ************************ *
     * isBenchmarking() TESTS
     * ************************ */

    /**
     * @test         isBenchmarking returns true for 198.18.0.0/15 addresses (RFC 2544)
     * @dataProvider dataProviderForIsBenchmarking
     * @param        string $cidr     Subnet in CIDR notation
     * @param        bool   $expected Whether the address is benchmarking
     */
    public function testIsBenchmarking(string $cidr, bool $expected): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->isBenchmarking();

        // Then
        $this->assertSame($expected, $result, "Expected isBenchmarking() for {$cidr} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [cidr, isBenchmarking]
     */
    public function dataProviderForIsBenchmarking(): array
    {
        return [
            // Benchmarking range 198.18.0.0/15 (198.18.0.0 - 198.19.255.255)
            ['198.18.0.0/15', true],
            ['198.18.0.1/32', true],
            ['198.19.255.255/32', true],
            ['198.18.128.0/24', true],
            ['198.19.0.0/24', true],

            // Boundaries
            ['198.17.255.255/32', false],  // Just before benchmarking
            ['198.20.0.0/32', false],      // Just after benchmarking

            // Other addresses
            ['192.168.1.1/32', false],
            ['10.0.0.1/32', false],
        ];
    }

    /* ********************* *
     * isReserved() TESTS
     * ********************* */

    /**
     * @test         isReserved returns true for 240.0.0.0/4 addresses (RFC 1112)
     * @dataProvider dataProviderForIsReserved
     * @param        string $cidr     Subnet in CIDR notation
     * @param        bool   $expected Whether the address is reserved
     */
    public function testIsReserved(string $cidr, bool $expected): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->isReserved();

        // Then
        $this->assertSame($expected, $result, "Expected isReserved() for {$cidr} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [cidr, isReserved]
     */
    public function dataProviderForIsReserved(): array
    {
        return [
            // Reserved range 240.0.0.0/4 (240.0.0.0 - 255.255.255.255)
            // Note: 255.255.255.255 is separately classified as limited broadcast
            ['240.0.0.0/4', true],
            ['240.0.0.1/32', true],
            ['255.255.255.254/32', true],
            ['248.0.0.0/8', true],

            // 255.255.255.255 is limited broadcast, but still in the reserved range
            ['255.255.255.255/32', true],

            // Boundaries
            ['239.255.255.255/32', false], // Just before reserved (multicast)

            // Other addresses
            ['192.168.1.1/32', false],
            ['10.0.0.1/32', false],
        ];
    }

    /* **************************** *
     * isLimitedBroadcast() TESTS
     * **************************** */

    /**
     * @test         isLimitedBroadcast returns true only for 255.255.255.255/32
     * @dataProvider dataProviderForIsLimitedBroadcast
     * @param        string $cidr     Subnet in CIDR notation
     * @param        bool   $expected Whether the address is limited broadcast
     */
    public function testIsLimitedBroadcast(string $cidr, bool $expected): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->isLimitedBroadcast();

        // Then
        $this->assertSame($expected, $result, "Expected isLimitedBroadcast() for {$cidr} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [cidr, isLimitedBroadcast]
     */
    public function dataProviderForIsLimitedBroadcast(): array
    {
        return [
            // Limited broadcast
            ['255.255.255.255/32', true],

            // Close but not limited broadcast
            ['255.255.255.254/32', false],
            ['255.255.255.0/24', false],

            // Other addresses
            ['192.168.1.255/32', false],   // Directed broadcast for a /24
            ['10.0.0.255/32', false],
        ];
    }

    /* ************************* *
     * isThisNetwork() TESTS
     * ************************* */

    /**
     * @test         isThisNetwork returns true for 0.0.0.0/8 addresses (RFC 1122)
     * @dataProvider dataProviderForIsThisNetwork
     * @param        string $cidr     Subnet in CIDR notation
     * @param        bool   $expected Whether the address is "this" network
     */
    public function testIsThisNetwork(string $cidr, bool $expected): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->isThisNetwork();

        // Then
        $this->assertSame($expected, $result, "Expected isThisNetwork() for {$cidr} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [cidr, isThisNetwork]
     */
    public function dataProviderForIsThisNetwork(): array
    {
        return [
            // "This" network range 0.0.0.0/8
            ['0.0.0.0/8', true],
            ['0.0.0.1/32', true],
            ['0.255.255.255/32', true],
            ['0.0.0.0/32', true],

            // Boundaries
            ['1.0.0.0/32', false],         // Just after "this" network

            // Other addresses
            ['192.168.1.1/32', false],
            ['10.0.0.1/32', false],
        ];
    }

    /* ************************ *
     * getAddressType() TESTS
     * ************************ */

    /**
     * @test         getAddressType returns correct classification string
     * @dataProvider dataProviderForGetAddressType
     * @param        string $cidr     Subnet in CIDR notation
     * @param        string $expected Expected address type classification
     */
    public function testGetAddressType(string $cidr, string $expected): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->getAddressType();

        // Then
        $this->assertSame($expected, $result, "Expected getAddressType() for {$cidr} to be '{$expected}'");
    }

    /**
     * @return array[] [cidr, addressType]
     */
    public function dataProviderForGetAddressType(): array
    {
        return [
            // Private addresses
            ['10.0.0.1/32', 'private'],
            ['172.16.0.1/32', 'private'],
            ['192.168.1.1/32', 'private'],

            // Loopback
            ['127.0.0.1/32', 'loopback'],
            ['127.255.255.255/32', 'loopback'],

            // Link-local
            ['169.254.1.1/32', 'link-local'],
            ['169.254.255.255/32', 'link-local'],

            // Multicast
            ['224.0.0.1/32', 'multicast'],
            ['239.255.255.255/32', 'multicast'],

            // Carrier-grade NAT
            ['100.64.0.1/32', 'carrier-grade-nat'],
            ['100.127.255.255/32', 'carrier-grade-nat'],

            // Documentation
            ['192.0.2.1/32', 'documentation'],
            ['198.51.100.1/32', 'documentation'],
            ['203.0.113.1/32', 'documentation'],

            // Benchmarking
            ['198.18.0.1/32', 'benchmarking'],
            ['198.19.255.255/32', 'benchmarking'],

            // Limited broadcast (checked before reserved since it's a subset)
            ['255.255.255.255/32', 'limited-broadcast'],

            // Reserved (excluding limited broadcast)
            ['240.0.0.1/32', 'reserved'],
            ['255.255.255.254/32', 'reserved'],

            // "This" network
            ['0.0.0.0/32', 'this-network'],
            ['0.0.0.1/32', 'this-network'],

            // Public addresses
            ['8.8.8.8/32', 'public'],
            ['1.1.1.1/32', 'public'],
            ['93.184.216.34/32', 'public'],
        ];
    }

    /* ************************ *
     * EDGE CASES
     * ************************ */

    /**
     * @test isPrivate and isPublic are mutually exclusive (an address can't be both)
     */
    public function testPrivateAndPublicAreMutuallyExclusive(): void
    {
        // Public address
        $public = SubnetCalculatorFactory::fromCidr('8.8.8.8/32');
        $this->assertTrue($public->isPublic());
        $this->assertFalse($public->isPrivate());

        // Private address
        $private = SubnetCalculatorFactory::fromCidr('192.168.1.1/32');
        $this->assertTrue($private->isPrivate());
        $this->assertFalse($private->isPublic());
    }

    /**
     * @test         Special ranges are not considered public
     * @dataProvider dataProviderForSpecialRangesNotPublic
     * @param        string $cidr        Subnet in CIDR notation
     * @param        string $description Description of the special range type
     */
    public function testSpecialRangesAreNotPublic(string $cidr, string $description): void
    {
        // Given
        $subnet = SubnetCalculatorFactory::fromCidr($cidr);

        // When
        $result = $subnet->isPublic();

        // Then
        $this->assertFalse($result, "Expected {$cidr} ({$description}) to NOT be public");
    }

    /**
     * @return array[] [cidr, description]
     */
    public function dataProviderForSpecialRangesNotPublic(): array
    {
        return [
            ['127.0.0.1/32', 'Loopback'],
            ['169.254.1.1/32', 'Link-local'],
            ['224.0.0.1/32', 'Multicast'],
            ['100.64.0.1/32', 'CGN'],
            ['192.0.2.1/32', 'Documentation'],
            ['198.18.0.1/32', 'Benchmarking'],
            ['240.0.0.1/32', 'Reserved'],
            ['255.255.255.255/32', 'Limited broadcast'],
            ['0.0.0.1/32', 'This network'],
        ];
    }

    /**
     * @test /31 and /32 edge cases work correctly
     */
    public function testSlash31And32EdgeCases(): void
    {
        // /31 in private range
        $subnet31 = SubnetCalculatorFactory::fromCidr('192.168.1.0/31');
        $this->assertTrue($subnet31->isPrivate());
        $this->assertFalse($subnet31->isPublic());

        // /32 at boundary of private range
        $subnet32 = SubnetCalculatorFactory::fromCidr('10.255.255.255/32');
        $this->assertTrue($subnet32->isPrivate());

        // /31 in loopback range
        $loopback31 = SubnetCalculatorFactory::fromCidr('127.0.0.0/31');
        $this->assertTrue($loopback31->isLoopback());
    }
}
