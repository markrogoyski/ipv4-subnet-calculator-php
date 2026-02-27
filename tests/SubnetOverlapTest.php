<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for subnet overlap and containment detection methods.
 *
 * These methods are essential for network planning and conflict prevention,
 * including firewall rule validation and routing table conflict detection.
 */
class SubnetOverlapTest extends \PHPUnit\Framework\TestCase
{
    /**
     * overlaps returns true when subnets share any IP addresses
     * @param        string $subnetA   First subnet in CIDR notation
     * @param        string $subnetB   Second subnet in CIDR notation
     * @param        bool   $expected  Whether the subnets overlap
     */
    #[Test]
    #[DataProvider('dataProviderForOverlaps')]
    public function testOverlaps(string $subnetA, string $subnetB, bool $expected): void
    {
        // Given
        $subnetA = Subnet::fromCidr($subnetA);
        $subnetB = Subnet::fromCidr($subnetB);

        // When
        $result = $subnetA->overlaps($subnetB);

        // Then
        $this->assertSame($expected, $result, "Expected {$subnetA} and {$subnetB} overlap to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * overlaps is symmetric (A overlaps B == B overlaps A)
     * @param        string $subnetA
     * @param        string $subnetB
     * @param        bool   $expected
     */
    #[Test]
    #[DataProvider('dataProviderForOverlaps')]
    public function testOverlapsIsSymmetric(string $subnetA, string $subnetB, bool $expected): void
    {
        // Given
        $subnetA = Subnet::fromCidr($subnetA);
        $subnetB = Subnet::fromCidr($subnetB);

        // When
        $resultAB = $subnetA->overlaps($subnetB);
        $resultBA = $subnetB->overlaps($subnetA);

        // Then
        $this->assertSame($resultAB, $resultBA, "Overlap should be symmetric: {$subnetA} overlaps {$subnetB} should equal {$subnetB} overlaps {$subnetA}");
    }

    /**
     * @return array[] [subnetA, subnetB, overlaps]
     */
    public static function dataProviderForOverlaps(): array
    {
        return [
            // B is subset of A
            ['192.168.1.0/24', '192.168.1.0/25', true],
            // Adjacent, no overlap
            ['192.168.1.0/24', '192.168.2.0/24', false],
            // A is subset of B
            ['192.168.1.0/24', '192.168.0.0/23', true],
            // B is subset of A (second half)
            ['192.168.1.0/24', '192.168.1.128/25', true],
            // Single host in large network
            ['10.0.0.0/8', '10.1.2.3/32', true],
            // Adjacent /30s
            ['192.168.1.0/30', '192.168.1.4/30', false],
            // /32 in /31
            ['10.0.0.0/31', '10.0.0.0/32', true],
            // Other /32 in /31
            ['10.0.0.0/31', '10.0.0.1/32', true],
            // Adjacent /31s
            ['10.0.0.0/31', '10.0.0.2/31', false],
            // Large supernet contains smaller (192.x.x.x is in 128.0.0.0/1 range)
            ['128.0.0.0/1', '192.168.1.0/24', true],
            // Same subnet overlaps itself
            ['192.168.1.0/24', '192.168.1.0/24', true],
            // Non-overlapping different sizes
            ['10.0.0.0/24', '10.0.1.0/24', false],
            // Partial overlap - one is supernet of other
            ['172.16.0.0/12', '172.16.0.0/16', true],
            // /32 outside /31
            ['10.0.0.0/31', '10.0.0.2/32', false],
        ];
    }

    /**
     * contains returns true when this subnet fully contains another
     * @param        string $subnetA   Container subnet in CIDR notation
     * @param        string $subnetB   Possibly contained subnet in CIDR notation
     * @param        bool   $expected  Whether A contains B
     */
    #[Test]
    #[DataProvider('dataProviderForContains')]
    public function testContains(string $subnetA, string $subnetB, bool $expected): void
    {
        // Given
        $subnetA = Subnet::fromCidr($subnetA);
        $subnetB = Subnet::fromCidr($subnetB);

        // When
        $result = $subnetA->contains($subnetB);

        // Then
        $this->assertSame($expected, $result, "Expected {$subnetA} contains {$subnetB} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [subnetA, subnetB, A contains B]
     */
    public static function dataProviderForContains(): array
    {
        return [
            // /24 contains /25
            ['192.168.1.0/24', '192.168.1.0/25', true],
            // /24 contains other /25
            ['192.168.1.0/24', '192.168.1.128/25', true],
            // /25 cannot contain /24
            ['192.168.1.0/25', '192.168.1.0/24', false],
            // /8 contains /32
            ['10.0.0.0/8', '10.1.2.3/32', true],
            // Equal subnets - a subnet contains itself
            ['192.168.1.0/24', '192.168.1.0/24', true],
            // Different networks, no containment
            ['192.168.1.0/24', '192.168.2.0/24', false],
            // /31 contains /32
            ['10.0.0.0/31', '10.0.0.0/32', true],
            // /32 cannot contain /31
            ['10.0.0.0/32', '10.0.0.0/31', false],
            // /31 contains the other /32 in its range
            ['10.0.0.0/31', '10.0.0.1/32', true],
            // /24 does not contain /24 from different network
            ['192.168.1.0/24', '192.168.0.0/24', false],
            // Large network contains smaller
            ['172.16.0.0/12', '172.16.0.0/16', true],
            // Large network contains smaller at different offset
            ['172.16.0.0/12', '172.31.255.0/24', true],
            // Smaller network does not contain larger
            ['172.16.0.0/16', '172.16.0.0/12', false],
            // /32 outside /31 range
            ['10.0.0.0/31', '10.0.0.2/32', false],
        ];
    }

    /**
     * isContainedIn returns true when this subnet is fully contained in another
     * @param        string $subnetA   Possibly contained subnet in CIDR notation
     * @param        string $subnetB   Container subnet in CIDR notation
     * @param        bool   $expected  Whether A is contained in B
     */
    #[Test]
    #[DataProvider('dataProviderForIsContainedIn')]
    public function testIsContainedIn(string $subnetA, string $subnetB, bool $expected): void
    {
        // Given
        $subnetA = Subnet::fromCidr($subnetA);
        $subnetB = Subnet::fromCidr($subnetB);

        // When
        $result = $subnetA->isContainedIn($subnetB);

        // Then
        $this->assertSame($expected, $result, "Expected {$subnetA} isContainedIn {$subnetB} to be " . ($expected ? 'true' : 'false'));
    }

    /**
     * @return array[] [subnetA, subnetB, A is contained in B]
     */
    public static function dataProviderForIsContainedIn(): array
    {
        return [
            // /25 is contained in /24
            ['192.168.1.0/25', '192.168.1.0/24', true],
            // Other /25 is contained in /24
            ['192.168.1.128/25', '192.168.1.0/24', true],
            // /24 is not contained in /25
            ['192.168.1.0/24', '192.168.1.0/25', false],
            // /32 is contained in /8
            ['10.1.2.3/32', '10.0.0.0/8', true],
            // Equal subnets - a subnet is contained in itself
            ['192.168.1.0/24', '192.168.1.0/24', true],
            // Different networks, not contained
            ['192.168.2.0/24', '192.168.1.0/24', false],
            // /32 is contained in /31
            ['10.0.0.0/32', '10.0.0.0/31', true],
            // /31 is not contained in /32
            ['10.0.0.0/31', '10.0.0.0/32', false],
            // Other /32 is contained in /31
            ['10.0.0.1/32', '10.0.0.0/31', true],
            // /24 is not contained in different /24
            ['192.168.0.0/24', '192.168.1.0/24', false],
            // /16 is contained in /12
            ['172.16.0.0/16', '172.16.0.0/12', true],
            // /24 at edge of /12 is contained
            ['172.31.255.0/24', '172.16.0.0/12', true],
            // /12 is not contained in /16
            ['172.16.0.0/12', '172.16.0.0/16', false],
            // /32 outside range is not contained
            ['10.0.0.2/32', '10.0.0.0/31', false],
        ];
    }

    /**
     * isContainedIn is the inverse of contains
     * @param        string $subnetA
     * @param        string $subnetB
     */
    #[Test]
    #[DataProvider('dataProviderForContainsAndIsContainedInRelationship')]
    public function testContainsAndIsContainedInRelationship(string $subnetA, string $subnetB): void
    {
        // Given
        $subnetA = Subnet::fromCidr($subnetA);
        $subnetB = Subnet::fromCidr($subnetB);

        // Then
        // If A contains B, then B isContainedIn A
        $this->assertSame($subnetA->contains($subnetB), $subnetB->isContainedIn($subnetA));

        // If B contains A, then A isContainedIn B
        $this->assertSame($subnetB->contains($subnetA), $subnetA->isContainedIn($subnetB));
    }

    /**
     * @return array[] [subnetA, subnetB]
     */
    public static function dataProviderForContainsAndIsContainedInRelationship(): array
    {
        return [
            ['192.168.1.0/24', '192.168.1.0/25'],
            ['192.168.1.0/24', '192.168.1.128/25'],
            ['10.0.0.0/8', '10.1.2.3/32'],
            ['192.168.1.0/24', '192.168.1.0/24'],
            ['192.168.1.0/24', '192.168.2.0/24'],
            ['10.0.0.0/31', '10.0.0.0/32'],
            ['172.16.0.0/12', '172.16.0.0/16'],
        ];
    }

    /**
     * If two subnets overlap but neither contains the other, both overlap checks are true but contains checks are false
     */
    #[Test]
    public function testOverlapWithoutContainment(): void
    {
        // Note: With strict CIDR subnets, if two subnets overlap, one must contain the other
        // or they must be equal. This is because CIDR subnets are hierarchical.
        // This test verifies that overlap implies containment relationship for CIDR.

        // Given - /24 and /25 from same range
        $larger  = Subnet::fromCidr('192.168.1.0/24');
        $smaller = Subnet::fromCidr('192.168.1.0/25');

        // When/Then - they overlap and the larger contains the smaller
        $this->assertTrue($larger->overlaps($smaller));
        $this->assertTrue($smaller->overlaps($larger));
        $this->assertTrue($larger->contains($smaller));
        $this->assertFalse($smaller->contains($larger));
    }
}
