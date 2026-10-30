<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\IPAddress;
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

    #[Test]
    #[DataProvider('dataProviderForAdjacency')]
    public function testIsAdjacentRecognizesDisjointTouchingBlocks(string $leftCidr, string $rightCidr, bool $expected): void
    {
        // Given
        $left = Subnet::fromCidr($leftCidr);
        $right = Subnet::fromCidr($rightCidr);

        // When
        $forward = $left->isAdjacent($right);
        $reverse = $right->isAdjacent($left);

        // Then
        $this->assertSame($expected, $forward);
        $this->assertSame($expected, $reverse);
        $this->assertSame($leftCidr, $left->cidr());
        $this->assertSame($rightCidr, $right->cidr());
        if ($forward) {
            $this->assertFalse($left->overlaps($right));
            $this->assertFalse($left->contains($right));
            $this->assertFalse($right->contains($left));
        }
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function dataProviderForAdjacency(): array
    {
        return [
            'mergeable siblings'          => ['192.168.1.0/25', '192.168.1.128/25', true],
            'nonmergeable same prefix'    => ['10.0.1.0/24', '10.0.2.0/24', true],
            'unequal prefixes'            => ['10.0.0.0/24', '10.0.1.0/25', true],
            'host-bit inputs'             => ['10.0.0.200/24', '10.0.1.100/25', true],
            'gap between blocks'          => ['192.168.1.0/24', '192.168.3.0/24', false],
            'overlapping blocks'          => ['192.168.1.0/24', '192.168.1.128/25', false],
            'contained block'             => ['10.0.0.0/8', '10.1.0.0/16', false],
            'equal networks'              => ['192.168.1.0/24', '192.168.1.0/24', false],
            'equal host-bit networks'     => ['192.168.1.1/24', '192.168.1.254/24', false],
            'root against root'           => ['0.0.0.0/0', '255.255.255.255/0', false],
            'root against first address'  => ['255.255.255.255/0', '0.0.0.0/32', false],
            'root against last address'   => ['0.0.0.0/0', '255.255.255.255/32', false],
            'touching halves'             => ['0.0.0.0/1', '128.0.0.0/1', true],
            'first point-to-point links'  => ['0.0.0.0/31', '0.0.0.2/31', true],
            'last point-to-point links'   => ['255.255.255.252/31', '255.255.255.254/31', true],
            'first link and next host'    => ['0.0.0.1/31', '0.0.0.2/32', true],
            'last host before last link'  => ['255.255.255.253/32', '255.255.255.255/31', true],
            'first singleton neighbors'   => ['0.0.0.0/32', '0.0.0.1/32', true],
            'last singleton neighbors'    => ['255.255.255.254/32', '255.255.255.255/32', true],
            'singleton gap'               => ['192.0.2.0/32', '192.0.2.2/32', false],
            'equal singleton'             => ['255.255.255.255/32', '255.255.255.255/32', false],
            'first host inside link'      => ['0.0.0.0/31', '0.0.0.0/32', false],
            'last host inside link'       => ['255.255.255.254/31', '255.255.255.255/32', false],
            'octet boundary'              => ['192.0.1.255/32', '192.0.2.0/31', true],
            'high-bit boundary'           => ['127.255.255.255/32', '128.0.0.0/32', true],
            'singletons do not wrap'      => ['255.255.255.255/32', '0.0.0.0/32', false],
            'point-to-points do not wrap' => ['255.255.255.255/31', '0.0.0.1/31', false],
        ];
    }

    /**
     * @param list<int> $leftAddresses
     * @param list<int> $rightAddresses
     */
    #[Test]
    #[DataProvider('dataProviderForBoundedSubnetSets')]
    public function testAdjacencyMatchesIndependentAddressSets(
        string $leftCidr,
        array $leftAddresses,
        string $rightCidr,
        array $rightAddresses
    ): void {
        // Given - Expected sets are enumerated independently of subnet bounds and relations
        $left = Subnet::fromCidr($leftCidr);
        $right = Subnet::fromCidr($rightCidr);
        $common = \array_intersect($leftAddresses, $rightAddresses);
        $expected = $common === [] && (
            \max($leftAddresses) + 1 === \min($rightAddresses)
            || \max($rightAddresses) + 1 === \min($leftAddresses)
        );

        // When
        $forward = $left->isAdjacent($right);
        $reverse = $right->isAdjacent($left);

        // Then
        $this->assertSame($expected, $forward);
        $this->assertSame($expected, $reverse);
        if ($forward) {
            $this->assertFalse($left->overlaps($right));
        }
    }

    /**
     * Pair all blocks of up to eight addresses, including host inputs and boundary windows.
     *
     * @return \Generator<string, array{string, list<int>, string, list<int>}>
     */
    public static function dataProviderForBoundedSubnetSets(): \Generator
    {
        $fixtures = [];
        foreach ([0, 0xC0000200, 0xFFFFFFF8] as $base) {
            foreach ([[1, 32], [2, 31], [4, 30], [8, 29]] as [$size, $prefix]) {
                for ($offset = 0; $offset < 8; $offset += $size) {
                    $start = $base + $offset;
                    $end = $start + $size - 1;
                    $fixtures[] = [\long2ip($end) . '/' . $prefix, \range($start, $end)];
                }
            }
        }

        // Literal endpoints also check a carry into the next octet.
        $fixtures[] = ['192.0.1.255/32', [0xC00001FF]];
        $fixtures[] = ['192.0.2.1/31', [0xC0000200, 0xC0000201]];

        foreach ($fixtures as $leftIndex => [$leftCidr, $leftAddresses]) {
            foreach ($fixtures as $rightIndex => [$rightCidr, $rightAddresses]) {
                yield "{$leftIndex}-{$rightIndex}" => [$leftCidr, $leftAddresses, $rightCidr, $rightAddresses];
            }
        }
    }

    #[Test]
    #[DataProvider('dataProviderForIntersection')]
    public function testIntersectionReturnsCanonicalNarrowerBlock(
        string $leftCidr,
        string $rightCidr,
        ?string $expectedCidr,
        ?string $expectedIp
    ): void {
        // Given
        $left = Subnet::fromCidr($leftCidr);
        $right = Subnet::fromCidr($rightCidr);

        // When
        $forward = $left->intersection($right);
        $reverse = $right->intersection($left);

        // Then
        $this->assertSame($leftCidr, $left->cidr());
        $this->assertSame($rightCidr, $right->cidr());
        $this->assertSame(\explode('/', $leftCidr)[0], $left->ipAddress()->asQuads());
        $this->assertSame(\explode('/', $rightCidr)[0], $right->ipAddress()->asQuads());
        if ($expectedCidr === null) {
            $this->assertNull($forward);
            $this->assertNull($reverse);
            return;
        }
        $this->assertNotNull($forward);
        $this->assertNotNull($reverse);
        $this->assertSame($expectedCidr, $forward->cidr());
        $this->assertSame($expectedCidr, $reverse->cidr());
        $this->assertSame($expectedCidr, $forward->networkCidr());
        $this->assertSame($expectedCidr, $reverse->networkCidr());
        $this->assertSame($expectedIp, $forward->ipAddress()->asQuads());
        $this->assertSame($expectedIp, $reverse->ipAddress()->asQuads());
        $this->assertTrue($left->contains($forward));
        $this->assertTrue($right->contains($forward));
        $this->assertTrue($forward->equals($reverse));
    }

    /**
     * @return array<string, array{string, string, ?string, ?string}>
     */
    public static function dataProviderForIntersection(): array
    {
        return [
            'disjoint blocks'         => ['192.168.1.0/24', '192.168.3.0/24', null, null],
            'touching blocks'         => ['10.0.0.0/24', '10.0.1.0/25', null, null],
            'nested child'            => ['192.168.1.0/24', '192.168.1.128/25', '192.168.1.128/25', '192.168.1.128'],
            'nested parent'           => ['192.168.1.128/25', '192.168.1.0/24', '192.168.1.128/25', '192.168.1.128'],
            'equal canonical blocks'  => ['192.168.1.0/24', '192.168.1.0/24', '192.168.1.0/24', '192.168.1.0'],
            'equal host-bit blocks'   => ['192.168.1.50/24', '192.168.1.200/24', '192.168.1.0/24', '192.168.1.0'],
            'nested host-bit inputs'  => ['10.2.3.4/8', '10.20.30.40/16', '10.20.0.0/16', '10.20.0.0'],
            'equal root inputs'       => ['255.255.255.255/0', '123.45.67.89/0', '0.0.0.0/0', '0.0.0.0'],
            'root and first host'     => ['255.255.255.255/0', '0.0.0.0/32', '0.0.0.0/32', '0.0.0.0'],
            'root and last host'      => ['0.0.0.0/0', '255.255.255.255/32', '255.255.255.255/32', '255.255.255.255'],
            'root and last link'      => ['0.0.0.0/0', '255.255.255.255/31', '255.255.255.254/31', '255.255.255.254'],
            'root and host-bit child' => ['123.45.67.89/0', '192.168.1.200/24', '192.168.1.0/24', '192.168.1.0'],
            'first link first member' => ['0.0.0.1/31', '0.0.0.0/32', '0.0.0.0/32', '0.0.0.0'],
            'first link last member'  => ['0.0.0.0/31', '0.0.0.1/32', '0.0.0.1/32', '0.0.0.1'],
            'last link first member'  => ['255.255.255.255/31', '255.255.255.254/32', '255.255.255.254/32', '255.255.255.254'],
            'last link last member'   => ['255.255.255.254/31', '255.255.255.255/32', '255.255.255.255/32', '255.255.255.255'],
            'equal host-bit links'    => ['255.255.255.255/31', '255.255.255.254/31', '255.255.255.254/31', '255.255.255.254'],
            'singleton in parent'     => ['192.168.1.200/32', '192.168.1.0/24', '192.168.1.200/32', '192.168.1.200'],
            'equal first singleton'   => ['0.0.0.0/32', '0.0.0.0/32', '0.0.0.0/32', '0.0.0.0'],
            'equal last singleton'    => ['255.255.255.255/32', '255.255.255.255/32', '255.255.255.255/32', '255.255.255.255'],
            'opposite singletons'     => ['0.0.0.0/32', '255.255.255.255/32', null, null],
            'singleton outside link'  => ['0.0.0.0/31', '0.0.0.2/32', null, null],
            'neighboring links'       => ['0.0.0.0/31', '0.0.0.2/31', null, null],
            'disjoint halves'         => ['0.0.0.0/1', '128.0.0.0/1', null, null],
            'last link in upper half' => ['128.0.0.0/1', '255.255.255.255/31', '255.255.255.254/31', '255.255.255.254'],
        ];
    }

    /**
     * @param list<int> $leftAddresses
     * @param list<int> $rightAddresses
     */
    #[Test]
    #[DataProvider('dataProviderForBoundedSubnetSets')]
    public function testIntersectionMatchesExactIndependentMembership(
        string $leftCidr,
        array $leftAddresses,
        string $rightCidr,
        array $rightAddresses
    ): void {
        // Given - Enumerated expected sets do not depend on subnet bounds or relations
        $left = Subnet::fromCidr($leftCidr);
        $right = Subnet::fromCidr($rightCidr);
        $expected = \array_values(\array_intersect($leftAddresses, $rightAddresses));

        // When
        $forward = $left->intersection($right);
        $reverse = $right->intersection($left);

        // Then
        if ($expected === []) {
            $this->assertNull($forward);
            $this->assertNull($reverse);
            return;
        }
        $this->assertNotNull($forward);
        $this->assertNotNull($reverse);
        $this->assertSame(\count($expected), $forward->addressCount());
        $this->assertSame(\count($expected), $reverse->addressCount());
        $this->assertSame($expected, \array_map(
            static fn (IPAddress $ip): int => $ip->asInteger(),
            \iterator_to_array($forward->addressRange(), false)
        ));
        $this->assertSame($expected, \array_map(
            static fn (IPAddress $ip): int => $ip->asInteger(),
            \iterator_to_array($reverse->addressRange(), false)
        ));
        $this->assertSame(\long2ip(\min($expected)), $forward->ipAddress()->asQuads());
        $this->assertSame(\long2ip(\min($expected)), $reverse->ipAddress()->asQuads());
        $this->assertTrue($left->contains($forward));
        $this->assertTrue($right->contains($forward));
        $this->assertTrue($forward->equals($reverse));
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
