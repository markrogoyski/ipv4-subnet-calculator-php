# Advanced Features

This document covers advanced operations including subnet exclusion, collection set operations, variable-size allocation, exact range conversion, direct indexing, reverse-zone coverage, classless reverse delegation, global reachability, CIDR aggregation, utilization analysis, and legacy network class information.

## Table of Contents
- [Canonical Subnet Intersection](#canonical-subnet-intersection)
- [Adjacency and Aggregation](#adjacency-and-aggregation)
- [Deterministic Subnet Ordering](#deterministic-subnet-ordering)
- [Strict Network Configuration](#strict-network-configuration)
- [Subnet Exclusion and Difference Operations](#subnet-exclusion-and-difference-operations)
- [Collection Intersection, Difference, and Coverage](#collection-intersection-difference-and-coverage)
- [Variable-Size Subnet Allocation](#variable-size-subnet-allocation)
- [Exact Range-to-CIDR Conversion](#exact-range-to-cidr-conversion)
- [Constant-Time Address and Subnet Indexing](#constant-time-address-and-subnet-indexing)
- [Exact Reverse-Zone Coverage](#exact-reverse-zone-coverage)
- [Classless Reverse Delegation](#classless-reverse-delegation)
- [Global Reachability](#global-reachability)
- [CIDR Aggregation and Supernetting](#cidr-aggregation-and-supernetting)
- [Utilization Statistics](#utilization-statistics)
- [Network Class Information (Legacy)](#network-class-information-legacy)

## Canonical Subnet Intersection

`intersection()` returns the narrower CIDR when blocks overlap, or `null` when disjoint, including touching neighbors. CIDR blocks are nested whenever they overlap, so no range decomposition is needed. The operation is symmetric by value and uses full address ranges, including network and broadcast addresses.

```php
$scope = IPv4\Subnet::fromCidr('10.1.2.3/8');
$requested = IPv4\Subnet::fromCidr('10.20.30.40/16');
$shared = $scope->intersection($requested);
echo $shared?->cidr() . "\n"; // 10.20.0.0/16
echo $requested->ipAddress()->asQuads() . "\n"; // 10.20.30.40
```

The result's input IP is always the network address, even for equal networks with different original host IPs; `/0`, `/31`, `/32` and both IPv4 boundaries use the same contract. See [restricting blocks to a scope](examples.md#restrict-a-network-block-to-a-scope).

## Adjacency and Aggregation

`isAdjacent()` recognizes disjoint blocks whose full address ranges touch, in either order, even with different prefixes or input host bits. A true result does not guarantee that the blocks can aggregate into one exact CIDR:

```php
$blocks = [
    IPv4\Subnet::fromCidr('10.0.1.0/24'),
    IPv4\Subnet::fromCidr('10.0.2.0/24'),
];
\var_dump($blocks[0]->isAdjacent($blocks[1])); // true
$aggregated = IPv4\Subnets::aggregate($blocks);
echo \implode(', ', \array_map(static fn (IPv4\Subnet $block): string => $block->networkCidr(), $aggregated));
// 10.0.1.0/24, 10.0.2.0/24
```

Only appropriately aligned equal-prefix siblings can merge directly. Equality, overlap, containment and gaps are not adjacency; `/0` has no neighbor. Consecutive `/31` and `/32` blocks can touch, while the maximum address and zero never do. See [finding adjacent blocks](examples.md#find-adjacent-network-blocks) for unequal-prefix neighbors.

## Deterministic Subnet Ordering

Use `compare()` as a sorting callback to order blocks by ascending numeric network address and then ascending prefix. Broader blocks precede narrower ones at the same start. The method returns exactly `-1`, `0` or `1`; zero agrees with `equals()` even when the original inputs contain different host bits.

```php
$networks = [
    IPv4\Subnet::fromCidr('10.0.0.1/16'),
    IPv4\Subnet::fromCidr('10.255.255.255/8'),
    IPv4\Subnet::fromCidr('2.0.0.1/8'),
];
\usort($networks, static fn (IPv4\Subnet $a, IPv4\Subnet $b): int => $a->compare($b));
echo \implode(', ', \array_map(static fn (IPv4\Subnet $network): string => $network->networkCidr(), $networks));
// 2.0.0.0/8, 10.0.0.0/8, 10.0.0.0/16
```

Sorting preserves each subnet's original IP; use `networkCidr()` for canonical output. This ordering applies across `/0`, `/31`, `/32` and both IPv4 boundaries. See [sorting network blocks](examples.md#sort-network-blocks) for a larger example.

## Strict Network Configuration

Use `Subnet::fromNetworkCidr()` at a configuration-import boundary when host bits indicate a mistake. It delegates existing CIDR syntax validation, then rejects an unaligned address with `\InvalidArgumentException`. For example, `192.168.1.50/24` is rejected, while `192.168.1.0/24` is accepted. Both malformed input and host-bit errors identify the offending input.

For an already constructed subnet, `isNetworkAddress()` tests the original IP's alignment without changing it. Calling `networkCidr()` on permissively parsed input produces a canonical string; it does not establish that the original input was aligned. The constructor and `fromCidr()` retain their existing host-address behavior.

```php
$configuredNetwork = IPv4\Subnet::fromNetworkCidr('192.0.2.6/31');
\var_dump($configuredNetwork->isNetworkAddress()); // true
```

Only the zero address is aligned for `/0`, `/31` inputs must be even, and every valid `/32` is aligned. See [strict configuration import](examples.md#strict-network-configuration-import) for reporting rejected entries.

## Subnet Exclusion and Difference Operations

Calculate what remains of a subnet after excluding (removing) one or more other subnets. Useful for IP address management (IPAM), reserving address ranges, and carving out specific blocks from larger allocations.

The exclusion methods return the minimal set of CIDR blocks that represent the remaining address space.

### Exclude a Single Subnet

Remove one subnet from another and get the remaining address space:

```php
// Allocate a /24 network but reserve the first /26 for infrastructure
$allocated = IPv4\Subnet::fromCidr('192.168.1.0/24');
$reserved  = IPv4\Subnet::fromCidr('192.168.1.0/26');

$available = $allocated->exclude($reserved);
// Returns array of Subnet instances:
// [0] => 192.168.1.64/26
// [1] => 192.168.1.128/25
// Available address space: 192.168.1.64-192.168.1.255 (192 addresses)

foreach ($available as $subnet) {
    echo $subnet->cidr() . "\n";
}
```

### Exclude Multiple Subnets

Remove multiple subnets from a base subnet:

```php
// Allocate a /24 but reserve multiple ranges
$allocated = IPv4\Subnet::fromCidr('10.0.0.0/24');

$reserved = [
    IPv4\Subnet::fromCidr('10.0.0.0/26'),   // First quarter for infrastructure
    IPv4\Subnet::fromCidr('10.0.0.128/26'), // Third quarter for management
];

$available = $allocated->excludeAll($reserved);
// Returns array of Subnet instances:
// [0] => 10.0.0.64/26
// [1] => 10.0.0.192/26
// Available: 10.0.0.64-127 and 10.0.0.192-255 (128 addresses)

foreach ($available as $subnet) {
    echo "{$subnet->cidr()}: {$subnet->addressCount()} addresses\n";
}
```

### Practical Use Cases

#### Reserve Network and Broadcast Addresses

```php
// Remove network and broadcast addresses from a subnet
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');

$exclusions = [
    IPv4\Subnet::fromCidr('192.168.1.0/32'),   // Network address
    IPv4\Subnet::fromCidr('192.168.1.255/32'), // Broadcast address
];

$usableSpace = $subnet->excludeAll($exclusions);
// Returns CIDR blocks representing 192.168.1.1-254 (254 addresses)

echo "Usable address space after excluding network and broadcast:\n";
foreach ($usableSpace as $block) {
    echo "  {$block->cidr()}\n";
}
```

#### Carve Out Reserved Ranges from Large Allocation

```php
// ISP allocates a /16 but needs to exclude documentation ranges
$allocation = IPv4\Subnet::fromCidr('192.0.0.0/16');
$testNet1   = IPv4\Subnet::fromCidr('192.0.2.0/24'); // TEST-NET-1

$usableSpace = $allocation->exclude($testNet1);
// Returns optimal CIDR blocks for all addresses except 192.0.2.0/24

echo "Usable space: " . \count($usableSpace) . " CIDR blocks\n";
foreach ($usableSpace as $block) {
    echo "  {$block->cidr()}\n";
}
```

#### Sequential Subnet Carving

```php
// Start with a large block and carve out assignments
$pool = IPv4\Subnet::fromCidr('10.0.0.0/16');

// Assign subnets to different departments
$engineering = IPv4\Subnet::fromCidr('10.0.0.0/20');
$sales       = IPv4\Subnet::fromCidr('10.0.16.0/20');
$hr          = IPv4\Subnet::fromCidr('10.0.32.0/20');

$remainingPool = $pool->excludeAll([$engineering, $sales, $hr]);
// Returns remaining unallocated address space

echo "Allocated:\n";
echo "  Engineering: {$engineering->cidr()} ({$engineering->addressCount()} IPs)\n";
echo "  Sales: {$sales->cidr()} ({$sales->addressCount()} IPs)\n";
echo "  HR: {$hr->cidr()} ({$hr->addressCount()} IPs)\n";
echo "\nRemaining pool:\n";
foreach ($remainingPool as $block) {
    echo "  {$block->cidr()} ({$block->addressCount()} IPs)\n";
}
```

#### Progressive IPAM Allocation

```php
// Real-world IPAM: Track allocations and remaining space
class IPAMPool {
    private $availableBlocks;

    public function __construct($initialBlock) {
        $this->availableBlocks = [
            IPv4\Subnet::fromCidr($initialBlock)
        ];
    }

    public function allocate($cidr) {
        $allocation = IPv4\Subnet::fromCidr($cidr);
        foreach ($this->availableBlocks as $index => $block) {
            if ($block->contains($allocation)) {
                // Replace the containing free block with what remains of it.
                \array_splice($this->availableBlocks, $index, 1, $block->exclude($allocation));
                return $allocation;
            }
        }

        // Reject requests that are already allocated, outside the pool,
        // or larger than any single free block
        throw new \RuntimeException("{$allocation->networkCidr()} is not available");
    }

    public function getAvailableBlocks() {
        return $this->availableBlocks;
    }
}

// Usage
$ipam = new IPAMPool('10.0.0.0/16');

$ipam->allocate('10.0.0.0/24');   // First allocation
$ipam->allocate('10.0.1.0/24');   // Second allocation
$ipam->allocate('10.0.16.0/20');  // Larger allocation

try {
    $ipam->allocate('10.0.1.0/24');  // Already allocated
} catch (\RuntimeException $e) {
    echo "Allocation failed: {$e->getMessage()}\n";
}

echo "Remaining available blocks:\n";
foreach ($ipam->getAvailableBlocks() as $block) {
    echo "  {$block->cidr()} ({$block->addressCount()} IPs)\n";
}
```

### Behavior Notes

#### Non-overlapping Subnets

If the excluded subnet doesn't overlap with the base subnet, returns the base subnet as a single
element, normalized to its network address (e.g. `192.168.1.100/24` becomes `192.168.1.0/24`).
`excludeAll([])` behaves the same way:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');
$other  = IPv4\Subnet::fromCidr('192.168.2.0/24');

$result = $subnet->exclude($other);
// Returns: [192.168.1.0/24] (no overlap)
```

#### Full Exclusion

If the excluded subnet fully contains the base subnet, returns an empty array:

```php
$small = IPv4\Subnet::fromCidr('10.0.0.0/25');
$large = IPv4\Subnet::fromCidr('10.0.0.0/24');

$result = $small->exclude($large);
// Returns: [] (nothing remains - fully excluded)
```

#### Optimal CIDR Blocks

Results are always properly aligned CIDR blocks (not arbitrary ranges):

```php
$subnet = IPv4\Subnet::fromCidr('10.0.0.0/24');
$middle = IPv4\Subnet::fromCidr('10.0.0.100/32');

$result = $subnet->exclude($middle);
// Returns 8 optimally-sized CIDR blocks representing addresses 10.0.0.0-99 and 10.0.0.101-255
// Result: 10.0.0.0/26, 10.0.0.64/27, 10.0.0.96/30, 10.0.0.101/32, 10.0.0.102/31,
//         10.0.0.104/29, 10.0.0.112/28, 10.0.0.128/25
```

## Collection Intersection, Difference, and Coverage

`Subnets::intersection()`, `Subnets::difference()` and `Subnets::covers()` treat arrays of subnets as address sets. Use them when both sides are collections, such as address pools against allocations, where `excludeAll()` handles only one base subnet:

```php
$pools = [IPv4\Subnet::fromCidr('10.0.0.0/23'), IPv4\Subnet::fromCidr('10.0.4.0/24')];
$allocated = [
    IPv4\Subnet::fromCidr('10.0.0.0/24'),
    IPv4\Subnet::fromCidr('10.0.1.128/26'),
    IPv4\Subnet::fromCidr('10.0.4.0/25'),
];

$free = IPv4\Subnets::difference($pools, $allocated);
// 10.0.1.0/25, 10.0.1.192/26, 10.0.4.128/25

$inPools = IPv4\Subnets::intersection($pools, [
    IPv4\Subnet::fromCidr('10.0.1.0/24'),
    IPv4\Subnet::fromCidr('10.0.4.128/25'),
]);
// 10.0.1.0/24, 10.0.4.128/25
```

Both methods return the fewest canonical CIDR blocks for exactly the resulting addresses, sorted by network address and numerically indexed. Inputs may be unsorted, duplicated, nested or keyed. Intersection is commutative; difference is not.

`covers()` checks whether one collection contains every address of another, even when no single block does:

```php
$halves = [IPv4\Subnet::fromCidr('192.0.2.0/25'), IPv4\Subnet::fromCidr('192.0.2.128/25')];
\var_dump(IPv4\Subnets::covers($halves, [IPv4\Subnet::fromCidr('192.0.2.0/24')])); // true
\var_dump(IPv4\Subnets::covers([], []));                                           // true
\var_dump(IPv4\Subnets::covers([], [IPv4\Subnet::fromCidr('192.0.2.1/32')]));     // false
```

Every collection covers an empty one, and an empty collection covers nothing else. Union is `Subnets::aggregate()` over both arrays' values. See [auditing firewall coverage](examples.md#audit-firewall-coverage).

## Variable-Size Subnet Allocation

`SubnetAllocator::allocate()` plans a VLSM layout: one CIDR block per named host-count requirement, placed inside one parent network around reserved space. It is stateless; the result is an immutable `AllocationPlan`.

```php
$plan = IPv4\SubnetAllocator::allocate(
    IPv4\Subnet::fromCidr('192.168.10.0/24'),
    ['users' => 100, 'servers' => 50, 'printers' => 10, 'uplink' => 2],
    [IPv4\Subnet::fromCidr('192.168.10.0/28')],  // Gateways and infrastructure
);

foreach ($plan->allocations() as $name => $subnet) {
    echo "{$name}: {$subnet}\n";
}
// users: 192.168.10.128/25
// servers: 192.168.10.64/26
// printers: 192.168.10.16/28
// uplink: 192.168.10.32/31

$plan->reserved();   // [192.168.10.0/28]
$plan->remaining();  // [192.168.10.34/31, 192.168.10.36/30, 192.168.10.40/29, 192.168.10.48/28]
```

- **Sizing**: as `SubnetParser::optimalPrefixForHosts()`: one host gets a `/32`, two get a `/31` (RFC 3021), and larger counts add the network and broadcast addresses, so 100 hosts need a `/25`.
- **Placement**: larger blocks first, equal sizes in bytewise name order (`strcmp()`), each at the lowest aligned address that is still free. The same input always gives the same plan, and `allocations()` keeps the caller's requirement order.
- **Reservations**: must lie wholly inside the parent; duplicates, nesting and overlaps are merged. `reserved()` returns their union as the fewest CIDR blocks. The parent's host bits are ignored.
- **Free space**: `remaining()` is the fewest CIDR blocks left over. Allocations, reservations and remaining space are disjoint and together cover exactly the parent. With no requirements, the plan is the parent minus the reservations.
- **All or nothing**: invalid input throws `\InvalidArgumentException` before anything is placed: names must be nonempty strings, counts positive ints no larger than IPv4 allows, and reservations `Subnet` objects inside the parent. A requirement that does not fit throws `\RuntimeException` naming it, its prefix, and the largest free block. No partial plan is returned.

```php
try {
    IPv4\SubnetAllocator::allocate(
        IPv4\Subnet::fromCidr('10.0.0.0/24'),
        ['x' => 100],
        [IPv4\Subnet::fromCidr('10.0.0.64/26'), IPv4\Subnet::fromCidr('10.0.0.192/26')],
    );
} catch (\RuntimeException $e) {
    echo $e->getMessage();
    // Cannot allocate 'x' as a /25: largest free block is a /26 (128 free addresses in 2 blocks)
}
```

Fragmentation can prevent a placement even when enough addresses are free in total, as above. Requirement names are array keys, so PHP keeps only the last of duplicate names and turns numeric strings such as `'10'` into integers, which are rejected; check for duplicates while building the map. Persistent pools, releasing allocations and alternative packing strategies are outside the allocator's scope; to add requirements later without moving existing ones, pass the earlier plan's reservations and allocations together as the next call's reservations: `[...$plan->reserved(), ...\array_values($plan->allocations())]`. Passing only the allocations drops the original reservations, so new blocks can land in them.

### Sizing by Prefix

Host-count sizing maps two hosts to a `/31` and three to a `/29`, so no host count yields a `/30`. `SubnetAllocator::allocateByPrefix()` takes `name => prefix` instead and otherwise behaves exactly like `allocate()`: the same placement order, reservations, all-or-nothing validation and returned plan. Mixed plans need no other API; convert host counts with `SubnetParser::optimalPrefixForHosts()`:

```php
$plan = IPv4\SubnetAllocator::allocateByPrefix(
    IPv4\Subnet::fromCidr('192.168.10.0/24'),
    [
        'users'    => IPv4\SubnetParser::optimalPrefixForHosts(100),
        'servers'  => IPv4\SubnetParser::optimalPrefixForHosts(50),
        'printers' => IPv4\SubnetParser::optimalPrefixForHosts(10),
        'uplink'   => 30,
    ],
    [IPv4\Subnet::fromCidr('192.168.10.0/28')],  // Gateways and infrastructure
);

foreach ($plan->allocations() as $name => $subnet) {
    echo "{$name}: {$subnet}\n";
}
// users: 192.168.10.128/25
// servers: 192.168.10.64/26
// printers: 192.168.10.16/28
// uplink: 192.168.10.32/30

$plan->remaining();  // [192.168.10.36/30, 192.168.10.40/29, 192.168.10.48/28]
```

Prefixes must be ints from 0 to 32; anything else throws `\InvalidArgumentException` before anything is placed. A prefix shorter than the parent's is valid but cannot fit, so it throws the same `\RuntimeException` as any block that the free space cannot hold.

## Exact Range-to-CIDR Conversion

`IPRange::toSubnets()` converts any inclusive range to its smallest exact CIDR cover. Results are canonical, disjoint, and sorted by ascending network address. Even two adjacent addresses may need separate `/32` blocks when they are not aligned to a `/31`:

```php
$range = new IPv4\IPRange(new IPv4\IPAddress('192.0.2.1'), new IPv4\IPAddress('192.0.2.2'));
$subnets = $range->toSubnets();
// 192.0.2.1/32, 192.0.2.2/32
```

Choose the operation according to the required coverage:

| Operation | Input | Result |
|---|---|---|
| `IPRange::toSubnets()` | Any inclusive address range | Fewest CIDRs with exactly that coverage |
| `SubnetParser::fromRange()` | One aligned CIDR range | One subnet; rejects other ranges |
| `Subnets::summarize()` | A nonempty array of subnets | One enclosing CIDR, potentially including extra addresses |

The decomposition uses integer alignment checks without enumerating addresses. Work is proportional to the number of output blocks times the fixed 32-bit width, and memory is proportional to the output block count. The entire IPv4 address space returns a single block:

```php
$all = new IPv4\IPRange(new IPv4\IPAddress('0.0.0.0'), new IPv4\IPAddress('255.255.255.255'));
echo $all->toSubnets()[0]->cidr(); // 0.0.0.0/0
```

## Constant-Time Address and Subnet Indexing

Use direct indexing when you need one address or child subnet from a large allocation. `IPRange::at()`, `IPRange::indexOf()`, and `Subnet::subnetAt()` compute their result directly, independent of the requested index:

```php
$root = IPv4\Subnet::fromCidr('0.0.0.0/0');
$addresses = $root->addressRange();

echo $addresses->at(4_294_967_295); // 255.255.255.255
$addresses->indexOf('255.255.255.255'); // 4_294_967_295
echo $root->subnetAt(32, 4_294_967_295)->cidr(); // 255.255.255.255/32
```

All indices are zero-based. The range methods add or subtract cached integer endpoints. Child lookup calculates the child's size and offset from the parent's network address, after validating the prefix and index. It returns a canonical network even for a parent constructed with host bits set. No preceding addresses or children are visited.

`at()` and `subnetAt()` reject negative and out-of-range indices with `\OutOfRangeException`. `indexOf()` returns `null` for a valid address outside the range and throws `\InvalidArgumentException` for malformed strings. `subnetAt()` requires a prefix strictly greater than the parent's and at most 32; invalid prefixes throw `\InvalidArgumentException` before index validation.

For usable hosts, use `$subnet->hostRange()->at(0)` to get the first host. `/31` ranges include both addresses and `/32` ranges include their one address, following the existing host-range policy.

## Exact Reverse-Zone Coverage

`reverseZones()` returns the smallest set of ordinary octet-aligned reverse zones whose address space equals the network exactly. Each origin is at the next octet boundary at or after the prefix, so zones never reach outside the network:

```php
$zones = IPv4\Subnet::fromCidr('192.0.2.0/25')->reverseZones();
echo \count($zones) . "\n"; // 128
echo $zones[0] . "\n";       // 0.2.0.192.in-addr.arpa
echo $zones[127] . "\n";     // 127.2.0.192.in-addr.arpa
```

A `/25` never yields `2.0.192.in-addr.arpa`, because that zone also covers `192.0.2.128/25`. Per-address zones are exact but cumbersome to delegate; use [`classlessDelegation()`](#classless-reverse-delegation) for one RFC 2317 child zone instead. Origins are ordered by network address with no duplicates, `/0` yields the single origin `in-addr.arpa`, and the input IP's host bits do not change the result.

## Classless Reverse Delegation

[RFC 2317](https://www.rfc-editor.org/rfc/rfc2317.html) delegates a block smaller than a `/24` by creating a child zone and having the parent `/24` zone alias each address's PTR name into it with a CNAME. `classlessDelegation()` returns both halves: the zone names and the parent's aliases.

```php
$delegation = IPv4\Subnet::fromCidr('192.0.2.200/25')->classlessDelegation();
echo $delegation->parentZone() . "\n"; // 2.0.192.in-addr.arpa
echo $delegation->childZone() . "\n";  // 128-255.2.0.192.in-addr.arpa

$records = $delegation->cnameRecords();
echo \count($records) . "\n";                       // 128
echo $records['128.2.0.192.in-addr.arpa'] . "\n";   // 128.128-255.2.0.192.in-addr.arpa
```

- **Scope**: only `/25`–`/32`, always inside one `/24` parent. `/0`–`/24` throw `\InvalidArgumentException`; use `reverseZones()` for octet-aligned zones.
- **Records**: one per address in ascending order, including the network and broadcast addresses, because delegation covers address space rather than usable hosts. Input host bits are ignored.
- **Child label**: defaults to `startLastOctet-endLastOctet` (`7-7` for a `/32`). RFC 2317 leaves naming open, so a custom label is accepted, restricted to one nonnumeric label of 1–63 ASCII letters, digits or hyphens that begins and ends with a letter or digit. Dotted names, zone-file syntax and numeric-only labels, which could collide with PTR owners, are rejected.
- **Not included**: NS and SOA records for the child, the PTR records inside it, and the parent operator's cooperation in publishing the CNAMEs and NS delegation. Names omit trailing dots; append them when writing absolute names in zone files.

## Global Reachability

`isGloballyReachable()` answers whether address policy permits global unicast forwarding, following the "Globally Reachable" column of the [IANA IPv4 Special-Purpose Address Registry](https://www.iana.org/assignments/iana-ipv4-special-registry/). It is separate from `isPublic()` and `addressType()`, which keep their existing classification. A true result is not evidence of allocation, a BGP route or live connectivity.

The most specific matching registry entry decides, so a child entry overrides its parent:

```php
$reachable = static fn (string $ip): string => \var_export((new IPv4\IPAddress($ip))->isGloballyReachable(), true);

echo $reachable('192.0.0.8') . "\n";    // false - IPv4 dummy address (192.0.0.0/24 parent is false)
echo $reachable('192.0.0.9') . "\n";    // true - Port Control Protocol anycast
echo $reachable('192.0.0.10') . "\n";   // true - TURN anycast
echo $reachable('192.0.0.11') . "\n";   // false - back to the 192.0.0.0/24 parent
echo $reachable('192.31.196.1') . "\n"; // true - AS112-v4
echo $reachable('192.88.99.1') . "\n";  // false - deprecated 6to4 relay, reachability left blank
echo $reachable('192.88.99.2') . "\n";  // false - 6a44 relay anycast, explicitly false
echo $reachable('8.8.8.8') . "\n";      // true - ordinary unicast, not in the registry
echo $reachable('233.252.0.1') . "\n";  // false - multicast is outside this unicast check
```

Policy notes:

- Explicit true and false registry values are honored. Blank values, such as the deprecated `192.88.99.0/24` entry, return false conservatively, so false includes "not established by this policy".
- Ordinary unicast space outside the registry returns true. Multicast (`224.0.0.0/4`) is not in the registry but returns false, because its scope is outside a unicast predicate.
- `Subnet::isGloballyReachable()` checks the input IP only, like the other classification methods. `192.0.0.9/24` returns true even though most of that block is not reachable.

### Registry Snapshot

The library uses a checked-in snapshot of the registry, last updated by IANA on **2025-10-09**; it never fetches the registry at runtime. To refresh it:

1. Recheck the live registry and its footnotes, and note its "Last Updated" date.
2. Review every added, removed or changed row, especially the "Globally Reachable" column and blank fields.
3. Add regression tests for each behavior change before updating the data.
4. Update the table and snapshot date in `src/Internal/SpecialPurposeRegistry.php`, and add a CHANGELOG entry.

Tests use the pinned snapshot, not the live registry.

## CIDR Aggregation and Supernetting

Combine multiple subnets into larger summary routes to reduce routing table size. Essential for BGP route summarization, OSPF area design, and efficient network addressing plans.

### Aggregate Multiple Subnets

The `aggregate()` method combines contiguous subnets into the minimal set of larger CIDR blocks. Only truly contiguous subnets are merged - gaps are preserved.

#### Example 1: Two Adjacent /24s Combine into One /23

```php
$subnets = [
    IPv4\Subnet::fromCidr('192.168.0.0/24'),
    IPv4\Subnet::fromCidr('192.168.1.0/24'),
];

$aggregated = IPv4\Subnets::aggregate($subnets);
// Returns: [192.168.0.0/23]

foreach ($aggregated as $summary) {
    echo $summary->cidr() . "\n";
}
```

#### Example 2: Four Consecutive /24s Become One /22

```php
$subnets = [
    IPv4\Subnet::fromCidr('10.0.0.0/24'),
    IPv4\Subnet::fromCidr('10.0.1.0/24'),
    IPv4\Subnet::fromCidr('10.0.2.0/24'),
    IPv4\Subnet::fromCidr('10.0.3.0/24'),
];

$aggregated = IPv4\Subnets::aggregate($subnets);
// Returns: [10.0.0.0/22]

echo "Aggregated " . \count($subnets) . " /24s into: {$aggregated[0]->cidr()}\n";
```

#### Example 3: Non-contiguous Subnets Remain Separate

```php
$subnets = [
    IPv4\Subnet::fromCidr('192.168.0.0/24'),
    IPv4\Subnet::fromCidr('192.168.2.0/24'),  // Gap at .1.0/24
];

$aggregated = IPv4\Subnets::aggregate($subnets);
// Returns: [192.168.0.0/24, 192.168.2.0/24] - cannot combine due to gap

echo "Cannot aggregate - gap exists. Result:\n";
foreach ($aggregated as $subnet) {
    echo "  {$subnet->cidr()}\n";
}
```

### Summarize to Single Supernet

The `summarize()` method finds the smallest single CIDR block that contains all input subnets. Unlike `aggregate()`, this always returns a single subnet but may include addresses not in the original subnets (fills gaps).

#### Example 1: Perfect Fit - No Extra Addresses

```php
$subnets = [
    IPv4\Subnet::fromCidr('192.168.0.0/24'),
    IPv4\Subnet::fromCidr('192.168.1.0/24'),
];

$summary = IPv4\Subnets::summarize($subnets);
// Returns: 192.168.0.0/23 (perfect fit, no waste)

echo "Summary: {$summary->cidr()}\n";
```

#### Example 2: Has Gap - Includes Extra Addresses to Cover Range

```php
$subnets = [
    IPv4\Subnet::fromCidr('192.168.0.0/24'),
    IPv4\Subnet::fromCidr('192.168.2.0/24'),  // Missing .1.0/24
];

$summary = IPv4\Subnets::summarize($subnets);
// Returns: 192.168.0.0/22
// Includes .0.x, .1.x (not in input!), .2.x, and .3.x (not in input!)

echo "Summary: {$summary->cidr()}\n";
echo "Note: Includes 192.168.1.0/24 and 192.168.3.0/24 (not in original input)\n";
```

#### Example 3: Sparse Subnets Need Large Covering Block

```php
$subnets = [
    IPv4\Subnet::fromCidr('10.0.0.0/32'),
    IPv4\Subnet::fromCidr('10.0.0.255/32'),
];

$summary = IPv4\Subnets::summarize($subnets);
// Returns: 10.0.0.0/24 (includes all 254 addresses between them)

echo "Summary: {$summary->cidr()}\n";
echo "Covers: {$summary->addressCount()} addresses (includes many not in input)\n";
```

### Practical Use Cases

#### BGP Route Summarization

```php
// Your organization has these 4 regional office subnets
$offices = [
    IPv4\Subnet::fromCidr('10.1.0.0/24'),  // Office A
    IPv4\Subnet::fromCidr('10.1.1.0/24'),  // Office B
    IPv4\Subnet::fromCidr('10.1.2.0/24'),  // Office C
    IPv4\Subnet::fromCidr('10.1.3.0/24'),  // Office D
];

$summary = IPv4\Subnets::aggregate($offices);
// Returns: [10.1.0.0/22]
// Advertise this single route instead of 4 individual routes

echo "BGP Advertisement: {$summary[0]->cidr()}\n";
echo "Reduces routing table by " . (\count($offices) - \count($summary)) . " entries\n";
```

#### Multiple Data Centers

```php
// Aggregate separate data center allocations
$datacenters = [
    IPv4\Subnet::fromCidr('10.0.0.0/24'),
    IPv4\Subnet::fromCidr('10.0.1.0/24'),
    IPv4\Subnet::fromCidr('192.168.0.0/24'),
    IPv4\Subnet::fromCidr('192.168.1.0/24'),
];

$aggregated = IPv4\Subnets::aggregate($datacenters);
// Returns: [10.0.0.0/23, 192.168.0.0/23]
// Two summary routes for two non-contiguous regions

echo "Data center routes:\n";
foreach ($aggregated as $route) {
    echo "  {$route->cidr()}\n";
}
```

#### Finding Covering Supernet for ACLs

```php
// Allow access to multiple department subnets with one firewall rule
$departments = [
    IPv4\Subnet::fromCidr('172.16.1.0/24'),  // Engineering
    IPv4\Subnet::fromCidr('172.16.2.0/24'),  // Sales
    IPv4\Subnet::fromCidr('172.16.3.0/24'),  // Marketing
];

$allowRule = IPv4\Subnets::summarize($departments);
// Returns: 172.16.0.0/22
// One ACL entry instead of three (includes .0.0/24 which may be acceptable)

echo "Single ACL rule: permit {$allowRule->cidr()}\n";
echo "Covers {$allowRule->addressCount()} addresses\n";
```

#### Routing Table Optimization

```php
// Analyze routing table for aggregation opportunities
$routes = [
    IPv4\Subnet::fromCidr('10.0.0.0/24'),
    IPv4\Subnet::fromCidr('10.0.1.0/24'),
    IPv4\Subnet::fromCidr('10.0.2.0/24'),
    IPv4\Subnet::fromCidr('10.0.3.0/24'),
    IPv4\Subnet::fromCidr('172.16.0.0/24'),
    IPv4\Subnet::fromCidr('172.16.1.0/24'),
];

$optimized = IPv4\Subnets::aggregate($routes);

echo "Original routes: " . \count($routes) . "\n";
echo "Optimized routes: " . \count($optimized) . "\n";
echo "Reduction: " . (\count($routes) - \count($optimized)) . " routes\n\n";

echo "Optimized routing table:\n";
foreach ($optimized as $route) {
    echo "  {$route->cidr()}\n";
}
```

### Behavior Notes

#### Deduplication and Overlap Handling

Both methods automatically handle duplicates and overlapping subnets:

```php
$subnets = [
    IPv4\Subnet::fromCidr('192.168.0.0/23'),  // Larger subnet
    IPv4\Subnet::fromCidr('192.168.0.0/24'),  // Contained within /23
    IPv4\Subnet::fromCidr('192.168.0.0/24'),  // Duplicate
];

$aggregated = IPv4\Subnets::aggregate($subnets);
// Returns: [192.168.0.0/23] - duplicates removed, smaller subnet absorbed
```

#### Alignment Requirements

Subnets must be properly aligned to merge. Misaligned adjacent blocks cannot combine:

```php
$subnets = [
    IPv4\Subnet::fromCidr('10.0.1.0/24'),  // Starts at odd boundary
    IPv4\Subnet::fromCidr('10.0.2.0/24'),
];

$aggregated = IPv4\Subnets::aggregate($subnets);
// Returns: [10.0.1.0/24, 10.0.2.0/24]
// Cannot merge - 10.0.1.0 is not aligned for /23 (would need to start at 10.0.0.0)
```

#### Empty Input

```php
$result1 = IPv4\Subnets::aggregate([]);   // Returns: []
// $result2 = IPv4\Subnets::summarize([]);   // Throws: InvalidArgumentException
```

## Utilization Statistics

Analyze subnet efficiency and perform capacity planning. These methods help choose optimal subnet sizes and minimize IP address waste.

### Get Usable Host Percentage

Calculate what percentage of the subnet's total IP addresses are usable as hosts (accounting for network and broadcast address overhead):

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');

$percentage = $subnet->usableHostPercentage();  // 99.21875 (254 usable of 256 total)

echo 'Usable hosts: ' . \number_format($percentage, 2) . "%\n";  // Usable hosts: 99.22%

// Different subnet sizes have different efficiency
$sizes = [24, 25, 26, 27, 28, 29, 30];
foreach ($sizes as $size) {
    $s = IPv4\Subnet::fromCidr("192.168.1.0/{$size}");
    echo "/{$size}: " . \number_format($s->usableHostPercentage(), 2) . "% usable\n";
}
```

**RFC 3021 Special Cases - No Overhead:**

```php
// Point-to-point link (/31)
$p2p = IPv4\Subnet::fromCidr('10.0.0.0/31');
$p2p->usableHostPercentage();  // 100.0% (2 usable of 2 total)

// Single host (/32)
$single = IPv4\Subnet::fromCidr('10.0.0.1/32');
$single->usableHostPercentage();  // 100.0% (1 usable of 1 total)
```

### Count Unusable Addresses

Get the count of addresses that cannot be used as hosts (network address + broadcast address):

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');

$unusable = $subnet->unusableAddressCount();  // 2 (network + broadcast)

echo "Unusable addresses: {$unusable}\n";

// RFC 3021 special cases - no unusable addresses
$p2p = IPv4\Subnet::fromCidr('10.0.0.0/31');
echo "P2P unusable: {$p2p->unusableAddressCount()}\n";  // 0 (both addresses usable)

$single = IPv4\Subnet::fromCidr('10.0.0.1/32');
echo "Single host unusable: {$single->unusableAddressCount()}\n";  // 0
```

### Calculate Utilization for Host Requirements

Determine how efficiently a subnet would be utilized for a specific number of required hosts:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');  // 254 usable hosts

// Good fit - 78.74% utilization
$utilization = $subnet->utilizationFor(200);
echo "200 hosts in /24: " . \number_format($utilization, 2) . "% utilization\n";  // 78.74%

// Perfect fit - 100% utilization
$utilization = $subnet->utilizationFor(254);
echo "254 hosts in /24: " . \number_format($utilization, 2) . "% utilization\n";  // 100.00%

// Oversized subnet - wasting addresses
$utilization = $subnet->utilizationFor(50);
echo "50 hosts in /24: " . \number_format($utilization, 2) . "% utilization\n";   // 19.69% (inefficient)

// Insufficient capacity - more than 100%
$utilization = $subnet->utilizationFor(300);
echo "300 hosts in /24: " . \number_format($utilization, 2) . "% utilization\n";  // 118.11% (too small!)
```

### Calculate Wasted Addresses

Determine how many usable addresses would be wasted (or how many more are needed) for a specific host requirement:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');  // 254 usable hosts

// 54 addresses wasted
$wasted = $subnet->wastedAddressesFor(200);
echo "200 hosts in /24: {$wasted} addresses wasted\n";  // 54 (254 - 200)

// Perfect fit - no waste
$wasted = $subnet->wastedAddressesFor(254);
echo "254 hosts in /24: {$wasted} addresses wasted\n";  // 0

// Lots of wasted addresses
$wasted = $subnet->wastedAddressesFor(50);
echo "50 hosts in /24: {$wasted} addresses wasted\n";   // 204 (254 - 50)

// Insufficient capacity - negative value
$wasted = $subnet->wastedAddressesFor(300);
echo "300 hosts in /24: {$wasted} addresses wasted\n";  // -46 (need 46 more addresses!)
```

### Practical Use Case - Choosing Optimal Subnet Size

```php
// You need to allocate 100 hosts
$requiredHosts = 100;

// Compare different subnet sizes
$sizes = [24, 25, 26, 27];

echo "Finding optimal subnet size for {$requiredHosts} required hosts:\n\n";

$bestSubnet = null;
$bestUtilization = 0;

foreach ($sizes as $size) {
    $subnet = IPv4\Subnet::fromCidr("192.168.1.0/{$size}");
    $usableHosts = $subnet->hostCount();

    // Skip if subnet is too small
    if ($usableHosts < $requiredHosts) {
        echo "/{$size}: {$usableHosts} usable - TOO SMALL\n";
        continue;
    }

    $utilization = $subnet->utilizationFor($requiredHosts);
    $wasted = $subnet->wastedAddressesFor($requiredHosts);
    $usablePercent = $subnet->usableHostPercentage();

    echo "/{$size}: {$usableHosts} usable, " . \number_format($utilization, 2) . "% utilized, {$wasted} wasted, "
        . \number_format($usablePercent, 2) . "% efficiency\n";

    // Track best fit (highest utilization that meets requirements)
    if ($utilization > $bestUtilization && $utilization <= 100) {
        $bestUtilization = $utilization;
        $bestSubnet = $size;
    }
}

echo "\nRecommendation: /{$bestSubnet} provides the best fit (" . \number_format($bestUtilization, 2) . "% utilization)\n";

/*
Output:
Finding optimal subnet size for 100 required hosts:

/24: 254 usable, 39.37% utilized, 154 wasted, 99.22% efficiency
/25: 126 usable, 79.37% utilized, 26 wasted, 98.44% efficiency
/26: 62 usable - TOO SMALL
/27: 30 usable - TOO SMALL

Recommendation: /25 provides the best fit (79.37% utilization)
*/
```

### Network Planning Example

```php
// Department subnet planning
$departments = [
    'Engineering' => 150,
    'Sales'       => 50,
    'Marketing'   => 30,
    'HR'          => 20,
    'IT'          => 10,
];

echo "Department Subnet Planning:\n\n";

foreach ($departments as $dept => $requiredHosts) {
    // Find optimal prefix
    $optimalPrefix = IPv4\SubnetParser::optimalPrefixForHosts($requiredHosts);
    $subnet = IPv4\Subnet::fromCidr("10.0.0.0/{$optimalPrefix}");

    $utilization = $subnet->utilizationFor($requiredHosts);
    $wasted = $subnet->wastedAddressesFor($requiredHosts);
    $usable = $subnet->hostCount();

    echo "{$dept}:\n";
    echo "  Required: {$requiredHosts} hosts\n";
    echo "  Optimal: /{$optimalPrefix} ({$usable} usable)\n";
    echo "  Utilization: {$utilization}%\n";
    echo "  Wasted: {$wasted} addresses\n\n";
}
```

## Network Class Information (Legacy)

While classful networking is obsolete (RFC 4632 established CIDR), legacy network class information is still referenced in education, certifications, and some legacy systems.

### Get Network Class

Determine the legacy classful network class:

```php
$classA = IPv4\Subnet::fromCidr('10.0.0.0/8');
$classB = IPv4\Subnet::fromCidr('172.16.0.0/16');
$classC = IPv4\Subnet::fromCidr('192.168.1.0/24');
$classD = IPv4\Subnet::fromCidr('224.0.0.1/32');
$classE = IPv4\Subnet::fromCidr('240.0.0.0/32');

// networkClass() now returns IPv4\NetworkClass enum
echo "Class A: " . $classA->networkClass()->value . "\n";  // 'A'
echo "Class B: " . $classB->networkClass()->value . "\n";  // 'B'
echo "Class C: " . $classC->networkClass()->value . "\n";  // 'C'
echo "Class D: " . $classD->networkClass()->value . "\n";  // 'D' (Multicast)
echo "Class E: " . $classE->networkClass()->value . "\n";  // 'E' (Reserved)
```

**Working with the NetworkClass Enum:**

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');
$class = $subnet->networkClass();  // Returns IPv4\NetworkClass::C

// Get string value
echo $class->value;  // 'C'

// Type-safe comparison
if ($class === IPv4\NetworkClass::C) {
    echo "This is a Class C network\n";
}

// Use in match expressions
$range = match ($class) {
    IPv4\NetworkClass::A => '0.0.0.0 - 127.255.255.255',
    IPv4\NetworkClass::B => '128.0.0.0 - 191.255.255.255',
    IPv4\NetworkClass::C => '192.0.0.0 - 223.255.255.255',
    IPv4\NetworkClass::D => '224.0.0.0 - 239.255.255.255',
    IPv4\NetworkClass::E => '240.0.0.0 - 255.255.255.255',
};
```

### Get Default Classful Mask

Get the default subnet mask and prefix for the network's class:

```php
$subnet = IPv4\Subnet::fromCidr('10.0.0.0/24');

$defaultMask   = $subnet->defaultClassMask();    // '255.0.0.0' (Class A default)
$defaultPrefix = $subnet->defaultClassPrefix();  // 8 (Class A default /8)

echo "Network: {$subnet->cidr()}\n";
echo "Class: {$subnet->networkClass()->value}\n";  // Use ->value for string
echo "Default mask: {$defaultMask}\n";
echo "Default prefix: /{$defaultPrefix}\n";
```

### Check if Subnet Uses Classful Mask

Determine if a subnet is using its default classful boundary or if it's been subnetted/supernetted:

```php
$classfulA   = IPv4\Subnet::fromCidr('10.0.0.0/8');
$subnettedA  = IPv4\Subnet::fromCidr('10.0.0.0/24');
$classfulB   = IPv4\Subnet::fromCidr('172.16.0.0/16');
$supernettedB = IPv4\Subnet::fromCidr('172.16.0.0/12');

echo "10.0.0.0/8 is classful: "  . ($classfulA->isClassful() ? 'Yes' : 'No') . "\n";     // Yes
echo "10.0.0.0/24 is classful: " . ($subnettedA->isClassful() ? 'Yes' : 'No') . "\n";    // No (subnetted)
echo "172.16.0.0/16 is classful: " . ($classfulB->isClassful() ? 'Yes' : 'No') . "\n";   // Yes
echo "172.16.0.0/12 is classful: " . ($supernettedB->isClassful() ? 'Yes' : 'No') . "\n"; // No (supernetted)
```

### Class Definitions

| Class | First Octet | Default Mask | Default Prefix | Purpose |
|-------|-------------|--------------|----------------|---------|
| A | 0-127 | 255.0.0.0 | /8 | Large networks (includes 0.x.x.x and 127.x.x.x) |
| B | 128-191 | 255.255.0.0 | /16 | Medium networks |
| C | 192-223 | 255.255.255.0 | /24 | Small networks |
| D | 224-239 | N/A | N/A | Multicast |
| E | 240-255 | N/A | N/A | Reserved for future use |

### Practical Example - Educational Tool

```php
// Subnet analysis tool for learning classful networking
function analyzeSubnet($cidr) {
    $subnet = IPv4\Subnet::fromCidr($cidr);

    echo "Subnet: {$subnet->cidr()}\n";
    echo "Class: {$subnet->networkClass()->value}\n";  // Use ->value for string
    echo "Default classful mask: {$subnet->defaultClassMask()} (/{$subnet->defaultClassPrefix()})\n";
    echo "Actual mask: {$subnet->mask()} (/{$subnet->networkSize()})\n";

    if ($subnet->isClassful()) {
        echo "This subnet uses its natural classful boundary.\n";
    } else {
        $actualPrefix = $subnet->networkSize();
        $classfulPrefix = $subnet->defaultClassPrefix();

        if ($actualPrefix > $classfulPrefix) {
            $bits = $actualPrefix - $classfulPrefix;
            echo "This subnet has been subnetted using {$bits} additional bit(s).\n";
        } else {
            $bits = $classfulPrefix - $actualPrefix;
            echo "This subnet has been supernetted using {$bits} fewer bit(s).\n";
        }
    }

    echo "\n";
}

// Examples
analyzeSubnet('10.0.0.0/8');    // Classful Class A
analyzeSubnet('10.0.0.0/16');   // Subnetted Class A
analyzeSubnet('172.16.0.0/16'); // Classful Class B
analyzeSubnet('172.16.0.0/12'); // Supernetted Class B
```

## Next Steps

- **[Core Features](core-features.md)** - Network calculations, IP operations, overlap detection
- **[Reports](reports.md)** - Generate comprehensive network reports in multiple formats
- **[API Reference](api-reference.md)** - Complete method documentation
- **[Real-World Examples](examples.md)** - Practical patterns and use cases
