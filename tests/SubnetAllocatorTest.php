<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\AllocationPlan;
use IPv4\Subnet;
use IPv4\SubnetAllocator;
use IPv4\Subnets;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for deterministic variable-size allocation within one parent network.
 */
class SubnetAllocatorTest extends TestCase
{
    /* ************************** *
     * Successful plans
     * ************************** */

    /**
     * allocate places each requirement and returns allocations, reservations and free space
     *
     * @param string                $parentCidr
     * @param array<string, int>    $requirements
     * @param array<string>         $reservedCidrs
     * @param array<string, string> $expectedAllocations
     * @param list<string>          $expectedReserved
     * @param list<string>          $expectedRemaining
     */
    #[Test]
    #[DataProvider('dataProviderForPlans')]
    public function allocateReturnsTheExpectedPlan(
        string $parentCidr,
        array $requirements,
        array $reservedCidrs,
        array $expectedAllocations,
        array $expectedReserved,
        array $expectedRemaining
    ): void {
        // Given
        $parent = Subnet::fromCidr($parentCidr);
        $reserved = self::subnets($reservedCidrs);

        // When
        $plan = SubnetAllocator::allocate($parent, $requirements, $reserved);

        // Then
        $this->assertSame(
            [$expectedAllocations, $expectedReserved, $expectedRemaining],
            [self::cidrs($plan->allocations()), self::cidrs($plan->reserved()), self::cidrs($plan->remaining())]
        );
    }

    /**
     * @return array<string, array{string, array<string, int>, array<string>, array<string, string>, list<string>, list<string>}>
     */
    public static function dataProviderForPlans(): array
    {
        return [
            'larger block placed first'            => [
                '10.0.0.0/22',
                ['Branch' => 50, 'HQ' => 500],
                [],
                ['Branch' => '10.0.2.0/26', 'HQ' => '10.0.0.0/23'],
                [],
                ['10.0.2.64/26', '10.0.2.128/25', '10.0.3.0/24'],
            ],
            'equal sizes ordered by name'          => [
                '192.168.0.0/24',
                ['b' => 20, 'a' => 20, 'c' => 20],
                [],
                ['b' => '192.168.0.32/27', 'a' => '192.168.0.0/27', 'c' => '192.168.0.64/27'],
                [],
                ['192.168.0.96/27', '192.168.0.128/25'],
            ],
            'permuted equal sizes'                 => [
                '192.168.0.0/24',
                ['c' => 20, 'a' => 20, 'b' => 20],
                [],
                ['c' => '192.168.0.64/27', 'a' => '192.168.0.0/27', 'b' => '192.168.0.32/27'],
                [],
                ['192.168.0.96/27', '192.168.0.128/25'],
            ],
            'names compared bytewise'              => [
                '10.0.0.0/26',
                ['a9' => 10, 'a10' => 10, 'Z' => 10, 'a' => 10],
                [],
                ['a9' => '10.0.0.48/28', 'a10' => '10.0.0.32/28', 'Z' => '10.0.0.0/28', 'a' => '10.0.0.16/28'],
                [],
                [],
            ],
            'reserved first block'                 => [
                '10.0.0.0/24',
                ['lan' => 100, 'mgmt' => 50],
                ['10.0.0.0/26'],
                ['lan' => '10.0.0.128/25', 'mgmt' => '10.0.0.64/26'],
                ['10.0.0.0/26'],
                [],
            ],
            'overlapping reservations'             => [
                '10.0.0.0/24',
                ['x' => 30],
                ['10.0.0.0/25', '10.0.0.64/26', '10.0.0.0/26', '10.0.0.130/27', '10.0.0.128/27'],
                ['x' => '10.0.0.160/27'],
                ['10.0.0.0/25', '10.0.0.128/27'],
                ['10.0.0.192/26'],
            ],
            'adjacent reservations merge'          => [
                '10.0.0.0/24',
                [],
                ['r2' => '10.0.0.64/26', 'r1' => '10.0.0.0/26'],
                [],
                ['10.0.0.0/25'],
                ['10.0.0.128/25'],
            ],
            'skips smaller block over octet carry' => [
                '10.0.0.0/23',
                ['a' => 200],
                ['10.0.0.0/25'],
                ['a' => '10.0.1.0/24'],
                ['10.0.0.0/25'],
                ['10.0.0.128/25'],
            ],
            'exact capacity'                       => [
                '10.0.0.0/24',
                ['a' => 126, 'b' => 62, 'c' => 30, 'd' => 14, 'e' => 6, 'f' => 2, 'g' => 2, 'h' => 1, 'i' => 1, 'j' => 1, 'k' => 1],
                [],
                [
                    'a' => '10.0.0.0/25',
                    'b' => '10.0.0.128/26',
                    'c' => '10.0.0.192/27',
                    'd' => '10.0.0.224/28',
                    'e' => '10.0.0.240/29',
                    'f' => '10.0.0.248/31',
                    'g' => '10.0.0.250/31',
                    'h' => '10.0.0.252/32',
                    'i' => '10.0.0.253/32',
                    'j' => '10.0.0.254/32',
                    'k' => '10.0.0.255/32',
                ],
                [],
                [],
            ],
            'ending at 255.255.255.255'            => [
                '255.255.255.248/29',
                ['p' => 2, 'q' => 2, 'r' => 1, 's' => 1, 't' => 1, 'u' => 1],
                [],
                [
                    'p' => '255.255.255.248/31',
                    'q' => '255.255.255.250/31',
                    'r' => '255.255.255.252/32',
                    's' => '255.255.255.253/32',
                    't' => '255.255.255.254/32',
                    'u' => '255.255.255.255/32',
                ],
                [],
                [],
            ],
            'last free block at top of space'      => [
                '255.255.255.0/24',
                ['top' => 60],
                ['255.255.255.0/25', '255.255.255.128/26'],
                ['top' => '255.255.255.192/26'],
                ['255.255.255.0/25', '255.255.255.128/26'],
                [],
            ],
            'empty requirements'                   => [
                '10.0.0.0/24',
                [],
                ['10.0.0.16/28'],
                [],
                ['10.0.0.16/28'],
                ['10.0.0.0/28', '10.0.0.32/27', '10.0.0.64/26', '10.0.0.128/25'],
            ],
            'empty requirements and reservations'  => [
                '10.0.0.0/24',
                [],
                [],
                [],
                [],
                ['10.0.0.0/24'],
            ],
            'whole parent reserved'                => [
                '10.0.0.0/24',
                [],
                ['10.0.0.0/24'],
                [],
                ['10.0.0.0/24'],
                [],
            ],
            'host-bit parent is normalized'        => [
                '10.0.0.77/24',
                ['a' => 100],
                [],
                ['a' => '10.0.0.0/25'],
                [],
                ['10.0.0.128/25'],
            ],
            '/0 split into halves'                 => [
                '0.0.0.0/0',
                ['upper' => 2147483646, 'lower' => 2147483646],
                [],
                ['upper' => '128.0.0.0/1', 'lower' => '0.0.0.0/1'],
                [],
                [],
            ],
            '/0 with a reserved half'              => [
                '0.0.0.0/0',
                ['a' => 1073741822],
                ['0.0.0.0/1'],
                ['a' => '128.0.0.0/2'],
                ['0.0.0.0/1'],
                ['192.0.0.0/2'],
            ],
            '/0 whole space'                       => [
                '0.0.0.0/0',
                ['all' => 4294967294],
                [],
                ['all' => '0.0.0.0/0'],
                [],
                [],
            ],
            '/31 two single hosts'                 => [
                '192.0.2.6/31',
                ['a' => 1, 'b' => 1],
                [],
                ['a' => '192.0.2.6/32', 'b' => '192.0.2.7/32'],
                [],
                [],
            ],
            '/31 point-to-point link'              => [
                '192.0.2.7/31',
                ['link' => 2],
                [],
                ['link' => '192.0.2.6/31'],
                [],
                [],
            ],
            '/32 single host'                      => [
                '192.0.2.7/32',
                ['h' => 1],
                [],
                ['h' => '192.0.2.7/32'],
                [],
                [],
            ],
            '/32 reserved'                         => [
                '0.0.0.0/32',
                [],
                ['0.0.0.0/32'],
                [],
                ['0.0.0.0/32'],
                [],
            ],
        ];
    }

    /**
     * Every plan is a disjoint partition of the parent whose allocations are aligned and large enough
     *
     * @param string             $parentCidr
     * @param array<string, int> $requirements
     * @param array<string>      $reservedCidrs
     */
    #[Test]
    #[DataProvider('dataProviderForPlans')]
    public function allocatePartitionsTheParent(string $parentCidr, array $requirements, array $reservedCidrs): void
    {
        // Given
        $parent = Subnet::fromCidr($parentCidr);
        $reserved = self::subnets($reservedCidrs);

        // When
        $plan = SubnetAllocator::allocate($parent, $requirements, $reserved);

        // Then
        $this->assertValidPlan($parent, $requirements, $reserved, $plan);
    }

    /**
     * Small plans agree with independently enumerated address sets
     *
     * @param string             $parentCidr
     * @param array<string, int> $requirements
     * @param array<string>      $reservedCidrs
     */
    #[Test]
    #[DataProvider('dataProviderForSmallPlans')]
    public function allocateMatchesEnumeratedAddressSets(string $parentCidr, array $requirements, array $reservedCidrs): void
    {
        // Given
        $parent = Subnet::fromCidr($parentCidr);
        $reserved = self::subnets($reservedCidrs);

        // When
        $plan = SubnetAllocator::allocate($parent, $requirements, $reserved);

        // Then - The three sets partition the parent's addresses
        $partition = [
            ...self::addresses($plan->allocations()),
            ...self::addresses($plan->reserved()),
            ...self::addresses($plan->remaining()),
        ];
        $this->assertSame(self::addresses([$parent]), self::sorted($partition));

        // And - Reservations are exactly the union of the requested addresses
        $this->assertSame(self::sorted(\array_unique(self::addresses($reserved))), self::addresses($plan->reserved()));
    }

    /**
     * @return array<string, array{string, array<string, int>, array<string>}>
     */
    public static function dataProviderForSmallPlans(): array
    {
        return \array_map(
            static fn (array $case): array => [$case[0], $case[1], $case[2]],
            \array_filter(
                self::dataProviderForPlans(),
                static fn (array $case): bool => Subnet::fromCidr($case[0])->networkSize() >= 22
            )
        );
    }

    /**
     * A large parent with many requests is planned without enumerating addresses
     */
    #[Test]
    public function allocateHandlesALargeParentWithManyRequests(): void
    {
        // Given - Deterministic requests from 1 to about 16 million hosts, around private space
        $parent = Subnet::fromCidr('0.0.0.0/0');
        $reserved = self::subnets(['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '127.0.0.0/8', '255.255.255.255/32']);
        $requirements = [];
        for ($i = 0; $i < 200; $i++) {
            $requirements["site-{$i}"] = 1 + ($i * 7919 * 104729) % (1 << (($i % 24) + 1));
        }

        // When
        $plan = SubnetAllocator::allocate($parent, $requirements, $reserved);

        // Then
        $this->assertValidPlan($parent, $requirements, $reserved, $plan);
    }

    /* ************************** *
     * Placement failures
     * ************************** */

    /**
     * allocate throws a RuntimeException naming the first request that cannot be placed
     *
     * @param string             $parentCidr
     * @param array<string, int> $requirements
     * @param array<string>      $reservedCidrs
     * @param string             $message
     */
    #[Test]
    #[DataProvider('dataProviderForPlacementFailures')]
    public function allocateThrowsWhenARequestCannotBePlaced(string $parentCidr, array $requirements, array $reservedCidrs, string $message): void
    {
        // Given
        $parent = Subnet::fromCidr($parentCidr);
        $reserved = self::subnets($reservedCidrs);

        // Then
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);

        // When
        SubnetAllocator::allocate($parent, $requirements, $reserved);
    }

    /**
     * @return array<string, array{string, array<string, int>, array<string>, string}>
     */
    public static function dataProviderForPlacementFailures(): array
    {
        return [
            'insufficient capacity'       => ['10.0.0.0/24', ['a' => 200, 'b' => 100], [], "Cannot allocate 'b' as a /25: no free space remains"],
            'first failure in plan order' => ['10.0.0.0/24', ['small' => 100, 'big' => 200, 'other' => 100], [], "Cannot allocate 'other' as a /25: no free space remains"],
            'fragmented free space'       => ['10.0.0.0/24', ['x' => 100], ['10.0.0.64/26', '10.0.0.192/26'], "Cannot allocate 'x' as a /25: largest free block is a /26 (128 free addresses in 2 blocks)"],
            'request larger than parent'  => ['10.0.0.0/24', ['x' => 300], [], "Cannot allocate 'x' as a /23: largest free block is a /24 (256 free addresses in 1 block)"],
            'whole parent reserved'       => ['10.0.0.0/24', ['a' => 1], ['10.0.0.0/24'], "Cannot allocate 'a' as a /32: no free space remains"],
            '/32 parent with two hosts'   => ['192.0.2.7/32', ['h' => 2], [], "Cannot allocate 'h' as a /31: largest free block is a /32 (1 free address in 1 block)"],
        ];
    }

    /* ************************** *
     * Validation
     * ************************** */

    /**
     * allocate rejects invalid input with an InvalidArgumentException before placing anything
     *
     * @param array<mixed> $requirements
     * @param array<mixed> $reserved
     * @param string       $message
     */
    #[Test]
    #[DataProvider('dataProviderForInvalidInput')]
    public function allocateRejectsInvalidInput(array $requirements, array $reserved, string $message): void
    {
        // Given
        $parent = Subnet::fromCidr('10.0.0.77/24');

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        // When
        SubnetAllocator::allocate($parent, $requirements, $reserved);
    }

    /**
     * @return array<string, array{array<mixed>, array<mixed>, string}>
     */
    public static function dataProviderForInvalidInput(): array
    {
        return [
            'list requirements'                  => [[5], [], 'Requirement name must be a nonempty string, got integer key 0'],
            'numeric string name'                => [['ok' => 1, '10' => 5], [], 'Requirement name must be a nonempty string, got integer key 10'],
            'empty name'                         => [['' => 5], [], 'Requirement name must be a nonempty string, got empty string'],
            'string count'                       => [['a' => '5'], [], "Requirement 'a' host count must be an int, got string"],
            'float count'                        => [['a' => 5.0], [], "Requirement 'a' host count must be an int, got float"],
            'bool count'                         => [['a' => true], [], "Requirement 'a' host count must be an int, got bool"],
            'null count'                         => [['a' => null], [], "Requirement 'a' host count must be an int, got null"],
            'zero count'                         => [['a' => 0], [], "Requirement 'a': Host count must be positive, got 0"],
            'negative count'                     => [['a' => -1], [], "Requirement 'a': Host count must be positive, got -1"],
            'count beyond IPv4'                  => [['a' => 4294967295], [], "Requirement 'a': Host count 4294967295 exceeds maximum possible hosts in IPv4"],
            'bad count after unplaceable'        => [['big' => 300, 'bad' => 0], [], "Requirement 'bad': Host count must be positive, got 0"],
            'reservation not a subnet'           => [[], ['10.0.0.0/25'], 'Expected Subnet at index 0, got string'],
            'reservation outside parent'         => [[], [Subnet::fromCidr('10.0.1.0/24')], 'Reservation 10.0.1.0/24 is not contained in parent 10.0.0.0/24'],
            'reservation larger than parent'     => [[], [Subnet::fromCidr('10.0.0.0/23')], 'Reservation 10.0.0.0/23 is not contained in parent 10.0.0.0/24'],
            'bad reservation beside unplaceable' => [['big' => 300], [Subnet::fromCidr('10.0.0.0/25'), Subnet::fromCidr('0.0.0.0/0')], 'Reservation 0.0.0.0/0 is not contained in parent 10.0.0.0/24'],
        ];
    }

    /**
     * AllocationPlan rejects parts of the wrong shape when constructed directly
     *
     * @param array<mixed> $allocations
     * @param array<mixed> $reserved
     * @param array<mixed> $remaining
     * @param string       $message
     */
    #[Test]
    #[DataProvider('dataProviderForMalformedPlans')]
    public function allocationPlanRejectsMalformedParts(array $allocations, array $reserved, array $remaining, string $message): void
    {
        // Given the malformed parts

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        // When
        new AllocationPlan($allocations, $reserved, $remaining);
    }

    /**
     * @return array<string, array{array<mixed>, array<mixed>, array<mixed>, string}>
     */
    public static function dataProviderForMalformedPlans(): array
    {
        $subnet = Subnet::fromCidr('10.0.0.0/24');

        return [
            'integer allocation key'  => [[$subnet], [], [], 'Allocation name must be a string, got integer key 0'],
            'allocation not a subnet' => [['a' => '10.0.0.0/24'], [], [], 'Expected Subnet at index a, got string'],
            'reserved not a list'     => [[], ['x' => $subnet], [], 'Reserved and remaining subnets must be lists'],
            'remaining not a list'    => [[], [], [1 => $subnet], 'Reserved and remaining subnets must be lists'],
            'reserved not a subnet'   => [[], [null], [], 'Expected Subnet at index 0, got null'],
            'remaining not a subnet'  => [[], [], [$subnet, 5], 'Expected Subnet at index 1, got int'],
        ];
    }

    /* ************************** *
     * Helpers
     * ************************** */

    /**
     * Assert that a plan is a disjoint, exact partition of the parent that satisfies every requirement.
     *
     * @param Subnet             $parent
     * @param array<string, int> $requirements
     * @param array<Subnet>      $reserved
     * @param AllocationPlan     $plan
     */
    private function assertValidPlan(Subnet $parent, array $requirements, array $reserved, AllocationPlan $plan): void
    {
        $allocations = $plan->allocations();
        $this->assertSame(\array_keys($requirements), \array_keys($allocations), 'allocations in input order');
        $this->assertTrue(\array_is_list($plan->reserved()), 'reserved list');
        $this->assertTrue(\array_is_list($plan->remaining()), 'remaining list');

        foreach ($allocations as $name => $subnet) {
            $others = \array_values(\array_diff_key($allocations, [$name => true]));
            $this->assertTrue($subnet->isNetworkAddress(), "{$name}: aligned");
            $this->assertTrue($parent->contains($subnet), "{$name}: contained");
            $this->assertGreaterThanOrEqual($requirements[$name], $subnet->hostCount(), "{$name}: large enough");
            $this->assertSame([], Subnets::intersection([$subnet], $others), "{$name}: disjoint from other allocations");
            $this->assertSame([], Subnets::intersection([$subnet], $reserved), "{$name}: avoids reservations");
        }

        $all = [...\array_values($allocations), ...$plan->reserved(), ...$plan->remaining()];
        $this->assertSame(
            $parent->addressCount(),
            \array_sum(\array_map(static fn (Subnet $subnet): int => $subnet->addressCount(), $all)),
            'allocated + reserved + remaining addresses = parent addresses'
        );
        $this->assertSame([], Subnets::difference([$parent], $all), 'union covers the parent');
        $this->assertSame(self::cidrs(Subnets::intersection($reserved, [$parent])), self::cidrs($plan->reserved()), 'reserved is the normalized union');
        $this->assertSame(self::cidrs(Subnets::aggregate($plan->remaining())), self::cidrs($plan->remaining()), 'remaining is aggregated');
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
     * Enumerate every address of small subnets, in input order.
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
     * @param array<int> $values
     *
     * @return list<int>
     */
    private static function sorted(array $values): array
    {
        \sort($values);

        return $values;
    }
}
