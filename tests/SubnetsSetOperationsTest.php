<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\IPAddress;
use IPv4\Subnet;
use IPv4\Subnets;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for set operations on subnet collections: intersection, difference and coverage.
 * Also covers longest-prefix matching of an address against a collection.
 */
class SubnetsSetOperationsTest extends TestCase
{
    /* ************************** *
     * intersection()
     * ************************** */

    /**
     * @param array<string> $leftCidrs
     * @param array<string> $rightCidrs
     * @param list<string>  $expectedCidrs
     */
    #[Test]
    #[DataProvider('dataProviderForIntersection')]
    public function testIntersection(array $leftCidrs, array $rightCidrs, array $expectedCidrs): void
    {
        // Given
        $left = self::subnets($leftCidrs);
        $right = self::subnets($rightCidrs);

        // When
        $result = Subnets::intersection($left, $right);

        // Then
        $this->assertSame($expectedCidrs, self::cidrs($result));
    }

    /**
     * @return array<string, array{array<string>, array<string>, list<string>}>
     */
    public static function dataProviderForIntersection(): array
    {
        return [
            'both empty'                     => [[], [], []],
            'empty left'                     => [[], ['10.0.0.0/8'], []],
            'empty right'                    => [['10.0.0.0/8'], [], []],
            'disjoint blocks'                => [['10.0.0.0/24'], ['10.0.1.0/24'], []],
            'nested block'                   => [['10.0.0.0/8'], ['10.1.2.0/24'], ['10.1.2.0/24']],
            'duplicates collapse'            => [['10.0.0.0/24', '10.0.0.0/24'], ['10.0.0.0/24', '10.0.0.0/24'], ['10.0.0.0/24']],
            'overlap across blocks'          => [['10.0.0.0/24', '10.0.1.0/24'], ['10.0.0.128/25', '10.0.1.0/25'], ['10.0.0.128/25', '10.0.1.0/25']],
            'several blocks inside one'      => [['10.0.0.0/8'], ['9.255.255.255/32', '10.0.0.0/31', '10.255.255.255/32', '11.0.0.0/32'], ['10.0.0.0/31', '10.255.255.255/32']],
            'unsorted associative inputs'    => [['b' => '10.0.2.0/24', 'a' => '10.0.0.0/23'], ['x' => '10.0.1.128/25', 'y' => '10.0.2.0/25'], ['10.0.1.128/25', '10.0.2.0/25']],
            'host bits are ignored'          => [['10.0.0.77/24'], ['10.0.0.200/25'], ['10.0.0.128/25']],
            'entire space with blocks'       => [['0.0.0.0/0'], ['192.168.1.0/24', '10.0.0.0/8'], ['10.0.0.0/8', '192.168.1.0/24']],
            'entire space with itself'       => [['0.0.0.0/0'], ['128.0.0.0/1', '0.0.0.0/1'], ['0.0.0.0/0']],
            'point-to-point with one host'   => [['192.0.2.6/31'], ['192.0.2.7/32'], ['192.0.2.7/32']],
            'first address'                  => [['0.0.0.0/24'], ['0.0.0.0/32'], ['0.0.0.0/32']],
            'last address'                   => [['255.255.255.0/24'], ['255.255.255.255/32'], ['255.255.255.255/32']],
            'singleton does not match other' => [['255.255.255.255/32'], ['0.0.0.0/32'], []],
        ];
    }

    /* ************************** *
     * difference()
     * ************************** */

    /**
     * @param array<string> $leftCidrs
     * @param array<string> $rightCidrs
     * @param list<string>  $expectedCidrs
     */
    #[Test]
    #[DataProvider('dataProviderForDifference')]
    public function testDifference(array $leftCidrs, array $rightCidrs, array $expectedCidrs): void
    {
        // Given
        $left = self::subnets($leftCidrs);
        $right = self::subnets($rightCidrs);

        // When
        $result = Subnets::difference($left, $right);

        // Then
        $this->assertSame($expectedCidrs, self::cidrs($result));
    }

    /**
     * @return array<string, array{array<string>, array<string>, list<string>}>
     */
    public static function dataProviderForDifference(): array
    {
        return [
            'both empty'                      => [[], [], []],
            'empty left'                      => [[], ['10.0.0.0/8'], []],
            'empty right aggregates left'     => [['10.0.0.1/32', 'x' => '10.0.0.0/32', 'y' => '10.0.0.0/32'], [], ['10.0.0.0/31']],
            'disjoint subtrahend'             => [['10.0.0.0/24'], ['10.0.1.0/24'], ['10.0.0.0/24']],
            'whole block removed'             => [['10.0.0.0/24'], ['10.0.0.0/23'], []],
            'one-address hole'                => [['192.0.2.0/24'], ['192.0.2.7/32'], ['192.0.2.0/30', '192.0.2.4/31', '192.0.2.6/32', '192.0.2.8/29', '192.0.2.16/28', '192.0.2.32/27', '192.0.2.64/26', '192.0.2.128/25']],
            'subtrahend spans several blocks' => [['10.0.0.0/24', '10.0.2.0/24'], ['10.0.0.128/25', '10.0.1.0/24', '10.0.2.0/25'], ['10.0.0.0/25', '10.0.2.128/25']],
            'unsorted associative inputs'     => [['b' => '10.0.2.0/24', 'a' => '10.0.0.0/24'], ['y' => '10.0.2.0/25', 'x' => '10.0.0.0/25'], ['10.0.0.128/25', '10.0.2.128/25']],
            'host bits are ignored'           => [['10.0.0.77/24'], ['10.0.0.200/25'], ['10.0.0.0/25']],
            'point-to-point minus one host'   => [['192.0.2.6/31'], ['192.0.2.7/32'], ['192.0.2.6/32']],
            'first address removed'           => [['0.0.0.0/30'], ['0.0.0.0/32'], ['0.0.0.1/32', '0.0.0.2/31']],
            'last address removed'            => [['255.255.255.252/30'], ['255.255.255.255/32'], ['255.255.255.252/31', '255.255.255.254/32']],
            'entire space minus half'         => [['0.0.0.0/0'], ['0.0.0.0/1'], ['128.0.0.0/1']],
            'entire space minus last address' => [['0.0.0.0/0'], ['255.255.255.255/32'], ['0.0.0.0/1', '128.0.0.0/2', '192.0.0.0/3', '224.0.0.0/4', '240.0.0.0/5', '248.0.0.0/6', '252.0.0.0/7', '254.0.0.0/8', '255.0.0.0/9', '255.128.0.0/10', '255.192.0.0/11', '255.224.0.0/12', '255.240.0.0/13', '255.248.0.0/14', '255.252.0.0/15', '255.254.0.0/16', '255.255.0.0/17', '255.255.128.0/18', '255.255.192.0/19', '255.255.224.0/20', '255.255.240.0/21', '255.255.248.0/22', '255.255.252.0/23', '255.255.254.0/24', '255.255.255.0/25', '255.255.255.128/26', '255.255.255.192/27', '255.255.255.224/28', '255.255.255.240/29', '255.255.255.248/30', '255.255.255.252/31', '255.255.255.254/32']],
            'entire space minus entire space' => [['0.0.0.0/0'], ['0.0.0.0/0'], []],
            'block minus entire space'        => [['10.0.0.0/8'], ['0.0.0.0/0'], []],
        ];
    }

    /* ************************** *
     * covers()
     * ************************** */

    /**
     * @param array<string> $coveringCidrs
     * @param array<string> $requiredCidrs
     */
    #[Test]
    #[DataProvider('dataProviderForCovers')]
    public function testCovers(array $coveringCidrs, array $requiredCidrs, bool $expected): void
    {
        // Given
        $covering = self::subnets($coveringCidrs);
        $required = self::subnets($requiredCidrs);

        // When
        $covers = Subnets::covers($covering, $required);

        // Then
        $this->assertSame($expected, $covers);
    }

    /**
     * @return array<string, array{array<string>, array<string>, bool}>
     */
    public static function dataProviderForCovers(): array
    {
        return [
            'empty covers empty'                 => [[], [], true],
            'anything covers empty'              => [['10.0.0.0/32'], [], true],
            'empty covers no nonempty set'       => [[], ['10.0.0.0/32'], false],
            'sibling halves cover their parent'  => [['192.0.2.128/25', '192.0.2.0/25'], ['192.0.2.0/24'], true],
            'missing one host'                   => [['192.0.2.0/25', '192.0.2.128/26', '192.0.2.192/27', '192.0.2.224/28', '192.0.2.240/29', '192.0.2.248/30', '192.0.2.252/31', '192.0.2.254/32'], ['192.0.2.0/24'], false],
            'gap between blocks'                 => [['10.0.0.0/25', '10.0.1.0/24'], ['10.0.0.0/23'], false],
            'broader block covers narrower'      => [['10.0.0.0/8'], ['10.1.0.0/16', '10.2.3.4/32'], true],
            'narrower block does not cover'      => [['10.0.0.0/24'], ['10.0.0.0/23'], false],
            'duplicate associative requirements' => [['x' => '10.0.0.0/24'], ['a' => '10.0.0.0/25', 'b' => '10.0.0.0/25'], true],
            'host bits are ignored'              => [['10.0.0.77/24'], ['10.0.0.200/24'], true],
            'entire space covers both endpoints' => [['0.0.0.0/0'], ['255.255.255.255/32', '0.0.0.0/32'], true],
            'two halves cover entire space'      => [['128.0.0.0/1', '0.0.0.0/1'], ['0.0.0.0/0'], true],
            'point-to-point needs both hosts'    => [['192.0.2.6/32'], ['192.0.2.6/31'], false],
            'host covers itself'                 => [['255.255.255.255/32'], ['255.255.255.255/32'], true],
        ];
    }

    /* ************************** *
     * longestPrefixMatch()
     * ************************** */

    /**
     * @param array<string> $cidrs
     */
    #[Test]
    #[DataProvider('dataProviderForLongestPrefixMatch')]
    public function testLongestPrefixMatch(array $cidrs, string $ip, int|string|null $expectedKey): void
    {
        // Given
        $subnets = self::subnets($cidrs);
        $expected = $expectedKey === null ? null : $subnets[$expectedKey];

        // When
        $fromString = Subnets::longestPrefixMatch($subnets, $ip);
        $fromIPAddress = Subnets::longestPrefixMatch($subnets, new IPAddress($ip));

        // Then
        $this->assertSame($expected, $fromString);
        $this->assertSame($expected, $fromIPAddress);
    }

    /**
     * @return array<string, array{array<string>, string, int|string|null}>
     */
    public static function dataProviderForLongestPrefixMatch(): array
    {
        return [
            'nested picks the /24'            => [['10.0.0.0/8', '10.1.0.0/16', '10.1.2.0/24'], '10.1.2.3', 2],
            'unsorted nested picks the /24'   => [['10.1.2.0/24', '10.0.0.0/8', '10.1.0.0/16'], '10.1.2.3', 0],
            'nested falls back to the /16'    => [['10.0.0.0/8', '10.1.0.0/16', '10.1.2.0/24'], '10.1.3.1', 1],
            'nested falls back to the /8'     => [['10.0.0.0/8', '10.1.0.0/16', '10.1.2.0/24'], '10.200.0.1', 0],
            'outside all blocks'              => [['10.0.0.0/8', '192.168.0.0/16'], '172.16.0.1', null],
            'empty array'                     => [[], '10.0.0.1', null],
            'default route fallback'          => [['0.0.0.0/0', '10.0.0.0/8'], '8.8.8.8', 0],
            'default route loses to /8'       => [['0.0.0.0/0', '10.0.0.0/8'], '10.0.0.1', 1],
            'default route at first address'  => [['0.0.0.0/0'], '0.0.0.0', 0],
            'default route at last address'   => [['0.0.0.0/0'], '255.255.255.255', 0],
            '/31 matches its lower member'    => [['192.0.2.0/24', '192.0.2.6/31'], '192.0.2.6', 1],
            '/31 matches its upper member'    => [['192.0.2.0/24', '192.0.2.6/31'], '192.0.2.7', 1],
            '/31 neighbor falls back to /24'  => [['192.0.2.0/24', '192.0.2.6/31'], '192.0.2.5', 0],
            '/32 beats /31 and /24'           => [['192.0.2.0/24', '192.0.2.6/31', '192.0.2.7/32'], '192.0.2.7', 2],
            '/32 partner falls back to /31'   => [['192.0.2.0/24', '192.0.2.6/31', '192.0.2.7/32'], '192.0.2.6', 1],
            'lone /32 does not match other'   => [['192.0.2.7/32'], '192.0.2.8', null],
            '/32 at last address'             => [['255.255.255.0/24', '255.255.255.255/32'], '255.255.255.255', 1],
            'duplicate tie keeps the first'   => [['10.0.0.0/24', '10.0.0.0/24'], '10.0.0.1', 0],
            'host bit tie keeps the first'    => [['10.0.0.77/24', '10.0.0.200/24'], '10.0.0.1', 0],
            'equal pair after shorter prefix' => [['10.0.0.0/8', '10.1.0.0/16', '10.1.0.0/16'], '10.1.0.1', 1],
            'host bits are ignored'           => [['10.0.0.77/24'], '10.0.0.1', 0],
            'keyed input returns the key'     => [['wan' => '0.0.0.0/0', 'lan' => '192.168.1.0/24'], '192.168.1.10', 'lan'],
        ];
    }

    /**
     * The match is the caller's own instance, so host bits in it survive.
     */
    #[Test]
    public function testLongestPrefixMatchReturnsInputInstanceWithHostBits(): void
    {
        // Given
        $subnet = Subnet::fromCidr('10.0.0.77/24');

        // When
        $match = Subnets::longestPrefixMatch([$subnet], '10.0.0.1');

        // Then
        $this->assertSame($subnet, $match);
        $this->assertSame('10.0.0.77/24', $match->cidr());
    }

    #[Test]
    #[DataProvider('dataProviderForLongestPrefixMatchKeyRecovery')]
    public function testLongestPrefixMatchKeyIsRecoverableWithArraySearch(string $ip, string|false $expectedKey): void
    {
        // Given
        $vlans = [
            'campus' => Subnet::fromCidr('10.20.0.0/16'),
            'staff'  => Subnet::fromCidr('10.20.10.0/24'),
            'voice'  => Subnet::fromCidr('10.20.20.0/24'),
        ];

        // When
        $match = Subnets::longestPrefixMatch($vlans, $ip);
        $key = \array_search($match, $vlans, true);

        // Then
        $this->assertSame($expectedKey, $key);
    }

    /**
     * @return array<string, array{string, string|false}>
     */
    public static function dataProviderForLongestPrefixMatchKeyRecovery(): array
    {
        return [
            'staff VLAN'   => ['10.20.10.17', 'staff'],
            'voice VLAN'   => ['10.20.20.5', 'voice'],
            'campus block' => ['10.20.99.5', 'campus'],
            'no VLAN'      => ['192.0.2.1', false],
        ];
    }

    /**
     * @param array<mixed> $subnets
     */
    #[Test]
    #[DataProvider('dataProviderForLongestPrefixMatchInvalidElements')]
    public function testLongestPrefixMatchRejectsNonSubnetElement(array $subnets, string $message): void
    {
        // Given - $subnets from the data provider contains a non-Subnet element

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        // When
        Subnets::longestPrefixMatch($subnets, '10.0.0.1');
    }

    /**
     * @return array<string, array{array<mixed>, string}>
     */
    public static function dataProviderForLongestPrefixMatchInvalidElements(): array
    {
        $subnet = Subnet::fromCidr('10.0.0.0/8');

        return [
            'string element'                => [['nope'], 'Expected Subnet at index 0, got string'],
            'keyed null element'            => [['a' => $subnet, 'b' => null], 'Expected Subnet at index b, got null'],
            'invalid element after a match' => [[$subnet, 42], 'Expected Subnet at index 1, got int'],
        ];
    }

    /**
     * @param array<Subnet> $subnets
     */
    #[Test]
    #[DataProvider('dataProviderForLongestPrefixMatchInvalidIp')]
    public function testLongestPrefixMatchRejectsInvalidIpAddress(array $subnets, string $ip): void
    {
        // Given - $subnets and an invalid IPv4 address string $ip from the data provider

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IPv4 address');

        // When
        Subnets::longestPrefixMatch($subnets, $ip);
    }

    /**
     * @return array<string, array{array<Subnet>, string}>
     */
    public static function dataProviderForLongestPrefixMatchInvalidIp(): array
    {
        return [
            'empty array'   => [[], '10.0.0.256'],
            'default route' => [[Subnet::fromCidr('0.0.0.0/0')], 'not an ip'],
        ];
    }

    /* ************************** *
     * Validation
     * ************************** */

    /**
     * @param array<mixed> $left
     * @param array<mixed> $right
     */
    #[Test]
    #[DataProvider('dataProviderForInvalidElements')]
    public function testInvalidElementsAreRejectedBeforeEmptyInputsShortCircuit(string $method, array $left, array $right, string $message): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        // When
        [Subnets::class, $method]($left, $right);
    }

    /**
     * @return array<string, array{string, array<mixed>, array<mixed>, string}>
     */
    public static function dataProviderForInvalidElements(): array
    {
        $subnet = Subnet::fromCidr('10.0.0.0/8');

        return [
            'intersection left beside empty right'    => ['intersection', ['a' => 'nope'], [], 'Expected Subnet at index a, got string'],
            'intersection right beside empty left'    => ['intersection', [], [$subnet, null], 'Expected Subnet at index 1, got null'],
            'intersection left beside disjoint right' => ['intersection', [$subnet, '10.0.0.0/8'], [Subnet::fromCidr('11.0.0.0/8')], 'Expected Subnet at index 1, got string'],
            'difference left beside empty right'      => ['difference', [42], [], 'Expected Subnet at index 0, got int'],
            'difference right beside empty left'      => ['difference', [], ['z' => '10.0.0.0/8'], 'Expected Subnet at index z, got string'],
            'difference right beside full removal'    => ['difference', [$subnet], [Subnet::fromCidr('0.0.0.0/0'), 1.5], 'Expected Subnet at index 1, got float'],
            'covers covering beside empty required'   => ['covers', [$subnet, []], [], 'Expected Subnet at index 1, got array'],
            'covers required beside empty covering'   => ['covers', [], [false], 'Expected Subnet at index 0, got bool'],
        ];
    }

    /* ************************** *
     * Invariants
     * ************************** */

    /**
     * Results agree with independently enumerated address sets for every pair of bounded fixtures.
     */
    #[Test]
    public function testSetOperationsMatchIndependentAddressSets(): void
    {
        // Given - Expected sets are listed independently of subnet bounds
        $fixtures = self::boundedCollectionFixtures();

        foreach ($fixtures as $leftName => [$leftCidrs, $leftAddresses]) {
            foreach ($fixtures as $rightName => [$rightCidrs, $rightAddresses]) {
                $left = self::subnets($leftCidrs);
                $right = self::subnets($rightCidrs);
                $expectedIntersection = self::sorted(\array_intersect($leftAddresses, $rightAddresses));
                $expectedDifference = self::sorted(\array_diff($leftAddresses, $rightAddresses));
                $pair = "{$leftName} with {$rightName}";

                // When
                $intersection = Subnets::intersection($left, $right);
                $reversedIntersection = Subnets::intersection($right, $left);
                $reorderedIntersection = Subnets::intersection(\array_reverse($left, true), $right);
                $difference = Subnets::difference($left, $right);
                $covers = Subnets::covers($left, $right);

                // Then
                $this->assertSame($expectedIntersection, self::addresses($intersection), "{$pair}: intersection");
                $this->assertSame(self::cidrs($intersection), self::cidrs($reversedIntersection), "{$pair}: commutative");
                $this->assertSame(self::cidrs($intersection), self::cidrs($reorderedIntersection), "{$pair}: input order");
                $this->assertSame($expectedDifference, self::addresses($difference), "{$pair}: difference");
                $this->assertSame(self::sorted($leftAddresses), self::sorted([...self::addresses($intersection), ...self::addresses($difference)]), "{$pair}: partition");
                $this->assertSame(self::cidrs(Subnets::aggregate($intersection)), self::cidrs($intersection), "{$pair}: minimal intersection");
                $this->assertSame(self::cidrs(Subnets::aggregate($difference)), self::cidrs($difference), "{$pair}: minimal difference");
                $this->assertTrue(\array_is_list($intersection), "{$pair}: intersection list");
                $this->assertTrue(\array_is_list($difference), "{$pair}: difference list");
                $this->assertSame(\array_diff($rightAddresses, $leftAddresses) === [], $covers, "{$pair}: covers");
                $this->assertSame(Subnets::difference($right, $left) === [], $covers, "{$pair}: covers agrees with difference");
            }
        }
    }

    /**
     * Each bounded fixture intersects to its aggregate, subtracts to nothing and covers itself.
     */
    #[Test]
    public function testSetOperationsOfACollectionWithItself(): void
    {
        foreach (self::boundedCollectionFixtures() as $name => [$cidrs]) {
            // Given
            $subnets = self::subnets($cidrs);

            // When
            $intersection = Subnets::intersection($subnets, $subnets);
            $difference = Subnets::difference($subnets, $subnets);
            $covers = Subnets::covers($subnets, $subnets);

            // Then
            $this->assertSame(self::cidrs(Subnets::aggregate($subnets)), self::cidrs($intersection), $name);
            $this->assertSame([], $difference, $name);
            $this->assertTrue($covers, $name);
        }
    }

    /**
     * Small collections at the bottom, an octet carry, and the top of the address space,
     * including duplicate, nested, unsorted, associative and host-bit inputs.
     *
     * @return array<string, array{array<string>, list<int>}>
     */
    private static function boundedCollectionFixtures(): array
    {
        return [
            'empty'              => [[], []],
            'bottom /30'         => [['0.0.0.3/30'], [0, 1, 2, 3]],
            'bottom gap'         => [['0.0.0.0/32', '0.0.0.2/31'], [0, 2, 3]],
            'bottom duplicates'  => [['0.0.0.1/32', '0.0.0.4/30', '0.0.0.1/32'], [1, 4, 5, 6, 7]],
            'bottom nested'      => [['0.0.0.0/29', '0.0.0.2/31'], [0, 1, 2, 3, 4, 5, 6, 7]],
            'bottom associative' => [['a' => '0.0.0.7/31', 'b' => '255.255.255.254/32'], [6, 7, 0xFFFFFFFE]],
            'carry hosts'        => [['192.0.1.255/32', '192.0.2.1/31'], [0xC00001FF, 0xC0000200, 0xC0000201]],
            'carry /31'          => [['192.0.1.254/31'], [0xC00001FE, 0xC00001FF]],
            'carry unsorted'     => [['192.0.2.0/30', '192.0.1.252/30'], [0xC00001FC, 0xC00001FD, 0xC00001FE, 0xC00001FF, 0xC0000200, 0xC0000201, 0xC0000202, 0xC0000203]],
            'top /32'            => [['255.255.255.255/32'], [0xFFFFFFFF]],
            'top /29'            => [['255.255.255.255/29'], [0xFFFFFFF8, 0xFFFFFFF9, 0xFFFFFFFA, 0xFFFFFFFB, 0xFFFFFFFC, 0xFFFFFFFD, 0xFFFFFFFE, 0xFFFFFFFF]],
            'top and bottom'     => [['255.255.255.250/31', '255.255.255.252/30', '0.0.0.7/32'], [7, 0xFFFFFFFA, 0xFFFFFFFB, 0xFFFFFFFC, 0xFFFFFFFD, 0xFFFFFFFE, 0xFFFFFFFF]],
        ];
    }

    /**
     * @param array<string> $cidrs
     *
     * @return array<Subnet>
     */
    private static function subnets(array $cidrs): array
    {
        return \array_map(static fn (string $cidr): Subnet => Subnet::fromCidr($cidr), $cidrs);
    }

    /**
     * @param array<Subnet> $subnets
     *
     * @return array<string>
     */
    private static function cidrs(array $subnets): array
    {
        return \array_map(static fn (Subnet $subnet): string => $subnet->networkCidr(), $subnets);
    }

    /**
     * Enumerate every address of small subnets, in result order.
     *
     * @param array<Subnet> $subnets
     *
     * @return list<int>
     */
    private static function addresses(array $subnets): array
    {
        $addresses = [];
        foreach ($subnets as $subnet) {
            $start = $subnet->networkAddress()->asInteger();
            \array_push($addresses, ...\range($start, $start + $subnet->addressCount() - 1));
        }

        return $addresses;
    }

    /**
     * @param array<int> $addresses
     *
     * @return list<int>
     */
    private static function sorted(array $addresses): array
    {
        $addresses = \array_values(\array_unique($addresses));
        \sort($addresses);

        return $addresses;
    }
}
