# API Reference - Version 5.0

Complete reference of all public methods and classes in the IPv4 Subnet Calculator library v5.0.

## Table of Contents
- [Main Classes](#main-classes)
- [Subnet Class](#subnet-class)
- [SubnetParser Class](#subnetparser-class)
- [Subnets Class](#subnets-class)
- [SubnetAllocator Class](#subnetallocator-class)
- [Value Object Classes](#value-object-classes)
  - [IPAddress](#ipaddress-class)
  - [SubnetMask](#subnetmask-class)
  - [WildcardMask](#wildcardmask-class)
  - [IPRange](#iprange-class)
  - [ReverseDnsDelegation](#reversednsdelegation-class)
  - [AllocationPlan](#allocationplan-class)
  - [Mask Interface](#mask-interface)
- [Enum Classes](#enum-classes)

---

## Conventions in Examples

Snippets assume the classes are imported (`use IPv4\Subnet;`, `use IPv4\IPAddress;`, `use IPv4\SubnetMask;`, and so on) and, unless a snippet defines its own, that `$subnet` is:

```php
$subnet = new Subnet('192.168.1.100', 24);
```

---

## Main Classes

### Overview

v5.0 introduces a clean object-oriented architecture:

- **`Subnet`** - Main class for subnet operations
- **`SubnetParser`** - Factory for creating subnets from various input formats
- **`Subnets`** - Collection operations on arrays of Subnet objects
- **`SubnetAllocator`** - Deterministic variable-size allocation within one parent network
- **`IPAddress`** - Immutable IP address value object
- **`SubnetMask`** - Immutable subnet mask value object
- **`WildcardMask`** - Immutable wildcard mask value object
- **`IPRange`** - Immutable IP range value object (iterable)
- **`ReverseDnsDelegation`** - Immutable RFC 2317 classless reverse-DNS delegation data
- **`AllocationPlan`** - Immutable result of a subnet allocation

`IPAddress`, `SubnetMask`, `WildcardMask` and `IPRange` implement the `Stringable` interface. `IPAddress`, `SubnetMask` and `WildcardMask` also provide format methods (`asQuads()`, `asHex()`, etc.); `SubnetMask` and `WildcardMask` share them through the [`Mask`](#mask-interface) interface.

---

## Subnet Class

`IPv4\Subnet` - Main entry point for subnet calculations. Immutable, provides access to all subnet information through value objects.

### Construction

#### __construct()
```php
new Subnet(string|IPAddress $ipAddress, int $networkSize): Subnet
```
Create a subnet from IP address and prefix length.

**Parameters:**
- `$ipAddress` - IP address in dotted decimal format, or an `IPAddress`
- `$networkSize` - CIDR prefix (0-32)

**Example:**
```php
$subnet = new Subnet('192.168.1.100', 24);
$subnet = new Subnet(new IPAddress('192.168.1.100'), 24);
```

#### fromCidr()
```php
Subnet::fromCidr(string $cidr): Subnet
```
Create a subnet from CIDR notation.

**Parameters:**
- `$cidr` - CIDR notation (e.g., '192.168.1.0/24')

**Example:**
```php
$subnet = Subnet::fromCidr('192.168.1.100/24');
```

#### fromNetworkCidr()
```php
Subnet::fromNetworkCidr(string $cidr): Subnet
```
Create a subnet only when the input IP is its network address. Uses the same syntax validation and diagnostics as `fromCidr()`, then throws `\InvalidArgumentException` if host bits are set. Construction and `fromCidr()` continue to accept and retain host addresses.

For `/0`, only `0.0.0.0/0` is aligned. For `/31`, the input must be even; every valid `/32` address is aligned. Runs in O(1) time and additional space.

**Example:**
```php
$network = Subnet::fromNetworkCidr('192.168.1.0/24');
echo $network->cidr(); // 192.168.1.0/24

// Subnet::fromNetworkCidr('192.168.1.50/24') throws \InvalidArgumentException.
```

### Core Identity

#### ipAddress()
```php
ipAddress(): IPAddress
```
Returns the input IP address as an IPAddress value object.

**Example:**
```php
$ip = $subnet->ipAddress();
echo $ip;                  // "192.168.1.100"
echo $ip->asHex();         // "C0A80164"
```

#### isNetworkAddress()
```php
isNetworkAddress(): bool
```
Checks whether the original input IP equals this subnet's network address. It does not validate an arbitrary string or change the stored input. Runs in O(1) time and additional space.

**Example:**
```php
$host = Subnet::fromCidr('192.168.1.50/24');
\var_dump($host->isNetworkAddress()); // false
\var_dump(Subnet::fromNetworkCidr('192.168.1.0/24')->isNetworkAddress()); // true
```

#### networkSize()
```php
networkSize(): int
```
Returns the CIDR prefix length (0-32).

**Example:**
```php
$size = $subnet->networkSize();  // 24
```

#### cidr()
```php
cidr(): string
```
Returns CIDR notation string with the input IP address.

**Note:** Returns the input IP with prefix. For canonical form suitable for string comparisons, use `networkCidr()` instead.

**Example:**
```php
$subnet = Subnet::fromCidr('192.168.1.100/24');
$cidr = $subnet->cidr();  // "192.168.1.100/24"
```

#### networkCidr()
```php
networkCidr(): string
```
Returns canonical CIDR notation using the network address.

**Use this for:**
- String comparisons between subnets
- Serialization and storage
- Scenarios where equal subnets must produce identical strings

**Example:**
```php
$subnet1 = Subnet::fromCidr('192.168.1.100/24');
$subnet2 = Subnet::fromCidr('192.168.1.200/24');

$subnet1->cidr();        // "192.168.1.100/24"
$subnet2->cidr();        // "192.168.1.200/24"

$subnet1->networkCidr(); // "192.168.1.0/24"
$subnet2->networkCidr(); // "192.168.1.0/24"
```

### Network Addresses

#### networkAddress()
```php
networkAddress(): IPAddress
```
Returns the network address (first IP in subnet).

**Example:**
```php
$network = $subnet->networkAddress();  // IPAddress: 192.168.1.0
echo $network->asQuads();              // "192.168.1.0"
```

#### broadcastAddress()
```php
broadcastAddress(): IPAddress
```
Returns the broadcast address (last IP in subnet).

**Example:**
```php
$broadcast = $subnet->broadcastAddress();  // IPAddress: 192.168.1.255
```

#### minHost()
```php
minHost(): IPAddress
```
Returns the first usable host address.

**Example:**
```php
$min = $subnet->minHost();  // IPAddress: 192.168.1.1
```

#### maxHost()
```php
maxHost(): IPAddress
```
Returns the last usable host address.

**Example:**
```php
$max = $subnet->maxHost();  // IPAddress: 192.168.1.254
```

### Masks

#### mask()
```php
mask(): SubnetMask
```
Returns the subnet mask as a SubnetMask value object.

**Example:**
```php
$mask = $subnet->mask();
echo $mask;                  // "255.255.255.0"
echo $mask->asBinary();      // "11111111111111111111111100000000"
```

#### wildcardMask()
```php
wildcardMask(): WildcardMask
```
Returns the wildcard mask (inverse of subnet mask) for use in Cisco ACLs.

**Example:**
```php
$wildcard = $subnet->wildcardMask();  // WildcardMask: 0.0.0.255
```

### Network Portions

#### networkPortion()
```php
networkPortion(): IPAddress
```
Returns the network portion of the input IP address.

**Example:**
```php
$network = $subnet->networkPortion();  // IPAddress: 192.168.1.0
```

#### hostPortion()
```php
hostPortion(): IPAddress
```
Returns the host portion of the input IP address.

**Example:**
```php
$host = $subnet->hostPortion();  // IPAddress: 0.0.0.100
```

### Ranges

#### addressRange()
```php
addressRange(): IPRange
```
Returns all IPs in the subnet (including network and broadcast) as an iterable IPRange.

**Example:**
```php
$range = $subnet->addressRange();
foreach ($range as $ip) {
    echo $ip . "\n";  // Iterate through all 256 IPs
}
```

#### hostRange()
```php
hostRange(): IPRange
```
Returns usable host IPs (excludes network and broadcast) as an iterable IPRange.

**Note:** For /31 networks (RFC 3021 point-to-point) and /32 networks (single host), all addresses are returned since these special-purpose networks don't reserve network/broadcast addresses.

**Example:**
```php
$range = $subnet->hostRange();
foreach ($range as $ip) {
    echo $ip . "\n";  // Iterate through 254 usable hosts
}
```

### Counts

#### addressCount()
```php
addressCount(): int
```
Returns total number of IP addresses in the subnet.

**Example:**
```php
$total = $subnet->addressCount();  // 256
```

#### hostCount()
```php
hostCount(): int
```
Returns number of usable host addresses.

**Example:**
```php
$hosts = $subnet->hostCount();  // 254
```

### Classification

All classification methods delegate to the `ipAddress()` object, so they classify the input IP only, not the whole block.
A block that straddles a range boundary is classified by whichever side its input IP is on: `10.0.0.0/7` is private, but it also
spans the public `11.0.0.0/8`. To check a whole block, test it against the range with `isContainedIn()` or `overlaps()`.

#### isPrivate()
```php
isPrivate(): bool
```
Check if the IP is in private address space (RFC 1918).

#### isPublic()
```php
isPublic(): bool
```
Check if the IP is public: not in any of the [special-purpose ranges recognized by this library](core-features.md#supported-range-types-reference). Stricter than global reachability: a few special-purpose blocks are globally reachable but are not counted as public; see `isGloballyReachable()`.

#### isLoopback()
```php
isLoopback(): bool
```
Check if the IP is in loopback range (127.0.0.0/8).

#### isLinkLocal()
```php
isLinkLocal(): bool
```
Check if the IP is link-local (169.254.0.0/16).

#### isMulticast()
```php
isMulticast(): bool
```
Check if the IP is multicast (224.0.0.0/4).

#### isCarrierGradeNat()
```php
isCarrierGradeNat(): bool
```
Check if the IP is in carrier-grade NAT space (100.64.0.0/10).

#### isDocumentation()
```php
isDocumentation(): bool
```
Check if the IP is reserved for documentation (RFC 5737).

#### isBenchmarking()
```php
isBenchmarking(): bool
```
Check if the IP is reserved for benchmarking (198.18.0.0/15).

#### isIetfProtocol()
```php
isIetfProtocol(): bool
```
Check if the IP is reserved for IETF protocol assignments (192.0.0.0/24).

Used for protocols like DS-Lite, NAT64, and other IETF-assigned uses.

**Links:** RFC 5735, RFC 6890

#### is6to4Relay()
```php
is6to4Relay(): bool
```
Check if the IP is in the deprecated 6to4 relay anycast range (192.88.99.0/24).

This range is deprecated as of RFC 7526. The 6a44 relay anycast address 192.88.99.2 is excluded; it is classified by `isIanaReserved()`.

**Links:** RFC 3068, RFC 7526

#### isIanaReserved()
```php
isIanaReserved(): bool
```
Check if the IP is in an IANA Special-Purpose Address Registry range.

Covers three /24 blocks: 192.31.196.0/24 (AS112-v4), 192.52.193.0/24 (AMT), 192.175.48.0/24 (Direct Delegation AS112), plus the 6a44 relay anycast address 192.88.99.2/32.

**Links:** RFC 7535, RFC 7450, RFC 7534, RFC 6751

#### isReserved()
```php
isReserved(): bool
```
Check if the IP is in reserved address space (240.0.0.0/4).

#### isLimitedBroadcast()
```php
isLimitedBroadcast(): bool
```
Check if the IP is the limited broadcast address (255.255.255.255).

#### isThisNetwork()
```php
isThisNetwork(): bool
```
Check if the IP is in "this network" range (0.0.0.0/8).

#### isGloballyReachable()
```php
isGloballyReachable(): bool
```
Check if the IP is eligible for global unicast forwarding under the "Globally Reachable" column of the [IANA IPv4 Special-Purpose Address Registry](https://www.iana.org/assignments/iana-ipv4-special-registry/), using the most specific matching entry. Addresses outside the registry return true, except multicast (224.0.0.0/4), which returns false. A blank registry field, as on the deprecated 6to4 relay block 192.88.99.0/24, returns false: false includes "not established by this policy". This is address policy, not evidence of allocation, a route or live connectivity. `isPublic()` and `addressType()` are unaffected. Runs in O(1) time and additional space.

**Example:**
```php
Subnet::fromCidr('192.0.0.9/32')->isGloballyReachable();   // true - PCP anycast
Subnet::fromCidr('192.0.0.9/32')->isPublic();              // false - still IETF protocol space
Subnet::fromCidr('192.0.0.8/32')->isGloballyReachable();   // false - IPv4 dummy address
Subnet::fromCidr('192.88.99.2/32')->isGloballyReachable(); // false - 6a44 relay is not reachable
```

**Links:** [Global Reachability](advanced-features.md#global-reachability)

#### addressType()
```php
addressType(): AddressType
```
Returns the address type as an AddressType enum.

**Example:**
```php
$type = $subnet->addressType();  // AddressType::Private
```

### Network Class (Legacy)

#### networkClass()
```php
networkClass(): NetworkClass
```
Returns the classful network class (A, B, C, D, E).

#### defaultClassMask()
```php
defaultClassMask(): ?string
```
Returns the default subnet mask for the network class, or null for D/E.

#### defaultClassPrefix()
```php
defaultClassPrefix(): ?int
```
Returns the default prefix for the network class, or null for D/E.

#### isClassful()
```php
isClassful(): bool
```
Check if the subnet uses its default classful mask.

### Utilization

#### usableHostPercentage()
```php
usableHostPercentage(): float
```
Returns percentage of usable addresses (host count / total count).

#### unusableAddressCount()
```php
unusableAddressCount(): int
```
Returns number of unusable addresses (network + broadcast, except /31 and /32).

#### utilizationFor()
```php
utilizationFor(int $requiredHosts): float
```
Calculate utilization percentage for a given host requirement.

Throws `\InvalidArgumentException` if `$requiredHosts` is negative.

**Example:**
```php
$utilization = $subnet->utilizationFor(100);  // 39.37% for /24
```

#### wastedAddressesFor()
```php
wastedAddressesFor(int $requiredHosts): int
```
Calculate wasted addresses for a given host requirement.

Throws `\InvalidArgumentException` if `$requiredHosts` is negative.

**Example:**
```php
$wasted = $subnet->wastedAddressesFor(100);  // 154 for /24
```

### Operations

#### split()
```php
split(int $newPrefix): Subnet[]
```
Split subnet into smaller subnets with the specified prefix.

Throws `\InvalidArgumentException` if `$newPrefix` is invalid.

**Example:**
```php
$smaller = $subnet->split(26);  // Split /24 into four /26 subnets
```

#### splitIterator()
```php
splitIterator(int $newPrefix): \Generator<int, Subnet>
```
Lazy version of `split()` that yields one subnet at a time. Use for large splits. Throws on call for an invalid prefix.

#### subnetAt()
```php
subnetAt(int $newPrefix, int $index): Subnet
```
Returns the child at a zero-based index in ascending network order, using O(1) time and additional space. The result is canonical even when the parent's input IP has host bits set.

The new prefix must be strictly greater than the parent prefix and at most 32, matching `splitIterator()`. A `/32` has no child subnets. Invalid prefixes throw `\InvalidArgumentException` **before** index validation. Indices must be from `0` to `2^($newPrefix - $parentPrefix) - 1`; negative or out-of-range indices throw `\OutOfRangeException`.

**Examples:**
```php
$parent = Subnet::fromCidr('192.0.2.200/24');
echo $parent->subnetAt(26, 0)->cidr(); // 192.0.2.0/26
echo $parent->subnetAt(26, 3)->cidr(); // 192.0.2.192/26

echo Subnet::fromCidr('0.0.0.0/0')->subnetAt(32, 4_294_967_295)->cidr();
// 255.255.255.255/32, without traversing preceding children
```

#### supernet()
```php
supernet(int $prefix): Subnet
```
Returns the enclosing block at a shorter or equal prefix. The result is canonical (its input IP is the network address) even when this subnet's input IP has host bits set, and it always contains this subnet.

The prefix must be from `0` to the current prefix. A prefix equal to the current one returns the canonical form of this block, so generic code such as `supernet(\min(16, $subnet->networkSize()))` works for any subnet. Any other prefix throws `\InvalidArgumentException` with the message `Supernet prefix must be between 0 and {current prefix}, got {prefix}`.

**Examples:**
```php
$subnet = Subnet::fromCidr('192.168.1.100/24');
echo $subnet->supernet(16)->cidr(); // 192.168.0.0/16
echo $subnet->supernet(24)->cidr(); // 192.168.1.0/24
echo $subnet->supernet(0)->cidr();  // 0.0.0.0/0

echo Subnet::fromCidr('10.0.0.5/32')->supernet(31)->cidr(); // 10.0.0.4/31

$subnet->supernet(25); // throws \InvalidArgumentException
```

#### contains()
```php
contains(Subnet $other): bool
```
Check if this subnet contains another subnet.

#### isContainedIn()
```php
isContainedIn(Subnet $other): bool
```
Check if this subnet is contained within another subnet.

#### overlaps()
```php
overlaps(Subnet $other): bool
```
Check if this subnet overlaps with another subnet.

#### isAdjacent()
```php
isAdjacent(Subnet $other): bool
```
Returns whether two disjoint full address ranges touch, in either order. Includes network and broadcast addresses, ignores input host bits, and permits unequal prefixes. Adjacency does not imply that blocks can aggregate; even same-prefix neighbors must be aligned siblings to merge. Gaps, overlap, containment and equality return false. Runs in O(1) time and additional space.

**Example:**
```php
$left = Subnet::fromCidr('10.0.1.50/24');
$right = Subnet::fromCidr('10.0.2.20/24');
\var_dump($left->isAdjacent($right)); // true; these /24s cannot merge
\var_dump($right->isAdjacent($left)); // true
\var_dump($left->isAdjacent(Subnet::fromCidr('10.0.1.128/25'))); // false; contained
\var_dump(Subnet::fromCidr('255.255.255.255/32')->isAdjacent(Subnet::fromCidr('0.0.0.0/32'))); // false
```

`/0` has no adjacent block. Consecutive `/31` or `/32` blocks can be adjacent; the maximum IPv4 address never wraps to zero. See [finding adjacent blocks](examples.md#find-adjacent-network-blocks).

#### intersection()
```php
intersection(Subnet $other): ?Subnet
```
Returns `null` for disjoint blocks, including adjacent ones. Overlapping CIDR blocks are nested, so their intersection is the narrower block. The result always has a canonical input IP, including when equal networks were constructed with host bits. The result is symmetric by value. Runs in O(1) time and additional space, including `/0`, `/31` and `/32` at either IPv4 boundary.

**Example:**
```php
$left = Subnet::fromCidr('192.168.1.50/24');
$right = Subnet::fromCidr('192.168.1.200/24');
$shared = $left->intersection($right);
echo $shared?->cidr() . "\n"; // 192.168.1.0/24
echo $shared?->ipAddress()->asQuads() . "\n"; // 192.168.1.0
\var_dump($left->intersection(Subnet::fromCidr('192.168.2.0/24'))); // null
```

See [restricting a block to a scope](examples.md#restrict-a-network-block-to-a-scope).

#### exclude()
```php
exclude(Subnet $exclude): Subnet[]
```
Exclude a subnet, returning the remaining address space.

**Example:**
```php
$remaining = $allocated->exclude($reserved);
```

#### excludeAll()
```php
excludeAll(array $excludes): Subnet[]
```
Exclude multiple subnets, returning the remaining address space. With an empty array, returns
this subnet normalized to its network address.

Throws `\InvalidArgumentException` if any element is not a `Subnet`.

### Navigation

#### next()
```php
next(): Subnet
```
Get the next adjacent subnet of the same size.

Throws `\RuntimeException` if the next subnet exceeds the IPv4 range.

**Example:**
```php
$next = $subnet->next();  // 192.168.2.0/24
```

#### previous()
```php
previous(): Subnet
```
Get the previous adjacent subnet of the same size.

Throws `\RuntimeException` if the previous subnet is below 0.0.0.0.

#### adjacent()
```php
adjacent(int $count): Subnet[]
```
Get adjacent subnets. Positive count for forward, negative for backward.

Throws `\RuntimeException` if the requested subnets exceed the IPv4 range.

**Example:**
```php
$forward = $subnet->adjacent(3);   // Next 3 subnets
$backward = $subnet->adjacent(-3); // Previous 3 subnets
```

#### adjacentIterator()
```php
adjacentIterator(int $count): \Generator<int, Subnet>
```
Lazy version of `adjacent()` that yields one subnet at a time. Use for large counts. Throws on call if the requested subnets exceed the IPv4 range.

### Membership

#### containsIP()
```php
containsIP(string|IPAddress $ip): bool
```
Check if an IP address is within this subnet.
Throws `\InvalidArgumentException` if `$ip` is a string that is not a valid IPv4 address.

**Example:**
```php
$inSubnet = $subnet->containsIP('192.168.1.100');  // true
```

### Comparison

#### equals()
```php
equals(Subnet $other): bool
```
Check if two subnets are equal (same network and prefix).

#### compare()
```php
compare(Subnet $other): int
```
Returns exactly `-1`, `0` or `1`, ordered by ascending numeric network address, then ascending prefix. At an equal start address, broader networks come first. Input host bits do not affect ordering; zero agrees with `equals()`. Runs in O(1) time and additional space.

**Example:**
```php
\var_dump(Subnet::fromCidr('2.0.0.0/8')->compare(Subnet::fromCidr('10.0.0.0/8'))); // -1
\var_dump(Subnet::fromCidr('10.0.0.0/8')->compare(Subnet::fromCidr('10.0.0.0/16'))); // -1
\var_dump(Subnet::fromCidr('10.255.255.255/8')->compare(Subnet::fromCidr('10.0.0.0/8'))); // 0
```

Use `\usort($subnets, static fn (Subnet $a, Subnet $b): int => $a->compare($b))` to sort subnet objects. See the [sorting example](examples.md#sort-network-blocks) for canonical output.

### Output

#### arpaDomain()
```php
arpaDomain(): string
```
Get the reverse DNS ARPA domain for the input IP address.

**Example:**
```php
$subnet = Subnet::fromCidr('192.168.1.100/24');
$arpa   = $subnet->arpaDomain();  // "100.1.168.192.in-addr.arpa"
```

#### reverseZones()
```php
reverseZones(): array // list<string>
```
Get the reverse DNS zone origins that exactly cover the network: the smallest set of octet-aligned `in-addr.arpa` zones, in ascending network order, without trailing dots. A prefix off an octet boundary splits to the next one, so a `/20` yields sixteen `/24` origins and a `/25`–`/31` yields one `/32` origin per address, never the enclosing `/24`. `/0` yields `in-addr.arpa`. Input host bits are ignored. The names are calculated origins, not a claim that the caller controls those zones. Runs in time and memory proportional to the number of origins, at most 128.

**Example:**
```php
Subnet::fromCidr('192.168.1.50/24')->reverseZones(); // ["1.168.192.in-addr.arpa"]
Subnet::fromCidr('172.16.0.0/15')->reverseZones();   // ["16.172.in-addr.arpa", "17.172.in-addr.arpa"]
Subnet::fromCidr('192.0.2.6/31')->reverseZones();    // ["6.2.0.192.in-addr.arpa", "7.2.0.192.in-addr.arpa"]
```

#### classlessDelegation()
```php
classlessDelegation(?string $childLabel = null): ReverseDnsDelegation
```
Get [RFC 2317](https://www.rfc-editor.org/rfc/rfc2317.html) classless reverse-DNS delegation data for a `/25`–`/32` network: the enclosing `/24` parent zone, the child zone origin, and one parent-zone CNAME per address. The child label defaults to `startLastOctet-endLastOctet`, such as `64-127`. A custom label must be one nonnumeric label of 1–63 ASCII letters, digits or hyphens, beginning and ending with a letter or digit; numeric-only labels are rejected because they could collide with PTR owner names. Input host bits are ignored. Throws `\InvalidArgumentException` for `/0`–`/24`, checked first, and for an invalid label. Runs in time and memory proportional to the address count, at most 128 records.

**Example:**
```php
$delegation = Subnet::fromCidr('192.0.2.64/26')->classlessDelegation();
$delegation->parentZone(); // "2.0.192.in-addr.arpa"
$delegation->childZone();  // "64-127.2.0.192.in-addr.arpa"
$delegation->cnameRecords()['65.2.0.192.in-addr.arpa']; // "65.64-127.2.0.192.in-addr.arpa"

Subnet::fromCidr('192.0.2.64/26')->classlessDelegation('cust1')->childZone(); // "cust1.2.0.192.in-addr.arpa"
```

#### toArray()
```php
toArray(): array
```
Get subnet information as an associative array.

#### toJson()
```php
toJson(): string
```
Get subnet information as JSON string.

#### toPrintable()
```php
toPrintable(): string
```
Get subnet information as a formatted text report.

#### __toString()
```php
__toString(): string
```
String representation in canonical CIDR notation using the network address, same as `networkCidr()` (e.g., "192.168.1.0/24"). Equal subnets have identical string forms. Use `cidr()` for the input IP with prefix, and `toPrintable()` for detailed formatted reports.

**Example:**
```php
echo $subnet;  // "192.168.1.0/24"
echo $subnet->toPrintable();  // Full formatted report
```

#### jsonSerialize()
```php
jsonSerialize(): array
```
Implement JsonSerializable interface.

---

## SubnetParser Class

`IPv4\SubnetParser` - Static factory methods for creating Subnet objects from various input formats.

### Creation Methods

#### fromMask()
```php
SubnetParser::fromMask(string|IPAddress $ipAddress, string|SubnetMask $subnetMask): Subnet
```
Create subnet from an IP address and subnet mask. Each argument may be a string or an `IPAddress`/`SubnetMask` object.

Throws `\InvalidArgumentException` if the subnet mask is invalid or non-contiguous.

**Example:**
```php
$subnet = SubnetParser::fromMask('192.168.1.0', '255.255.255.0');
```

#### fromRange()
```php
SubnetParser::fromRange(string|IPAddress $startIp, string|IPAddress $endIp): Subnet
```
Create subnet from IP range (must be valid CIDR block). Each endpoint may be a string or an `IPAddress`.

Throws `\InvalidArgumentException` if the range does not represent a valid CIDR block.

For an arbitrary inclusive range that may require several blocks, use [`IPRange::toSubnets()`](#tosubnets).

**Example:**
```php
$subnet = SubnetParser::fromRange('192.168.1.0', '192.168.1.255');
```

#### fromHostCount()
```php
SubnetParser::fromHostCount(string|IPAddress $ipAddress, int $hostCount): Subnet
```
Create smallest subnet that accommodates the required host count. The address may be a string or an `IPAddress`.

Throws `\InvalidArgumentException` if the host count is invalid.

**Example:**
```php
$subnet = SubnetParser::fromHostCount('192.168.1.0', 100);  // Creates /25
```

### Utility

#### optimalPrefixForHosts()
```php
SubnetParser::optimalPrefixForHosts(int $hostCount): int
```
Calculate optimal CIDR prefix for a given host count.

Throws `\InvalidArgumentException` if the host count is invalid.

**Example:**
```php
$prefix = SubnetParser::optimalPrefixForHosts(100);  // Returns 25
```

---

## Subnets Class

`IPv4\Subnets` - Static methods for collection operations on arrays of Subnet objects.

### Collection Operations

#### aggregate()
```php
Subnets::aggregate(array $subnets): Subnet[]
```
Combine contiguous subnets into minimal set of larger blocks.

Throws `\InvalidArgumentException` if any element is not a `Subnet`.

**Parameters:**
- `$subnets` - Array of Subnet objects

**Returns:** Array of Subnet objects

**Example:**
```php
$aggregated = Subnets::aggregate([
    Subnet::fromCidr('192.168.0.0/24'),
    Subnet::fromCidr('192.168.1.0/24'),
]);
// Returns: [Subnet: 192.168.0.0/23]
```

#### summarize()
```php
Subnets::summarize(array $subnets): Subnet
```
Find smallest single CIDR block containing all input subnets.

Throws `\InvalidArgumentException` if the array is empty or any element is not a `Subnet`.

**Parameters:**
- `$subnets` - Array of Subnet objects

**Returns:** Single Subnet object

**Example:**
```php
$summary = Subnets::summarize([
    Subnet::fromCidr('192.168.0.0/24'),
    Subnet::fromCidr('192.168.1.0/24'),
]);
// Returns: Subnet: 192.168.0.0/23
```

#### longestPrefixMatch()
```php
Subnets::longestPrefixMatch(array $subnets, string|IPAddress $ip): ?Subnet
```
Find the most specific subnet containing an IP address: of the subnets whose range includes `$ip`, the one with the largest prefix (`networkSize()`). Host bits in the inputs are ignored, so `0.0.0.0/0` acts as a default route. When several containing subnets share the largest prefix, the first in iteration order wins.

The matching element itself is returned, not a canonical copy, so `\array_search($match, $subnets, true)` recovers its array key. This is a linear O(n) scan, not a routing trie.

Throws `\InvalidArgumentException` if any element is not a `Subnet` or the IP address is invalid.

**Parameters:**
- `$subnets` - Array of Subnet objects, with any keys
- `$ip` - IP address to match (string or IPAddress)

**Returns:** The most specific containing Subnet from `$subnets`, or `null` if the array is empty or no subnet contains the address

**Example:**
```php
$routes = [
    Subnet::fromCidr('0.0.0.0/0'),
    Subnet::fromCidr('10.0.0.0/8'),
    Subnet::fromCidr('10.1.0.0/16'),
];
Subnets::longestPrefixMatch($routes, '10.1.2.3');   // Subnet: 10.1.0.0/16
Subnets::longestPrefixMatch($routes, '10.200.0.1'); // Subnet: 10.0.0.0/8
Subnets::longestPrefixMatch($routes, '8.8.8.8');    // Subnet: 0.0.0.0/0
Subnets::longestPrefixMatch([], '8.8.8.8');         // null
```

### Set Operations

These methods treat each array as the set of addresses its subnets cover, including network and broadcast addresses. Inputs may contain duplicate, nested, unsorted or adjacent subnets, with any array keys; host bits are ignored. Every element of both arrays is validated before any result is computed, so an invalid element throws `\InvalidArgumentException` even when the other array is empty. Inputs are sorted and merged into address intervals in O(n log n + m log m) time, then swept once; no addresses are enumerated. Results take additional time and space proportional to the number of returned blocks.

#### intersection()
```php
Subnets::intersection(array $left, array $right): Subnet[]
```
Returns the addresses in both arrays as the fewest canonical CIDR blocks, in ascending order, with numeric keys. Commutative; `intersection($a, $a)` equals `aggregate($a)`.

**Example:**
```php
$shared = Subnets::intersection(
    [Subnet::fromCidr('10.0.0.0/23'), Subnet::fromCidr('10.0.4.0/24')],
    [Subnet::fromCidr('10.0.1.0/24'), Subnet::fromCidr('10.0.4.128/25')],
);
// Returns: [Subnet: 10.0.1.0/24, Subnet: 10.0.4.128/25]
```

#### difference()
```php
Subnets::difference(array $left, array $right): Subnet[]
```
Returns the addresses in `$left` that are not in `$right` as the fewest canonical CIDR blocks, in ascending order, with numeric keys. Not commutative. Together, `difference($a, $b)` and `intersection($a, $b)` cover exactly the addresses of `$a`. For a single base subnet, `Subnet::excludeAll()` gives the same blocks.

**Example:**
```php
$free = Subnets::difference(
    [Subnet::fromCidr('10.0.0.0/23'), Subnet::fromCidr('10.0.4.0/24')],
    [Subnet::fromCidr('10.0.0.0/24'), Subnet::fromCidr('10.0.1.128/26'), Subnet::fromCidr('10.0.4.0/25')],
);
// Returns: [Subnet: 10.0.1.0/25, Subnet: 10.0.1.192/26, Subnet: 10.0.4.128/25]
```

#### covers()
```php
Subnets::covers(array $covering, array $required): bool
```
Returns whether every address in `$required` is in `$covering`, equivalent to `difference($required, $covering) === []`. Several smaller blocks may together cover a larger one. Every array covers `[]`; `[]` covers no nonempty array.

**Example:**
```php
$halves = [Subnet::fromCidr('192.0.2.0/25'), Subnet::fromCidr('192.0.2.128/25')];
Subnets::covers($halves, [Subnet::fromCidr('192.0.2.0/24')]); // true
Subnets::covers([], [Subnet::fromCidr('192.0.2.1/32')]);       // false
```

Union needs no separate method: use `Subnets::aggregate([...\array_values($left), ...\array_values($right)])`, so duplicate string keys cannot overwrite an input block.

---

## SubnetAllocator Class

`IPv4\SubnetAllocator` - Stateless, deterministic variable-size (VLSM) allocation within one parent network.

#### allocate()
```php
SubnetAllocator::allocate(Subnet $parent, array $requirements, array $reserved = []): AllocationPlan
```
Allocate one CIDR block per named requirement from `$parent`, avoiding `$reserved`, and return an [`AllocationPlan`](#allocationplan-class).

Each host count is sized as by [`optimalPrefixForHosts()`](#optimalprefixforhosts): `1` gives a `/32`, `2` a `/31`, and larger counts include network and broadcast addresses. Larger blocks are placed first, equal sizes in bytewise name order (`strcmp()`); each takes the lowest aligned free address where it fits. The parent's host bits are ignored. Reservations may be duplicated, nested, overlapping or keyed, but must each lie wholly inside the parent. With no requirements, the plan is the parent minus the reservations; reserving the entire parent is then valid.

Requirement names are array keys: PHP keeps only the last duplicate key, so reject duplicates while building the map, and it converts numeric strings such as `'10'` to integers, which are rejected.

All input is validated before anything is placed. Throws `\InvalidArgumentException` if a name is not a nonempty string, a host count is not an `int` accepted by `optimalPrefixForHosts()`, or a reservation is not a `Subnet` contained in the parent. Throws `\RuntimeException` naming the first requirement, in placement order, that no free block can hold, with its prefix, the largest free block and the free address count; fragmentation can cause this even when enough addresses are free in total. No partial plan is returned. Runs in time proportional to the number of requirements times the number of free blocks, plus sorting, independent of the parent's address count; no addresses are enumerated.

**Parameters:**
- `$parent` - Network to allocate from
- `$requirements` - `array<string, int>`: name => required usable hosts
- `$reserved` - Array of Subnet objects to keep free of allocations

**Returns:** `AllocationPlan`

**Example:**
```php
$plan = SubnetAllocator::allocate(
    Subnet::fromCidr('10.0.0.0/22'),
    ['Branch' => 50, 'HQ' => 500],
    [Subnet::fromCidr('10.0.3.0/24')],
);
$plan->allocations(); // ['Branch' => Subnet: 10.0.2.0/26, 'HQ' => Subnet: 10.0.0.0/23]
$plan->reserved();    // [Subnet: 10.0.3.0/24]
$plan->remaining();   // [Subnet: 10.0.2.64/26, Subnet: 10.0.2.128/25]

SubnetAllocator::allocate(Subnet::fromCidr('10.0.0.0/24'), ['a' => 200, 'b' => 100]);
// Throws \RuntimeException: Cannot allocate 'b' as a /25: no free space remains
```

#### allocateByPrefix()
```php
SubnetAllocator::allocateByPrefix(Subnet $parent, array $prefixes, array $reserved = []): AllocationPlan
```
Allocate one CIDR block per named prefix from `$parent`, avoiding `$reserved`, and return an [`AllocationPlan`](#allocationplan-class).

Use this when a block's size is known as a prefix rather than a host count. Host-count sizing maps `2` hosts to a `/31` and `3` to a `/29`, so a `/30` link can only be requested by prefix. To mix both sizings, convert host counts with [`optimalPrefixForHosts()`](#optimalprefixforhosts): `['lan' => SubnetParser::optimalPrefixForHosts(50), 'wan' => 30]`.

Placement, ordering, reservations and names behave exactly as in [`allocate()`](#allocate): larger blocks (smaller prefixes) first, equal prefixes in bytewise name order, each at the lowest aligned free address; allocations are returned in input order. For equivalent input, `allocate($parent, $hosts, $reserved)` and `allocateByPrefix($parent, array_map(SubnetParser::optimalPrefixForHosts(...), $hosts), $reserved)` return the same plan.

All input is validated before anything is placed. Throws `\InvalidArgumentException` if a name is not a nonempty string, a prefix is not an `int` (`Requirement 'wan' prefix must be an int, got string`) or lies outside 0–32 (`Requirement 'wan' prefix must be between 0 and 32, got 33`), or a reservation is not a `Subnet` contained in the parent. A prefix shorter than the parent's is valid input that cannot fit: like any block that no free space can hold, it throws `\RuntimeException` naming the first such block in placement order. No partial plan is returned.

**Parameters:**
- `$parent` - Network to allocate from
- `$prefixes` - `array<string, int>`: name => CIDR prefix (0–32)
- `$reserved` - Array of Subnet objects to keep free of allocations

**Returns:** `AllocationPlan`

**Example:**
```php
$plan = SubnetAllocator::allocateByPrefix(
    Subnet::fromCidr('192.168.0.0/24'),
    ['lan' => SubnetParser::optimalPrefixForHosts(50), 'wan' => 30],
);
$plan->allocations(); // ['lan' => Subnet: 192.168.0.0/26, 'wan' => Subnet: 192.168.0.64/30]
$plan->remaining();   // [Subnet: 192.168.0.68/30, Subnet: 192.168.0.72/29, Subnet: 192.168.0.80/28, Subnet: 192.168.0.96/27, Subnet: 192.168.0.128/25]

SubnetAllocator::allocateByPrefix(Subnet::fromCidr('10.0.0.0/24'), ['x' => 23]);
// Throws \RuntimeException: Cannot allocate 'x' as a /23: largest free block is a /24 (256 free addresses in 1 block)
```

---

## Value Object Classes

All value objects are immutable (readonly). `IPAddress`, `SubnetMask`, `WildcardMask` and `IPRange` implement `Stringable` and `JsonSerializable`, so they can be placed directly in a `json_encode()` payload. `ReverseDnsDelegation` and `AllocationPlan` implement `JsonSerializable` but not `Stringable`.

### IPAddress Class

`IPv4\IPAddress` - Immutable IPv4 address value object.

#### Construction

```php
new IPAddress(string|int $ipAddress): IPAddress
```
Create an IP address from dotted decimal format or its unsigned 32-bit integer value.
A string is always parsed as dotted decimal, so a numeric string such as `'3232235876'` is rejected.
Throws `\InvalidArgumentException` if the string is not a valid IPv4 address or the integer is outside `0` to `4294967295`.

**Example:**
```php
$ip = new IPAddress('192.168.1.100');
$ip = new IPAddress(3232235876);  // 192.168.1.100
```

##### fromInteger()
```php
IPAddress::fromInteger(int $integer): IPAddress
```
Create an IP address from its unsigned 32-bit integer value (the inverse of `asInteger()`).
Equivalent to `new IPAddress($integer)`.
Throws `\InvalidArgumentException` if the integer is outside `0` to `4294967295`.

**Example:**
```php
$ip = IPAddress::fromInteger(3232235876);  // 192.168.1.100
$next = IPAddress::fromInteger($ip->asInteger() + 1);  // 192.168.1.101
```

#### Format Methods

All format methods are provided via the `Formattable` trait:

##### asQuads()
```php
asQuads(): string
```
Returns IP in dotted decimal format.

**Example:** `"192.168.1.100"`

##### asArray()
```php
asArray(): string[]
```
Returns array of octet strings.

**Example:** `['192', '168', '1', '100']`

##### asHex()
```php
asHex(): string
```
Returns IP in hexadecimal format.

**Example:** `"C0A80164"`

##### asBinary()
```php
asBinary(): string
```
Returns IP in binary format.

**Example:** `"11000000101010000000000101100100"`

##### asInteger()
```php
asInteger(): int
```
Returns IP as 32-bit integer.

**Example:** `3232235876`

##### __toString()
```php
__toString(): string
```
Returns IP in dotted decimal format (same as asQuads()).

##### jsonSerialize()
```php
jsonSerialize(): string
```
Implement JsonSerializable interface. Encodes as dotted decimal format (same as asQuads()).

**Example:**
```php
\json_encode($subnet->networkAddress());  // "192.168.1.0"
```

#### Classification Methods

See [Subnet Classification](#classification) - same methods available on IPAddress.

#### Utility Methods

##### arpaDomain()
```php
arpaDomain(): string
```
Get reverse DNS ARPA domain.

**Example:**
```php
$arpa = $ip->arpaDomain();  // "100.1.168.192.in-addr.arpa"
```

##### networkClass()
```php
networkClass(): NetworkClass
```
Get the classful network class.

##### equals()
```php
equals(IPAddress $other): bool
```
Compare with another IPAddress.

##### compare()
```php
compare(IPAddress $other): int
```
Order addresses by unsigned 32-bit integer value. Returns `-1` if this address is lower, `0` if equal, `1` if higher; usable as a `usort()` callback.

**Example:**
```php
$addresses = [new IPAddress('192.168.0.1'), new IPAddress('10.0.0.0'), new IPAddress('9.255.255.255')];
usort($addresses, fn (IPAddress $a, IPAddress $b): int => $a->compare($b));
// 9.255.255.255, 10.0.0.0, 192.168.0.1
```

##### next()
```php
next(): IPAddress
```
Get the following address, carrying across octet boundaries. Returns a new instance.

Throws `\RuntimeException` if the address is 255.255.255.255.

**Example:**
```php
$next = (new IPAddress('10.0.0.255'))->next();  // 10.0.1.0
```

##### previous()
```php
previous(): IPAddress
```
Get the preceding address, borrowing across octet boundaries. Returns a new instance.

Throws `\RuntimeException` if the address is 0.0.0.0.

**Example:**
```php
$previous = (new IPAddress('10.0.1.0'))->previous();  // 10.0.0.255
```

---

### SubnetMask Class

`IPv4\SubnetMask` - Immutable subnet mask value object.

#### Construction

```php
new SubnetMask(int $prefix): SubnetMask
```

**Example:**
```php
$mask = new SubnetMask(24);  // Creates 255.255.255.0
```

#### Methods

##### prefix()
```php
prefix(): int
```
Returns the CIDR prefix length.

##### wildcardMask()
```php
wildcardMask(): WildcardMask
```
Returns the corresponding wildcard mask.

##### equals()
```php
equals(SubnetMask $other): bool
```
Compare with another SubnetMask.

#### Format Methods

Same as IPAddress: `asQuads()`, `asArray()`, `asHex()`, `asBinary()`, `asInteger()`, `__toString()`, `jsonSerialize()`

---

### WildcardMask Class

`IPv4\WildcardMask` - Immutable wildcard mask value object (inverse of subnet mask).

#### Construction

```php
new WildcardMask(int $prefix): WildcardMask
```

**Example:**
```php
$wildcard = new WildcardMask(24);  // Creates 0.0.0.255
```

#### Methods

##### prefix()
```php
prefix(): int
```
Returns the CIDR prefix length.

##### subnetMask()
```php
subnetMask(): SubnetMask
```
Returns the corresponding subnet mask.

##### equals()
```php
equals(WildcardMask $other): bool
```
Compare with another WildcardMask.

#### Format Methods

Same as IPAddress: `asQuads()`, `asArray()`, `asHex()`, `asBinary()`, `asInteger()`, `__toString()`, `jsonSerialize()`

---

### Mask Interface

`IPv4\Mask` - Common interface implemented by `SubnetMask` and `WildcardMask`. Extends `Stringable` and `JsonSerializable`.

```php
prefix(): int
asQuads(): string
asArray(): string[]
asHex(): string
asBinary(): string
asInteger(): int
```

Use it as a type hint when a function accepts either kind of mask:

```php
function describe(IPv4\Mask $mask): string
{
    return "/{$mask->prefix()} = {$mask->asQuads()}";
}

describe($subnet->mask());          // '/24 = 255.255.255.0'
describe($subnet->wildcardMask());  // '/24 = 0.0.0.255'
```

---

### IPRange Class

`IPv4\IPRange` - Immutable IP range value object. Implements `IteratorAggregate`, `Countable`, `JsonSerializable`, and `Stringable`.

**Interface Support:**
- **`IteratorAggregate`** - Use `foreach` to iterate over all IP addresses in the range
- **`Countable`** - Use PHP's `count()` function to get the number of IPs
- **`JsonSerializable`** - Encodes as `{"start": ..., "end": ...}`
- **`Stringable`** - Automatically converts to "start - end" format

#### Construction

```php
new IPRange(string|IPAddress $start, string|IPAddress $end): IPRange
```

Each address may be a dotted-quad string or an `IPAddress`, in any combination. `IPAddress` instances are stored as given, so `start()` and `end()` return the same objects.

Throws `\InvalidArgumentException` if either string is not a valid IPv4 address, or if start is greater than end.

**Examples:**
```php
$range = new IPRange(
    new IPAddress('192.168.1.0'),
    new IPAddress('192.168.1.255')
);

$pool = new IPRange('192.168.10.100', '192.168.10.199');  // 100 addresses
$mixed = new IPRange('10.0.0.1', new IPAddress('10.0.0.10'));

new IPRange('192.168.1.100', '192.168.1.50');  // InvalidArgumentException: Start IP '192.168.1.100' is greater than end IP '192.168.1.50'
new IPRange('10.0.0.1', '10.0.0.256');         // InvalidArgumentException: Invalid IPv4 address: '10.0.0.256'
```

#### Methods

##### start()
```php
start(): IPAddress
```
Returns the starting IP address.

##### end()
```php
end(): IPAddress
```
Returns the ending IP address.

##### count()
```php
count(): int
```
Returns the number of IPs in the range. Because IPRange implements `Countable`, you can use either the `count()` method or PHP's built-in `count()` function.

**Examples:**
```php
// Using the method
$count = $range->count();  // 256

// Using PHP's \count() function (recommended)
$count = \count($range);  // 256
```

##### at()
```php
at(int $index): IPAddress
```
Returns the address at a zero-based index in O(1) time and additional space. Index `0` is the range's start, and `count() - 1` is its end. Negative indices and indices greater than or equal to `count()` throw `\OutOfRangeException`.

**Examples:**
```php
$hosts = Subnet::fromCidr('192.0.2.0/30')->hostRange();
echo $hosts->at(0); // 192.0.2.1, the first usable host
echo $hosts->at($hosts->count() - 1); // 192.0.2.2

$all = Subnet::fromCidr('0.0.0.0/0')->addressRange();
echo $all->at(4_294_967_295); // 255.255.255.255
```

The existing host-range rules apply: `/31` includes both addresses, and `/32` includes its single address.

##### indexOf()
```php
indexOf(string|IPAddress $ip): ?int
```
Returns the address's zero-based index in O(1) time and additional space, or `null` for a valid IPv4 address outside the range. Malformed strings throw `\InvalidArgumentException`. For every valid index, `$range->indexOf($range->at($index)) === $index`.

**Examples:**
```php
$hosts = Subnet::fromCidr('192.0.2.0/30')->hostRange();
$hosts->indexOf('192.0.2.1'); // 0
$hosts->indexOf(new IPAddress('192.0.2.2')); // 1
$hosts->indexOf('192.0.2.0'); // null (network address outside this host range)
```

Use `!== null` to distinguish an address at index `0` from an address outside the range.

##### contains()
```php
contains(string|IPAddress $ip): bool
```
Check if an IP is within the range.
Throws `\InvalidArgumentException` if `$ip` is a string that is not a valid IPv4 address.

`contains()` checks a single address. To compare two ranges, use `containsRange()`, `overlaps()`, `intersection()` or `isAdjacent()` below.

The range relations take an `IPRange` only, not a `Subnet`. Convert a subnet with `$subnet->addressRange()` (every address, network to broadcast) or `$subnet->hostRange()` (usable hosts only; for `/31` and `/32` this is the same as the address range). All four compare the range endpoints in O(1) time and never throw.

##### containsRange()
```php
containsRange(IPRange $other): bool
```
Check if every address of `$other` is within this range. Containment is inclusive: a range contains itself, and shared endpoints count.

**Examples:**
```php
$scope = Subnet::fromCidr('192.168.10.0/24')->hostRange();  // 192.168.10.1 - 192.168.10.254
$scope->containsRange(new IPRange('192.168.10.150', '192.168.10.250'));  // true
$scope->containsRange(new IPRange('192.168.10.150', '192.168.10.255'));  // false (includes the broadcast address)

$p2p = Subnet::fromCidr('192.0.2.6/31')->addressRange();
$p2p->containsRange(new IPRange('192.0.2.7', '192.0.2.7'));  // true (a /32 inside a /31)
```

##### overlaps()
```php
overlaps(IPRange $other): bool
```
Check if the two ranges share at least one address. Symmetric; a single shared endpoint counts, but touching ranges (one ends just before the other starts) do not overlap.

**Examples:**
```php
$engineering = new IPRange('192.168.10.100', '192.168.10.199');
$engineering->overlaps(new IPRange('192.168.10.150', '192.168.10.250'));  // true
$engineering->overlaps(new IPRange('192.168.10.200', '192.168.10.250'));  // false (touching, not overlapping)
```

##### intersection()
```php
intersection(IPRange $other): ?IPRange
```
Return the addresses shared by both ranges, from the larger start to the smaller end, or `null` if the ranges are disjoint. Unlike `Subnet::intersection()`, the result need not be either input: partially overlapping ranges give the overlapping part.

**Examples:**
```php
$engineering = new IPRange('192.168.10.100', '192.168.10.199');
$sales = new IPRange('192.168.10.150', '192.168.10.250');

echo $engineering->intersection($sales);  // 192.168.10.150 - 192.168.10.199
$engineering->intersection(new IPRange('192.168.11.10', '192.168.11.254'));  // null

// A single shared address gives a one-address range
echo (new IPRange('192.0.2.0', '192.0.2.8'))->intersection(new IPRange('192.0.2.8', '192.0.2.15'));  // 192.0.2.8 - 192.0.2.8
```

##### isAdjacent()
```php
isAdjacent(IPRange $other): bool
```
Check if the two ranges are disjoint and contiguous: one ends immediately before the other starts, in either order. Overlapping and identical ranges are not adjacent, matching `Subnet::isAdjacent()`. The address space does not wrap, so a range ending at `255.255.255.255` is not adjacent to one starting at `0.0.0.0`.

**Examples:**
```php
$first = new IPRange('192.168.20.10', '192.168.20.99');
$second = new IPRange('192.168.20.100', '192.168.20.199');
$first->isAdjacent($second);  // true
$second->isAdjacent($first);  // true

// Consecutive /32s and /31s are adjacent
(new IPRange('192.0.2.7', '192.0.2.7'))->isAdjacent(new IPRange('192.0.2.8', '192.0.2.8'));  // true
(new IPRange('192.0.2.6', '192.0.2.7'))->isAdjacent(new IPRange('192.0.2.8', '192.0.2.9'));  // true

(new IPRange('0.0.0.0', '0.0.0.0'))->isAdjacent(new IPRange('255.255.255.255', '255.255.255.255'));  // false (no wrap)
```

##### equals()
```php
equals(IPRange $other): bool
```
Compare with another IPRange.

##### getIterator()
```php
getIterator(): \Generator<IPAddress>
```
Get iterator for foreach loops. Because IPRange implements `IteratorAggregate`, you can iterate directly without calling this method explicitly.

**Examples:**
```php
// Using foreach (recommended)
foreach ($range as $ip) {
    echo $ip . "\n";
}

// Or access the iterator directly
$iterator = $range->getIterator();
```

##### toArray()
```php
toArray(): IPAddress[]
```
Convert to array of IPAddress objects. **Caution:** Memory-intensive for large ranges.

##### toSubnets()
```php
toSubnets(): array // list<Subnet>
```
Return the smallest exact CIDR cover of the inclusive range. The numerically indexed list contains canonical, disjoint subnets sorted by ascending network address, with no gaps or addresses outside the range.

Unlike `Subnets::summarize()`, this never adds addresses. `SubnetParser::fromRange()` still requires a range that represents exactly one aligned CIDR block.

```php
$range = new IPRange(new IPAddress('192.0.2.1'), new IPAddress('192.0.2.6'));
$cidrs = \array_map(static fn (Subnet $subnet): string => $subnet->cidr(), $range->toSubnets());
// ['192.0.2.1/32', '192.0.2.2/31', '192.0.2.4/31', '192.0.2.6/32']
```

A singleton returns one `/32`; the full range `0.0.0.0`–`255.255.255.255` returns one `0.0.0.0/0`. Addresses are not enumerated: work is proportional to the output block count times the fixed 32-bit width, and memory is proportional to the output block count.

##### __toString()
```php
__toString(): string
```
Returns range as "start - end" format.

**Example:** `"192.168.1.0 - 192.168.1.255"`

##### jsonSerialize()
```php
jsonSerialize(): array{start: string, end: string}
```
Implement JsonSerializable interface. Encodes only the start and end addresses, not every IP in the range.

**Example:**
```php
\json_encode($subnet->hostRange());  // {"start":"192.168.1.1","end":"192.168.1.254"}
```

---

### ReverseDnsDelegation Class

`IPv4\ReverseDnsDelegation` - Immutable [RFC 2317](https://www.rfc-editor.org/rfc/rfc2317.html) classless delegation data for a `/25`–`/32` block. Implements `JsonSerializable`. All names omit trailing dots. NS, SOA and PTR records, parent-zone cooperation and DNS publishing remain the caller's responsibility.

#### Construction

```php
new ReverseDnsDelegation(Subnet $subnet, ?string $childLabel = null): ReverseDnsDelegation
```

Usually obtained via [`Subnet::classlessDelegation()`](#classlessdelegation), which takes the same arguments and throws the same exceptions.

#### Methods

##### parentZone()
```php
parentZone(): string
```
The enclosing `/24` reverse zone, which publishes the CNAME records, e.g., `"2.0.192.in-addr.arpa"`.

##### childZone()
```php
childZone(): string
```
The delegated child zone origin, one label below the parent, e.g., `"64-127.2.0.192.in-addr.arpa"`.

##### cnameRecords()
```php
cnameRecords(): array // array<string, string>: owner => target
```
One record per address in ascending address order, network and broadcast included: delegation describes address space, not usable hosts. Each owner is the address's PTR name in the parent zone; each target is the same last-octet label in the child zone.

```php
Subnet::fromCidr('192.0.2.6/31')->classlessDelegation()->cnameRecords();
// [
//     "6.2.0.192.in-addr.arpa" => "6.6-7.2.0.192.in-addr.arpa",
//     "7.2.0.192.in-addr.arpa" => "7.6-7.2.0.192.in-addr.arpa",
// ]
```

##### jsonSerialize()
```php
jsonSerialize(): array{parent_zone: string, child_zone: string, cname_records: array<string, string>}
```
Implement JsonSerializable interface. Encodes the parent zone, the child zone and the CNAME records (owner => target, in the same order as `cnameRecords()`).

**Example:**
```php
\json_encode(Subnet::fromCidr('192.0.2.6/31')->classlessDelegation());
// {"parent_zone":"2.0.192.in-addr.arpa","child_zone":"6-7.2.0.192.in-addr.arpa","cname_records":{"6.2.0.192.in-addr.arpa":"6.6-7.2.0.192.in-addr.arpa","7.2.0.192.in-addr.arpa":"7.6-7.2.0.192.in-addr.arpa"}}
```

---

### AllocationPlan Class

`IPv4\AllocationPlan` - Immutable result of [`SubnetAllocator::allocate()`](#allocate) or [`allocateByPrefix()`](#allocatebyprefix). Its allocations, reservations and remaining space are disjoint and together cover exactly the parent network. All subnets are canonical. Implements `JsonSerializable`.

#### Construction

Obtain plans from `SubnetAllocator::allocate()` or `allocateByPrefix()`. The constructor is internal. It checks only the shape of its arguments, throwing `\InvalidArgumentException` if an allocation key is not a string, the reserved or remaining array is not a list, or any element is not a `Subnet`. It does not check that the parts are disjoint, canonical or cover a parent network; only plans from `allocate()` or `allocateByPrefix()` are guaranteed to.

#### Methods

##### allocations()
```php
allocations(): array // array<string, Subnet>: name => subnet
```
One allocated subnet per requirement, in the caller's requirement order.

##### reserved()
```php
reserved(): array // list<Subnet>
```
The union of the reservations as the fewest CIDR blocks, in ascending order.

##### remaining()
```php
remaining(): array // list<Subnet>
```
The free space left after reservations and allocations as the fewest CIDR blocks, in ascending order.

```php
$plan = SubnetAllocator::allocate(Subnet::fromCidr('192.0.2.0/29'), ['link' => 2], [Subnet::fromCidr('192.0.2.0/31')]);
$plan->allocations(); // ['link' => Subnet: 192.0.2.2/31]
$plan->remaining();   // [Subnet: 192.0.2.4/30]
```

##### jsonSerialize()
```php
jsonSerialize(): array // array<string, mixed>: allocations, reserved, remaining
```
Implement JsonSerializable interface. Encodes each subnet as its network CIDR string rather than a full subnet report: `allocations` as an object of name => CIDR in requirement order, and `reserved` and `remaining` as lists of CIDRs in ascending order. `allocations` is always a JSON object, `{}` when the plan has no allocations. Each CIDR parses back with `Subnet::fromNetworkCidr()`.

**Example:**
```php
\json_encode($plan, JSON_UNESCAPED_SLASHES);
// {"allocations":{"link":"192.0.2.2/31"},"reserved":["192.0.2.0/31"],"remaining":["192.0.2.4/30"]}

\json_encode(SubnetAllocator::allocate(Subnet::fromCidr('10.0.0.0/24'), []), JSON_UNESCAPED_SLASHES);
// {"allocations":{},"reserved":[],"remaining":["10.0.0.0/24"]}
```

---

## Enum Classes

### AddressType

`IPv4\AddressType` - Enum for IP address types.

**Values:**
- `AddressType::Private` - RFC 1918 private addresses
- `AddressType::Public` - Addresses not in any special-purpose range
- `AddressType::Loopback` - 127.0.0.0/8 loopback addresses
- `AddressType::LinkLocal` - 169.254.0.0/16 link-local/APIPA addresses
- `AddressType::Multicast` - 224.0.0.0/4 multicast addresses
- `AddressType::CarrierGradeNat` - 100.64.0.0/10 CGN shared address space
- `AddressType::Documentation` - RFC 5737 TEST-NET ranges
- `AddressType::Benchmarking` - RFC 2544 benchmarking addresses
- `AddressType::IetfProtocol` - RFC 5735/6890 IETF protocol assignments (192.0.0.0/24)
- `AddressType::Deprecated6to4` - RFC 3068/7526 deprecated 6to4 relay (192.88.99.0/24)
- `AddressType::Reserved` - 240.0.0.0/4 reserved for future use
- `AddressType::LimitedBroadcast` - 255.255.255.255 limited broadcast
- `AddressType::IanaReserved` - IANA Special-Purpose assignments (192.31.196.0/24, 192.52.193.0/24, 192.175.48.0/24, 192.88.99.2/32)
- `AddressType::ThisNetwork` - 0.0.0.0/8 "this" network

### NetworkClass

`IPv4\NetworkClass` - Enum for classful network classes.

**Values:**
- `NetworkClass::A`
- `NetworkClass::B`
- `NetworkClass::C`
- `NetworkClass::D`
- `NetworkClass::E`

**Methods:**

#### getDefaultMask()
```php
getDefaultMask(): ?string
```
Returns the default classful subnet mask (`'255.0.0.0'`, `'255.255.0.0'`, `'255.255.255.0'`), or `null` for classes D and E.

#### getDefaultPrefix()
```php
getDefaultPrefix(): ?int
```
Returns the default classful prefix (8, 16 or 24), or `null` for classes D and E.

**Example:**
```php
$class = IPv4\Subnet::fromCidr('172.16.0.0/12')->networkClass();  // NetworkClass::B
$class->getDefaultMask();    // '255.255.0.0'
$class->getDefaultPrefix();  // 16
```

---

## Migration from v4.x

See [Migration Guide](migration-v4-to-v5.md) for detailed information on migrating from version 4.x to 5.0.

## Additional Resources

- [Getting Started](getting-started.md) - Basic usage and examples
- [Core Features](core-features.md) - Common operations
- [Advanced Features](advanced-features.md) - Complex operations
- [Examples](examples.md) - Real-world use cases
