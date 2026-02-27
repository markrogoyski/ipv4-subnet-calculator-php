<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\NetworkClass;
use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for legacy network class information (Feature 9).
 *
 * While classful networking is obsolete (RFC 4632 established CIDR), it's still
 * referenced in education, certifications, and some legacy systems.
 *
 * Network class definitions (RFC 791):
 *   Class A: 0-127     (leading bit 0)     /8  mask  (includes 0.x.x.x and 127.x.x.x)
 *   Class B: 128-191   (leading bits 10)   /16 mask
 *   Class C: 192-223   (leading bits 110)  /24 mask
 *   Class D: 224-239   (leading bits 1110) Multicast, no default mask
 *   Class E: 240-255   (leading bits 1111) Reserved, no default mask
 *
 * @link https://datatracker.ietf.org/doc/html/rfc791 RFC 791 - Internet Protocol (original classful definition)
 * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - Classless Inter-domain Routing (CIDR)
 */
class SubnetNetworkClassTest extends TestCase
{
    /* ************************** *
     * getNetworkClass() TESTS
     * ************************** */

    #[Test]
    #[DataProvider('dataProviderForNetworkClass')]
    public function testGetNetworkClass(string $ipAddress, int $networkSize, NetworkClass $expectedClass): void
    {
        // Given
        $subnet = new Subnet($ipAddress, $networkSize);

        // When
        $result = $subnet->networkClass();

        // Then
        $this->assertSame($expectedClass, $result);
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: NetworkClass}>
     */
    public static function dataProviderForNetworkClass(): array
    {
        return [
            // Class A: 1-126
            'Class A start (1.0.0.0)' => ['1.0.0.0', 8, NetworkClass::A],
            'Class A typical (10.0.0.0)' => ['10.0.0.0', 8, NetworkClass::A],
            'Class A end (126.255.255.255)' => ['126.255.255.255', 32, NetworkClass::A],
            'Class A with /24 subnet' => ['10.0.0.0', 24, NetworkClass::A],
            'Class A with /32 host' => ['50.100.150.200', 32, NetworkClass::A],

            // Loopback (127.x.x.x) - technically in Class A space
            'Loopback (127.0.0.1)' => ['127.0.0.1', 8, NetworkClass::A],
            'Loopback end (127.255.255.255)' => ['127.255.255.255', 32, NetworkClass::A],

            // Class B: 128-191
            'Class B start (128.0.0.0)' => ['128.0.0.0', 16, NetworkClass::B],
            'Class B typical (172.16.0.0)' => ['172.16.0.0', 16, NetworkClass::B],
            'Class B end (191.255.255.255)' => ['191.255.255.255', 32, NetworkClass::B],
            'Class B with /12 supernet' => ['172.16.0.0', 12, NetworkClass::B],
            'Class B with /24 subnet' => ['172.16.1.0', 24, NetworkClass::B],

            // Class C: 192-223
            'Class C start (192.0.0.0)' => ['192.0.0.0', 24, NetworkClass::C],
            'Class C typical (192.168.1.0)' => ['192.168.1.0', 24, NetworkClass::C],
            'Class C end (223.255.255.255)' => ['223.255.255.255', 32, NetworkClass::C],
            'Class C with /25 subnet' => ['192.168.1.0', 25, NetworkClass::C],
            'Class C with /32 host' => ['200.100.50.25', 32, NetworkClass::C],

            // Class D: 224-239 (Multicast)
            'Class D start (224.0.0.0)' => ['224.0.0.0', 32, NetworkClass::D],
            'Class D typical multicast (239.255.255.255)' => ['239.255.255.255', 32, NetworkClass::D],
            'Class D all hosts multicast (224.0.0.1)' => ['224.0.0.1', 32, NetworkClass::D],

            // Class E: 240-255 (Reserved)
            'Class E start (240.0.0.0)' => ['240.0.0.0', 32, NetworkClass::E],
            'Class E typical (250.0.0.0)' => ['250.0.0.0', 32, NetworkClass::E],
            'Class E limited broadcast (255.255.255.255)' => ['255.255.255.255', 32, NetworkClass::E],

            // "This network" (0.x.x.x) - special case
            '0.0.0.0 this network' => ['0.0.0.0', 8, NetworkClass::A],
            '0.255.255.255 this network end' => ['0.255.255.255', 32, NetworkClass::A],
        ];
    }

    /* ************************** *
     * getDefaultClassMask() TESTS
     * ************************** */

    #[Test]
    #[DataProvider('dataProviderForDefaultClassMask')]
    public function testGetDefaultClassMask(string $ipAddress, int $networkSize, ?string $expectedMask): void
    {
        // Given
        $subnet = new Subnet($ipAddress, $networkSize);

        // When
        $result = $subnet->defaultClassMask();

        // Then
        $this->assertSame($expectedMask, $result);
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: ?string}>
     */
    public static function dataProviderForDefaultClassMask(): array
    {
        return [
            // Class A: 255.0.0.0
            'Class A (10.0.0.0)' => ['10.0.0.0', 8, '255.0.0.0'],
            'Class A with different subnet' => ['10.0.0.0', 24, '255.0.0.0'],
            'Class A boundary (1.0.0.0)' => ['1.0.0.0', 8, '255.0.0.0'],
            'Class A boundary (126.0.0.0)' => ['126.0.0.0', 8, '255.0.0.0'],
            'Loopback (127.0.0.1)' => ['127.0.0.1', 8, '255.0.0.0'],

            // Class B: 255.255.0.0
            'Class B (172.16.0.0)' => ['172.16.0.0', 16, '255.255.0.0'],
            'Class B with different subnet' => ['172.16.0.0', 12, '255.255.0.0'],
            'Class B boundary (128.0.0.0)' => ['128.0.0.0', 16, '255.255.0.0'],
            'Class B boundary (191.255.0.0)' => ['191.255.0.0', 16, '255.255.0.0'],

            // Class C: 255.255.255.0
            'Class C (192.168.1.0)' => ['192.168.1.0', 24, '255.255.255.0'],
            'Class C with different subnet' => ['192.168.1.0', 25, '255.255.255.0'],
            'Class C boundary (192.0.0.0)' => ['192.0.0.0', 24, '255.255.255.0'],
            'Class C boundary (223.255.255.0)' => ['223.255.255.0', 24, '255.255.255.0'],

            // Class D: N/A (Multicast)
            'Class D multicast' => ['224.0.0.1', 32, null],
            'Class D end' => ['239.255.255.255', 32, null],

            // Class E: N/A (Reserved)
            'Class E reserved' => ['240.0.0.0', 32, null],
            'Class E limited broadcast' => ['255.255.255.255', 32, null],

            // "This network" (0.x.x.x) - treated as Class A
            '0.0.0.0' => ['0.0.0.0', 8, '255.0.0.0'],
        ];
    }

    /* ************************** *
     * getDefaultClassPrefix() TESTS
     * ************************** */

    #[Test]
    #[DataProvider('dataProviderForDefaultClassPrefix')]
    public function testGetDefaultClassPrefix(string $ipAddress, int $networkSize, ?int $expectedPrefix): void
    {
        // Given
        $subnet = new Subnet($ipAddress, $networkSize);

        // When
        $result = $subnet->defaultClassPrefix();

        // Then
        $this->assertSame($expectedPrefix, $result);
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: ?int}>
     */
    public static function dataProviderForDefaultClassPrefix(): array
    {
        return [
            // Class A: /8
            'Class A (10.0.0.0)' => ['10.0.0.0', 8, 8],
            'Class A subnetted (10.0.0.0/24)' => ['10.0.0.0', 24, 8],
            'Loopback (127.0.0.1)' => ['127.0.0.1', 8, 8],

            // Class B: /16
            'Class B (172.16.0.0)' => ['172.16.0.0', 16, 16],
            'Class B supernetted (172.16.0.0/12)' => ['172.16.0.0', 12, 16],
            'Class B subnetted (172.16.1.0/24)' => ['172.16.1.0', 24, 16],

            // Class C: /24
            'Class C (192.168.1.0)' => ['192.168.1.0', 24, 24],
            'Class C subnetted (192.168.1.0/25)' => ['192.168.1.0', 25, 24],
            'Class C subnetted (192.168.1.0/30)' => ['192.168.1.0', 30, 24],

            // Class D: null (Multicast)
            'Class D multicast' => ['224.0.0.1', 32, null],
            'Class D end' => ['239.255.255.255', 32, null],

            // Class E: null (Reserved)
            'Class E reserved' => ['240.0.0.0', 32, null],
            'Class E limited broadcast' => ['255.255.255.255', 32, null],

            // "This network" (0.x.x.x) - treated as Class A
            '0.0.0.0' => ['0.0.0.0', 8, 8],
        ];
    }

    /* ************************** *
     * isClassful() TESTS
     * ************************** */

    #[Test]
    #[DataProvider('dataProviderForIsClassful')]
    public function testIsClassful(string $ipAddress, int $networkSize, bool $expectedIsClassful): void
    {
        // Given
        $subnet = new Subnet($ipAddress, $networkSize);

        // When
        $result = $subnet->isClassful();

        // Then
        $this->assertSame($expectedIsClassful, $result);
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: bool}>
     */
    public static function dataProviderForIsClassful(): array
    {
        return [
            // Class A classful (/8)
            'Class A classful (10.0.0.0/8)' => ['10.0.0.0', 8, true],
            'Class A classful (1.0.0.0/8)' => ['1.0.0.0', 8, true],
            'Loopback classful (127.0.0.0/8)' => ['127.0.0.0', 8, true],

            // Class A subnetted (not classful)
            'Class A subnetted (10.0.0.0/16)' => ['10.0.0.0', 16, false],
            'Class A subnetted (10.0.0.0/24)' => ['10.0.0.0', 24, false],
            'Class A host (10.0.0.1/32)' => ['10.0.0.1', 32, false],

            // Class B classful (/16)
            'Class B classful (172.16.0.0/16)' => ['172.16.0.0', 16, true],
            'Class B classful (128.0.0.0/16)' => ['128.0.0.0', 16, true],
            'Class B classful (191.255.0.0/16)' => ['191.255.0.0', 16, true],

            // Class B supernetted/subnetted (not classful)
            'Class B supernetted (172.16.0.0/12)' => ['172.16.0.0', 12, false],
            'Class B subnetted (172.16.0.0/24)' => ['172.16.0.0', 24, false],
            'Class B host (172.16.0.1/32)' => ['172.16.0.1', 32, false],

            // Class C classful (/24)
            'Class C classful (192.168.1.0/24)' => ['192.168.1.0', 24, true],
            'Class C classful (192.0.0.0/24)' => ['192.0.0.0', 24, true],
            'Class C classful (223.255.255.0/24)' => ['223.255.255.0', 24, true],

            // Class C supernetted/subnetted (not classful)
            'Class C supernetted (192.168.0.0/23)' => ['192.168.0.0', 23, false],
            'Class C subnetted (192.168.1.0/25)' => ['192.168.1.0', 25, false],
            'Class C subnetted (192.168.1.0/30)' => ['192.168.1.0', 30, false],
            'Class C host (192.168.1.1/32)' => ['192.168.1.1', 32, false],

            // Class D (Multicast) - never classful (no default mask)
            'Class D multicast' => ['224.0.0.1', 32, false],
            'Class D any prefix' => ['239.255.255.255', 24, false],

            // Class E (Reserved) - never classful (no default mask)
            'Class E reserved' => ['240.0.0.0', 32, false],
            'Class E limited broadcast' => ['255.255.255.255', 32, false],

            // "This network" (0.x.x.x) - treated as Class A
            '0.0.0.0/8 classful' => ['0.0.0.0', 8, true],
            '0.0.0.0/16 not classful' => ['0.0.0.0', 16, false],
        ];
    }

    /* ************************** *
     * EDGE CASE TESTS
     * ************************** */

    /**
     * Tests class boundary at 126/127 (loopback starts)
     */
    #[Test]
    public function testClassBoundaryBetween126And127(): void
    {
        // Given
        $before = new Subnet('126.255.255.255', 32);
        $loopback = new Subnet('127.0.0.1', 8);

        // When/Then - Both should be Class A
        $this->assertSame(NetworkClass::A, $before->networkClass());
        $this->assertSame(NetworkClass::A, $loopback->networkClass());
        $this->assertSame('255.0.0.0', $loopback->defaultClassMask());
    }

    /**
     * Tests class boundary at 127/128 (Class A to Class B)
     */
    #[Test]
    public function testClassBoundaryBetween127And128(): void
    {
        // Given
        $classA = new Subnet('127.255.255.255', 32);
        $classB = new Subnet('128.0.0.0', 16);

        // When/Then
        $this->assertSame(NetworkClass::A, $classA->networkClass());
        $this->assertSame(NetworkClass::B, $classB->networkClass());
    }

    /**
     * Tests class boundary at 191/192 (Class B to Class C)
     */
    #[Test]
    public function testClassBoundaryBetween191And192(): void
    {
        // Given
        $classB = new Subnet('191.255.255.255', 32);
        $classC = new Subnet('192.0.0.0', 24);

        // When/Then
        $this->assertSame(NetworkClass::B, $classB->networkClass());
        $this->assertSame(NetworkClass::C, $classC->networkClass());
    }

    /**
     * Tests class boundary at 223/224 (Class C to Class D)
     */
    #[Test]
    public function testClassBoundaryBetween223And224(): void
    {
        // Given
        $classC = new Subnet('223.255.255.255', 32);
        $classD = new Subnet('224.0.0.0', 32);

        // When/Then
        $this->assertSame(NetworkClass::C, $classC->networkClass());
        $this->assertSame(NetworkClass::D, $classD->networkClass());
    }

    /**
     * Tests class boundary at 239/240 (Class D to Class E)
     */
    #[Test]
    public function testClassBoundaryBetween239And240(): void
    {
        // Given
        $classD = new Subnet('239.255.255.255', 32);
        $classE = new Subnet('240.0.0.0', 32);

        // When/Then
        $this->assertSame(NetworkClass::D, $classD->networkClass());
        $this->assertSame(NetworkClass::E, $classE->networkClass());
    }

    /**
     * Tests /31 and /32 edge cases for isClassful()
     */
    #[Test]
    public function testRfc3021EdgeCasesForIsClassful(): void
    {
        // Given - /31 and /32 networks should never be classful
        $slash31ClassA = new Subnet('10.0.0.0', 31);
        $slash32ClassA = new Subnet('10.0.0.1', 32);
        $slash31ClassB = new Subnet('172.16.0.0', 31);
        $slash32ClassC = new Subnet('192.168.1.1', 32);

        // When/Then - None should be classful
        $this->assertFalse($slash31ClassA->isClassful());
        $this->assertFalse($slash32ClassA->isClassful());
        $this->assertFalse($slash31ClassB->isClassful());
        $this->assertFalse($slash32ClassC->isClassful());

        // But they should still report correct class
        $this->assertSame(NetworkClass::A, $slash31ClassA->networkClass());
        $this->assertSame(NetworkClass::A, $slash32ClassA->networkClass());
        $this->assertSame(NetworkClass::B, $slash31ClassB->networkClass());
        $this->assertSame(NetworkClass::C, $slash32ClassC->networkClass());
    }
}
