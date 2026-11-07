# Core Features

This document covers the fundamental operations and methods available in the IPv4 Subnet Calculator library.

## Table of Contents
- [Network Component Access](#network-component-access)
- [Ordering Subnets](#ordering-subnets)
- [Network Overlap and Containment](#network-overlap-and-containment)
- [IP Address Type Detection](#ip-address-type-detection)
- [Reverse DNS (ARPA Domain)](#reverse-dns-arpa-domain)
- [Subnet Operations](#subnet-operations)
- [Adjacent Subnet Navigation](#adjacent-subnet-navigation)

## Network Component Access

In v5.0, all network components are returned as **immutable value objects** (`IPAddress`, `SubnetMask`, `WildcardMask`) that provide format methods.

### CIDR Notation

Get CIDR notation in different forms:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.100/24');

// Input-preserving CIDR (returns the IP as entered)
$cidr = $subnet->cidr();  // "192.168.1.100/24"

// Canonical CIDR (always uses network address)
$networkCidr = $subnet->networkCidr();  // "192.168.1.0/24"
```

**When to use each:**
- `cidr()` - When you need to preserve the input IP (useful for audit trails and debugging)
- `networkCidr()` - When you need canonical form for comparisons and serialization

**Example - String Comparison:**
```php
$subnet1 = IPv4\Subnet::fromCidr('192.168.1.100/24');
$subnet2 = IPv4\Subnet::fromCidr('192.168.1.200/24');

// Different strings if using cidr()
echo $subnet1->cidr();  // "192.168.1.100/24"
echo $subnet2->cidr();  // "192.168.1.200/24"

// Same string if using networkCidr()
echo $subnet1->networkCidr();  // "192.168.1.0/24"
echo $subnet2->networkCidr();  // "192.168.1.0/24"

// Both are equal
\var_dump($subnet1->equals($subnet2));  // true
```

### Explicit Network Parsing

`isNetworkAddress()` checks the **original input IP's alignment**, even though `networkCidr()` always returns a canonical network. Use `fromNetworkCidr()` when a configuration entry must identify the network itself:

```php
$host = IPv4\Subnet::fromCidr('192.168.1.50/24');
\var_dump($host->isNetworkAddress()); // false
echo $host->cidr() . "\n";           // 192.168.1.50/24
echo $host->networkCidr() . "\n";    // 192.168.1.0/24

$network = IPv4\Subnet::fromNetworkCidr('192.168.1.0/24');
\var_dump($network->isNetworkAddress()); // true
```

`fromNetworkCidr('192.168.1.50/24')` throws `\InvalidArgumentException` because host bits are set. It first applies the existing CIDR syntax validation, preserving its diagnostics. The constructor and `fromCidr()` remain permissive about host bits and retain the supplied IP.

Only `0.0.0.0/0` is aligned for `/0`; `/31` requires an even input address, and every valid `/32` address is aligned.

### IP Address

Access the IP address as an IPAddress value object with format methods:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.112.203/23');

// Get IPAddress value object
$ip = $subnet->ipAddress();

// Access different formats via methods
$ipAddress        = $ip->asQuads();    // '192.168.112.203'
$ipAddressQuads   = $ip->asArray();    // ['192', '168', '112', '203']
$ipAddressHex     = $ip->asHex();      // 'C0A870CB'
$ipAddressBinary  = $ip->asBinary();   // '11000000101010000111000011001011'
$ipAddressInteger = $ip->asInteger();  // 3232264395

// Or use string casting
echo $ip;  // "192.168.112.203"
```

### Subnet Mask

The subnet mask defines which portion of an IP address represents the network:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.112.203/23');

// Get SubnetMask value object
$mask = $subnet->mask();

// Access different formats via methods
$subnetMask        = $mask->asQuads();    // '255.255.254.0'
$subnetMaskQuads   = $mask->asArray();    // ['255', '255', '254', '0']
$subnetMaskHex     = $mask->asHex();      // 'FFFFFE00'
$subnetMaskBinary  = $mask->asBinary();   // '11111111111111111111111000000000'
$subnetMaskInteger = $mask->asInteger();  // 4294966784

// Or use string casting
echo $mask;  // "255.255.254.0"

// Create one directly from a prefix or a dotted quad
$mask = new IPv4\SubnetMask(23);
$mask = IPv4\SubnetMask::fromString('255.255.254.0');
$mask->prefix();  // 23
```

### Wildcard Mask

Wildcard masks are the inverse of subnet masks, commonly used in Cisco ACLs and OSPF configurations.

```php
$subnet = IPv4\Subnet::fromCidr('192.168.112.203/23');

// Get WildcardMask value object
$wildcard = $subnet->wildcardMask();

// Access different formats via methods
$wildcardMask        = $wildcard->asQuads();    // '0.0.1.255'
$wildcardMaskQuads   = $wildcard->asArray();    // ['0', '0', '1', '255']
$wildcardMaskHex     = $wildcard->asHex();      // '000001FF'
$wildcardMaskBinary  = $wildcard->asBinary();   // '00000000000000000000000111111111'
$wildcardMaskInteger = $wildcard->asInteger();  // 511

// Or use string casting
echo $wildcard;  // "0.0.1.255"
```

**Use Cases:**
- Cisco ACL configuration
- OSPF area configuration
- Router wildcard matching

### Network Portion

The network portion is the IP address with host bits set to zero:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.112.203/23');

// Get IPAddress value object
$network = $subnet->networkPortion();

// Access different formats
$networkQuads   = $network->asQuads();    // '192.168.112.0'
$networkArray   = $network->asArray();    // ['192', '168', '112', '0']
$networkHex     = $network->asHex();      // 'C0A87000'
$networkBinary  = $network->asBinary();   // '11000000101010000111000000000000'
$networkInteger = $network->asInteger();  // 3232264192
```

### Host Portion

The host portion is the IP address with network bits set to zero:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.112.203/23');

// Get IPAddress value object
$host = $subnet->hostPortion();

// Access different formats
$hostQuads   = $host->asQuads();    // '0.0.0.203'
$hostArray   = $host->asArray();    // ['0', '0', '0', '203']
$hostHex     = $host->asHex();      // '000000CB'
$hostBinary  = $host->asBinary();   // '00000000000000000000000011001011'
$hostInteger = $host->asInteger();  // 203
```

### Minimum and Maximum Host

Get the first and last usable host addresses as IPAddress value objects:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.112.203/23');

// Minimum (first usable host) - returns IPAddress object
$minHost = $subnet->minHost();

// Access different formats
$minHostQuads   = $minHost->asQuads();    // '192.168.112.1'
$minHostArray   = $minHost->asArray();    // ['192', '168', '112', '1']
$minHostHex     = $minHost->asHex();      // 'C0A87001'
$minHostBinary  = $minHost->asBinary();   // '11000000101010000111000000000001'
$minHostInteger = $minHost->asInteger();  // 3232264193

// Maximum (last usable host) - returns IPAddress object
$maxHost = $subnet->maxHost();

// Access different formats
$maxHostQuads   = $maxHost->asQuads();    // '192.168.113.254'
$maxHostArray   = $maxHost->asArray();    // ['192', '168', '113', '254']
$maxHostHex     = $maxHost->asHex();      // 'C0A871FE'
$maxHostBinary  = $maxHost->asBinary();   // '11000000101010000111000111111110'
$maxHostInteger = $maxHost->asInteger();  // 3232264702
```

**RFC 3021 Special Cases:**

For /31 networks (point-to-point links), both addresses are usable:
```php
$p2p = IPv4\Subnet::fromCidr('10.0.0.0/31');
echo $p2p->minHost();  // "10.0.0.0"
echo $p2p->maxHost();  // "10.0.0.1"
```

For /32 networks (single host), the only address is both min and max:
```php
$single = IPv4\Subnet::fromCidr('10.0.0.1/32');
echo $single->minHost();  // "10.0.0.1"
echo $single->maxHost();  // "10.0.0.1"
```

### Iterate and Count IP Addresses

The IPRange class implements `IteratorAggregate` and `Countable` interfaces, enabling iteration with `foreach` and counting with `count()`.

#### Iterate with foreach

Loop through all IP addresses using IPRange objects:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/29');

// All IP addresses (including network and broadcast) - returns IPRange
foreach ($subnet->addressRange() as $ipAddress) {
    echo $ipAddress . "\n";  // IPAddress objects automatically convert to string
}
// Output:
// 192.168.1.0 (network)
// 192.168.1.1
// 192.168.1.2
// 192.168.1.3
// 192.168.1.4
// 192.168.1.5
// 192.168.1.6
// 192.168.1.7 (broadcast)

// Only usable host addresses (excludes network and broadcast)
foreach ($subnet->hostRange() as $hostAddress) {
    echo $hostAddress . "\n";
}
// Output:
// 192.168.1.1
// 192.168.1.2
// 192.168.1.3
// 192.168.1.4
// 192.168.1.5
// 192.168.1.6
```

#### Count IP Addresses

Use PHP's built-in `count()` function directly on IPRange objects:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');

// Count all IPs in the range
$totalIPs = \count($subnet->addressRange());  // 256

// Count only usable hosts
$usableHosts = \count($subnet->hostRange());  // 254

echo "Total IPs: {$totalIPs}\n";
echo "Usable hosts: {$usableHosts}\n";
```

#### Access Addresses by Index

Use `at()` to retrieve an address directly and `indexOf()` to find its position. Both operations use zero-based indices:

```php
$subnet = IPv4\Subnet::fromCidr('192.0.2.0/24');
$hosts = $subnet->hostRange();

echo $subnet->hostRange()->at(0); // 192.0.2.1, first usable host
echo $hosts->at($hosts->count() - 1); // 192.0.2.254, last usable host
$hosts->indexOf('192.0.2.100'); // 99
$hosts->indexOf('192.0.2.0'); // null, outside the usable host range
```

`at()` throws `\OutOfRangeException` for negative indices or indices at or beyond the count. `indexOf()` accepts a string or `IPAddress`, returns `null` for a valid address outside the range, and throws `\InvalidArgumentException` for malformed strings. Check its result with `!== null`, since `0` is a valid index.

Indexing follows the existing usable-host rules:

| Subnet | `hostRange()->at(0)` | Last usable host | Host count |
|---|---|---|---|
| `192.0.2.0/30` | `192.0.2.1` | `192.0.2.2` | 2 |
| `192.0.2.6/31` | `192.0.2.6` | `192.0.2.7` | 2 |
| `192.0.2.7/32` | `192.0.2.7` | `192.0.2.7` | 1 |

#### Combining Iteration and Counting

```php
$subnet = IPv4\Subnet::fromCidr('10.0.0.0/28');

$range = $subnet->hostRange();

echo "Processing " . \count($range) . " hosts:\n";

$index = 1;
foreach ($range as $ip) {
    echo "[{$index}/" . \count($range) . "] Processing {$ip}\n";
    $index++;
}
```

#### IPRange String Representation

IPRange provides a readable string format:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');

$range = $subnet->addressRange();

echo $range;  // "192.168.1.0 - 192.168.1.255"
```

#### Working with IPRange Directly

You can create and work with IPRange objects independently:

```php
use IPv4\IPRange;
use IPv4\IPAddress;

// Create a range directly
$start = new IPAddress('10.0.0.1');
$end = new IPAddress('10.0.0.10');
$range = new IPRange($start, $end);

// Count IPs in the range
echo "Range contains: " . \count($range) . " IPs\n";  // 10

// Iterate the range
foreach ($range as $ip) {
    echo $ip . "\n";
}

// Check if IP is in range
$isInRange = $range->contains('10.0.0.5');  // true
```

**Performance Note:** For large subnets (e.g., /16 or larger), iterating all IPs may consume significant memory and time. IPRange uses a generator for memory efficiency, but iteration itself will still process millions of addresses.

#### Convert a Range to CIDR Blocks

Use `toSubnets()` to get the fewest CIDR blocks covering exactly the range, sorted by network address:

```php
$range = new IPv4\IPRange(new IPv4\IPAddress('10.0.0.1'), new IPv4\IPAddress('10.0.0.10'));
foreach ($range->toSubnets() as $subnet) {
    echo $subnet->cidr() . "\n";
}
// 10.0.0.1/32, 10.0.0.2/31, 10.0.0.4/30, 10.0.0.8/31, 10.0.0.10/32
```

The endpoints are inclusive, and no addresses outside them are included. This works for arbitrary ranges, including `hostRange()` results, without iterating over individual addresses. See [Exact Range-to-CIDR Conversion](advanced-features.md#exact-range-to-cidr-conversion) for how this differs from parsing and summarization.

### Check if IP is in Subnet

Determine whether a specific IP address belongs to the subnet:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.112.0/23');

$inSubnet = $subnet->containsIP('192.168.112.5');   // true
$inSubnet = $subnet->containsIP('192.168.113.200'); // true
$inSubnet = $subnet->containsIP('192.168.111.5');   // false
$inSubnet = $subnet->containsIP('192.168.114.1');   // false

// Network and broadcast addresses are included
$inSubnet = $subnet->containsIP('192.168.112.0');   // true (network address)
$inSubnet = $subnet->containsIP('192.168.113.255'); // true (broadcast address)
```

**Use Cases:**
- Access control and authorization
- Log analysis and filtering
- Network segmentation validation
- Firewall rule verification

## Ordering Subnets

`compare()` returns exactly `-1`, `0` or `1` by numeric network address, then ascending prefix. Broader networks sort first at the same start. It uses the canonical network regardless of the original host IP, and zero agrees with `equals()`:

```php
$broad = IPv4\Subnet::fromCidr('10.255.255.255/8');
$narrow = IPv4\Subnet::fromCidr('10.0.0.1/16');
\var_dump($broad->compare($narrow)); // -1
\var_dump($broad->compare(IPv4\Subnet::fromCidr('10.0.0.0/8'))); // 0
```

Numeric ordering puts `2.0.0.0/8` before `10.0.0.0/8`. The same rules apply to `/0`, `/31` and `/32`, including both IPv4 endpoints. See [sorting network blocks](examples.md#sort-network-blocks).

## Network Overlap and Containment

Useful for network planning, firewall rule validation, and routing table conflict detection.

### Check if Two Subnets Overlap

Determine if two subnets share any IP addresses:

```php
$subnet1 = IPv4\Subnet::fromCidr('192.168.1.0/24');
$subnet2 = IPv4\Subnet::fromCidr('192.168.1.128/25');

$overlaps = $subnet1->overlaps($subnet2);  // true - they share 192.168.1.128-255

// Non-overlapping subnets
$subnet3 = IPv4\Subnet::fromCidr('192.168.2.0/24');
$overlaps = $subnet1->overlaps($subnet3);  // false - completely separate
```

**Overlap Detection Rules:**
- If one subnet completely contains the other, they overlap
- If they share any IP addresses, they overlap
- Adjacent subnets do not overlap (192.168.1.0/24 and 192.168.2.0/24)

**Use Cases:**
- Validate firewall rules for conflicts
- Detect routing table issues
- Prevent duplicate IP assignments
- Network planning and IPAM

### Check Whether Subnets Touch

`isAdjacent()` tests whether disjoint full address ranges touch, including network and broadcast addresses. It is symmetric, ignores input host bits and allows unequal prefixes:

```php
$left = IPv4\Subnet::fromCidr('10.0.0.200/24');
$right = IPv4\Subnet::fromCidr('10.0.1.100/25');
\var_dump($left->isAdjacent($right)); // true
\var_dump(IPv4\Subnet::fromCidr('0.0.0.0/0')->isAdjacent($left)); // false; contains the block
```

Adjacency does not guarantee aggregation: `10.0.1.0/24` and `10.0.2.0/24` touch but cannot merge into one exact CIDR. Gaps, equality, containment and overlap return false. Consecutive `/31`s and `/32`s can touch, but IPv4 endpoints never wrap around. See [finding adjacent network blocks](examples.md#find-adjacent-network-blocks).

### Find the Shared Block

`intersection()` returns the canonical narrower block when two CIDRs overlap and `null` when disjoint or merely adjacent. It is symmetric by value, includes all addresses, and preserves both input objects:

```php
$parent = IPv4\Subnet::fromCidr('10.1.2.3/8');
$child = IPv4\Subnet::fromCidr('10.20.30.40/16');
$shared = $parent->intersection($child);
echo $shared?->cidr() . "\n"; // 10.20.0.0/16
echo $child->cidr() . "\n";   // 10.20.30.40/16
```

Equal host-bit inputs also return a canonical input IP. `/0` can intersect every block, `/31` can intersect either member `/32`, and both IPv4 endpoints follow the same rules. See [restricting a network block](examples.md#restrict-a-network-block-to-a-scope).

### Check if One Subnet Contains Another

Determine if a subnet completely contains another subnet:

```php
$large = IPv4\Subnet::fromCidr('10.0.0.0/8');
$small = IPv4\Subnet::fromCidr('10.1.2.0/24');

$contains = $large->contains($small);  // true - 10.0.0.0/8 contains 10.1.2.0/24

// Equal subnets
$subnet1 = IPv4\Subnet::fromCidr('192.168.1.0/24');
$subnet2 = IPv4\Subnet::fromCidr('192.168.1.0/24');
$contains = $subnet1->contains($subnet2);  // true - a subnet contains itself

// Larger does not contain smaller
$small = IPv4\Subnet::fromCidr('172.16.0.0/16');
$large = IPv4\Subnet::fromCidr('172.16.0.0/12');
$contains = $small->contains($large);  // false - smaller cannot contain larger
```

**Containment Rules:**
- A subnet contains itself
- A larger prefix length subnet cannot contain a smaller prefix length subnet
  (e.g., /24 cannot contain /16)
- All IPs of the contained subnet must be within the containing subnet

### Check if Subnet is Contained Within Another

The inverse operation - check if this subnet is contained within another:

```php
$small = IPv4\Subnet::fromCidr('172.16.0.0/16');
$large = IPv4\Subnet::fromCidr('172.16.0.0/12');

$isContained = $small->isContainedIn($large);  // true - 172.16.0.0/16 is within 172.16.0.0/12

// Not contained
$subnet1 = IPv4\Subnet::fromCidr('192.168.1.0/24');
$subnet2 = IPv4\Subnet::fromCidr('192.168.2.0/24');
$isContained = $subnet1->isContainedIn($subnet2);  // false - different networks
```

**Practical Example - Validate Subnet Assignment:**

```php
// Corporate policy: All department subnets must be within 10.0.0.0/8
$corporateNetwork = IPv4\Subnet::fromCidr('10.0.0.0/8');
$proposedSubnet   = IPv4\Subnet::fromCidr('192.168.1.0/24');

if (!$proposedSubnet->isContainedIn($corporateNetwork)) {
    echo "Error: Proposed subnet is outside corporate address space\n";
}
```

## IP Address Type Detection

Useful for security validation, routing decisions, and network classification. All methods comply with IANA IPv4 Special-Purpose Address Registry and relevant RFCs.

### Private vs Public Addresses

Check if an IP address is private (RFC 1918) or public (not in any special-purpose range):

```php
$private = IPv4\Subnet::fromCidr('192.168.1.100/24');
$public  = IPv4\Subnet::fromCidr('8.8.8.8/32');

$private->isPrivate();  // true - 192.168.0.0/16 is RFC 1918 private
$public->isPrivate();   // false - 8.8.8.8 is public

$private->isPublic();   // false - private addresses are not public
$public->isPublic();    // true - not in any special-purpose range
```

> **Note:** These methods classify the subnet's input IP address, not the whole block. A block that straddles a range
> boundary is classified by whichever side its input IP is on: `10.0.0.0/7` is private, but it also spans the public
> `11.0.0.0/8`. To check a whole block, test it against the range directly:
>
> ```php
> $block = IPv4\Subnet::fromCidr('10.0.0.0/7');
>
> $block->isPrivate();                                         // true - the input IP 10.0.0.0 is private
> $block->isContainedIn(IPv4\Subnet::fromCidr('10.0.0.0/8'));  // false - the block extends past 10.0.0.0/8
> ```

**Private Address Ranges (RFC 1918):**
- 10.0.0.0/8
- 172.16.0.0/12
- 192.168.0.0/16

### Special-Purpose Address Ranges

Check for various special-purpose IP address types:

```php
$loopback      = IPv4\Subnet::fromCidr('127.0.0.1/32');
$linkLocal     = IPv4\Subnet::fromCidr('169.254.1.1/32');
$multicast     = IPv4\Subnet::fromCidr('224.0.0.1/32');
$cgn           = IPv4\Subnet::fromCidr('100.64.0.1/32');
$docs          = IPv4\Subnet::fromCidr('192.0.2.1/32');
$benchmark     = IPv4\Subnet::fromCidr('198.18.0.1/32');
$ietfProtocol  = IPv4\Subnet::fromCidr('192.0.0.1/32');
$deprecated6to4 = IPv4\Subnet::fromCidr('192.88.99.1/32');
$ianaReserved  = IPv4\Subnet::fromCidr('192.31.196.1/32');
$reserved      = IPv4\Subnet::fromCidr('240.0.0.1/32');
$broadcast     = IPv4\Subnet::fromCidr('255.255.255.255/32');
$thisNetwork   = IPv4\Subnet::fromCidr('0.0.0.1/32');

$loopback->isLoopback();              // true - 127.0.0.0/8
$linkLocal->isLinkLocal();            // true - 169.254.0.0/16 (APIPA)
$multicast->isMulticast();            // true - 224.0.0.0/4
$cgn->isCarrierGradeNat();            // true - 100.64.0.0/10 (Shared Address Space)
$docs->isDocumentation();             // true - TEST-NET ranges
$benchmark->isBenchmarking();         // true - 198.18.0.0/15
$ietfProtocol->isIetfProtocol();      // true - 192.0.0.0/24 (DS-Lite, NAT64, etc.)
$deprecated6to4->is6to4Relay();       // true - 192.88.99.0/24 (deprecated)
$ianaReserved->isIanaReserved();      // true - IANA special-purpose assignments
$reserved->isReserved();              // true - 240.0.0.0/4
$broadcast->isLimitedBroadcast();     // true - 255.255.255.255 only
$thisNetwork->isThisNetwork();        // true - 0.0.0.0/8
```

### Get Address Type Classification

Get a string representation of the address type:

```php
$classifications = [
    '192.168.1.1'     => 'private',
    '8.8.8.8'         => 'public',
    '127.0.0.1'       => 'loopback',
    '169.254.1.1'     => 'link-local',
    '224.0.0.1'       => 'multicast',
    '100.64.0.1'      => 'carrier-grade-nat',
    '192.0.2.1'       => 'documentation',
    '198.18.0.1'      => 'benchmarking',
    '192.0.0.1'       => 'ietf-protocol',
    '192.88.99.1'     => 'deprecated-6to4',
    '192.31.196.1'    => 'iana-reserved',
    '240.0.0.1'       => 'reserved',
    '255.255.255.255' => 'limited-broadcast',
    '0.0.0.1'         => 'this-network',
];

foreach ($classifications as $ip => $expectedType) {
    $subnet = IPv4\Subnet::fromCidr("{$ip}/32");
    $type = $subnet->addressType();  // Returns IPv4\AddressType enum
    echo "{$ip}: " . $type->value . "\n";  // Use ->value to get string
}
```

**Working with the AddressType Enum:**

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.1/32');
$type = $subnet->addressType();  // Returns IPv4\AddressType::Private

// Get string value
echo $type->value;  // 'private'

// Type-safe comparison
if ($type === IPv4\AddressType::Private) {
    echo "This is a private address\n";
}

// Use in match expressions
$description = match ($type) {
    IPv4\AddressType::Private => 'RFC 1918 private address',
    IPv4\AddressType::Public => 'Public address',
    IPv4\AddressType::Loopback => 'Loopback address',
    default => 'Special-purpose address',
};
```

### Supported Range Types Reference

| Method | Range | RFC | Description |
|--------|-------|-----|-------------|
| `isPrivate()` | 10.0.0.0/8<br>172.16.0.0/12<br>192.168.0.0/16 | RFC 1918 | Private network addresses |
| `isLoopback()` | 127.0.0.0/8 | RFC 1122 | Loopback addresses |
| `isLinkLocal()` | 169.254.0.0/16 | RFC 3927 | Link-local/APIPA addresses |
| `isMulticast()` | 224.0.0.0/4 | RFC 5771 | Multicast addresses |
| `isCarrierGradeNat()` | 100.64.0.0/10 | RFC 6598 | Shared Address Space (CGN) |
| `isDocumentation()` | 192.0.2.0/24<br>198.51.100.0/24<br>203.0.113.0/24 | RFC 5737 | TEST-NET-1/2/3 documentation |
| `isBenchmarking()` | 198.18.0.0/15 | RFC 2544 | Benchmarking addresses |
| `isIetfProtocol()` | 192.0.0.0/24 | RFC 5735, RFC 6890 | IETF protocol assignments (DS-Lite, NAT64, etc.) |
| `is6to4Relay()` | 192.88.99.0/24<br>(except 192.88.99.2) | RFC 3068, RFC 7526 | 6to4 relay anycast (deprecated) |
| `isReserved()` | 240.0.0.0/4 | RFC 1112 | Reserved for future use |
| `isLimitedBroadcast()` | 255.255.255.255/32 | RFC 919 | Limited broadcast address |
| `isIanaReserved()` | 192.31.196.0/24<br>192.52.193.0/24<br>192.175.48.0/24<br>192.88.99.2/32 | RFC 7535, RFC 7450, RFC 7534, RFC 6751 | IANA Special-Purpose assignments |
| `isThisNetwork()` | 0.0.0.0/8 | RFC 1122 | "This" network addresses |
| `isPublic()` | All others | - | Public addresses |

### Use Cases

**Traffic Classification:**
```php
// Tag requests for logging or analytics. Behind a proxy or load balancer, REMOTE_ADDR is the
// proxy's address, so resolve the client IP from headers set by proxies you trust.
try {
    $clientIP = new IPv4\IPAddress($_SERVER['REMOTE_ADDR']);
} catch (\InvalidArgumentException $e) {
    // Not IPv4 (e.g. an IPv6 client)
    $clientIP = null;
}

$origin = ($clientIP !== null && $clientIP->isPrivate()) ? 'internal' : 'external';
```

**Access Control:**
```php
// isPrivate() matches all of RFC 1918, including guest Wi-Fi, VPN pools and container
// networks, so it is not an authorization check. Allow the specific networks you trust.
$trustedNetworks = [
    IPv4\Subnet::fromCidr('10.20.0.0/16'),     // Office LAN
    IPv4\Subnet::fromCidr('192.168.50.0/24'),  // Admin VPN
];

// $clientIP as resolved above; null (non-IPv4) is never trusted
$trusted = $clientIP !== null
    && \array_filter($trustedNetworks, fn($net) => $net->containsIP($clientIP)) !== [];

$accessLevel = $trusted ? 'full' : 'limited';
```

**Routing Decisions:**
```php
$destination = new IPv4\IPAddress($targetIP);

if ($destination->isPrivate()) {
    $route = 'internal-gateway';
} elseif ($destination->isPublic()) {
    $route = 'external-gateway';
} else {
    // Special handling for loopback, multicast, etc.
}
```

`isPublic()` excludes every special-purpose block, including the few IANA marks globally reachable
(192.0.0.9/32, 192.0.0.10/32, 192.31.196.0/24, 192.52.193.0/24, 192.175.48.0/24). Use
`isGloballyReachable()` when those should take the external route:

```php
$pcpAnycast = new IPv4\IPAddress('192.0.0.9');

$pcpAnycast->isPublic();             // false - inside 192.0.0.0/24 (IETF protocol assignments)
$pcpAnycast->isGloballyReachable();  // true - IANA marks 192.0.0.9/32 globally reachable
```

See [Global Reachability](advanced-features.md#global-reachability) for the full policy.

**Network Configuration Validation:**
```php
$staticIP = new IPv4\IPAddress($configuredIP);

// isReserved() (240.0.0.0/4) also covers 255.255.255.255; isThisNetwork() covers 0.0.0.0/8
if ($staticIP->isThisNetwork() || $staticIP->isLoopback() || $staticIP->isMulticast() || $staticIP->isReserved()) {
    throw new \Exception("Invalid static IP: Cannot use special-purpose address");
}
```

## Reverse DNS (ARPA Domain)

Get the reverse DNS lookup domain (in-addr.arpa format) for an IP address:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.112.203/23');

$arpaDomain = $subnet->arpaDomain();  // '203.112.168.192.in-addr.arpa'
```

**Use Cases:**
- Configuring reverse DNS zones
- PTR record setup
- DNS server configuration
- Email server configuration (SPF, reverse lookup verification)

**Note:** The ARPA domain is based on the IP address used to create the subnet, not the network address.

### Reverse Zone Origins

Get the reverse DNS zones that exactly cover a network. Zones are delegated on octet boundaries, so a prefix off a boundary splits to the next one:

```php
IPv4\Subnet::fromCidr('10.0.0.0/8')->reverseZones();       // ['10.in-addr.arpa']
IPv4\Subnet::fromCidr('192.168.1.50/24')->reverseZones();  // ['1.168.192.in-addr.arpa']
IPv4\Subnet::fromCidr('192.168.16.0/20')->reverseZones();  // 16 zones: '16.168.192.in-addr.arpa' … '31.168.192.in-addr.arpa'
IPv4\Subnet::fromCidr('192.0.2.6/31')->reverseZones();     // ['6.2.0.192.in-addr.arpa', '7.2.0.192.in-addr.arpa']
```

Names have no trailing dot; append one when writing absolute names in a zone file. A `/25`–`/31` returns one zone per address rather than the enclosing `/24`. See the [reverse DNS zone generator](examples.md#reverse-dns-zone-generator).

### Classless Delegation (RFC 2317)

Delegate a `/25`–`/32` as one child zone. The parent `/24` zone publishes a CNAME for each address pointing into the child zone:

```php
$delegation = IPv4\Subnet::fromCidr('192.0.2.64/26')->classlessDelegation();

$delegation->parentZone();    // '2.0.192.in-addr.arpa'
$delegation->childZone();     // '64-127.2.0.192.in-addr.arpa'
$delegation->cnameRecords();  // ['64.2.0.192.in-addr.arpa' => '64.64-127.2.0.192.in-addr.arpa', … 64 records]

IPv4\Subnet::fromCidr('192.0.2.64/26')->classlessDelegation('cust1')->childZone();  // 'cust1.2.0.192.in-addr.arpa'
```

See [classless reverse delegation](advanced-features.md#classless-reverse-delegation) for naming rules and the [delegation example](examples.md#classless-reverse-dns-delegation).

## Subnet Operations

### Split Network into Smaller Subnets

Divide a subnet into smaller, equal-sized subnets:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.112.0/23');

// Split into /25 subnets
$smallerNetworks = $subnet->split(25);

// Returns array of Subnet instances:
// [0] => 192.168.112.0/25
// [1] => 192.168.112.128/25
// [2] => 192.168.113.0/25
// [3] => 192.168.113.128/25

foreach ($smallerNetworks as $network) {
    echo $network->cidr() . "\n";
}
```

**Rules:**
- New prefix must be larger than (more specific than) current prefix
- Cannot split /32 (single host)
- Result is always an array of Subnet instances

**Warning:** Be cautious when splitting large subnets into very small ones, as this can generate enormous arrays and exhaust memory. For example, splitting /0 to /32 would attempt to create 4.3 billion Subnet objects. Consider the result count before splitting: a split from prefix X to prefix Y generates 2^(Y-X) subnets.

For large splits, use `splitIterator()`, which yields subnets one at a time instead of building the array:

```php
foreach (IPv4\Subnet::fromCidr('10.0.0.0/8')->splitIterator(24) as $network) {
    echo $network->cidr() . "\n";  // 65,536 /24s, one in memory at a time
}
```

To retrieve a single child directly, use `subnetAt()`:

```php
$parent = IPv4\Subnet::fromCidr('192.0.2.200/24');
echo $parent->subnetAt(26, 3)->cidr(); // 192.0.2.192/26
```

The index is zero-based, follows the order of `splitIterator()`, and must be less than `2^($newPrefix - $parentPrefix)`. The result is canonical even when the parent has host bits set. Invalid prefixes throw `\InvalidArgumentException` before indices are checked; negative or out-of-range indices throw `\OutOfRangeException`. The same prefix rules as splitting apply, so `/32` has no children.

**Practical Example - Department Allocation:**

```php
// Split a /22 into four /24 subnets for departments
$allocated = IPv4\Subnet::fromCidr('10.0.0.0/22');
$departments = $allocated->split(24);

$assignments = [
    'Engineering' => $departments[0],  // 10.0.0.0/24
    'Sales'       => $departments[1],  // 10.0.1.0/24
    'Marketing'   => $departments[2],  // 10.0.2.0/24
    'HR'          => $departments[3],  // 10.0.3.0/24
];

foreach ($assignments as $dept => $subnet) {
    echo "{$dept}: {$subnet->cidr()} ";
    echo "({$subnet->hostCount()} hosts)\n";
}
```

### Allocate Variable-Size Subnets

When departments need different sizes, `SubnetAllocator::allocate()` sizes each one from its host count and places it in the parent, avoiding reserved space:

```php
$plan = IPv4\SubnetAllocator::allocate(
    IPv4\Subnet::fromCidr('10.0.0.0/22'),
    ['Sales' => 50, 'Engineering' => 500],
    [IPv4\Subnet::fromCidr('10.0.3.0/24')],  // Reserved
);

$plan->allocations();  // ['Sales' => 10.0.2.0/26, 'Engineering' => 10.0.0.0/23]
$plan->remaining();    // [10.0.2.64/26, 10.0.2.128/25]
```

To request a block by prefix instead, such as a `/30` uplink that no host count produces, use `SubnetAllocator::allocateByPrefix()` and convert host counts with `SubnetParser::optimalPrefixForHosts()`:

```php
$plan = IPv4\SubnetAllocator::allocateByPrefix(
    IPv4\Subnet::fromCidr('10.0.0.0/22'),
    [
        'Sales'       => IPv4\SubnetParser::optimalPrefixForHosts(50),
        'Engineering' => IPv4\SubnetParser::optimalPrefixForHosts(500),
        'Uplink'      => 30,
    ],
    [IPv4\Subnet::fromCidr('10.0.3.0/24')],  // Reserved
);

$plan->allocations();  // ['Sales' => 10.0.2.0/26, 'Engineering' => 10.0.0.0/23, 'Uplink' => 10.0.2.64/30]
$plan->remaining();    // [10.0.2.68/30, 10.0.2.72/29, 10.0.2.80/28, 10.0.2.96/27, 10.0.2.128/25]
```

See [variable-size allocation](advanced-features.md#variable-size-subnet-allocation) for placement rules and failures.

## Adjacent Subnet Navigation

Navigate to neighboring subnets of the same size for sequential IP allocation and network expansion planning.

### Get Next Subnet

Get the subnet immediately following the current one (same size):

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');
$next   = $subnet->next();  // Returns Subnet for 192.168.2.0/24

echo $next->cidr();  // '192.168.2.0/24'

// Works with any network size
$smallSubnet = IPv4\Subnet::fromCidr('10.0.0.0/30');
$next        = $smallSubnet->next();  // Returns 10.0.0.4/30
```

### Get Previous Subnet

Get the subnet immediately preceding the current one:

```php
$subnet   = IPv4\Subnet::fromCidr('192.168.5.0/24');
$previous = $subnet->previous();  // Returns Subnet for 192.168.4.0/24

echo $previous->cidr();  // '192.168.4.0/24'
```

### Get Multiple Adjacent Subnets

Get multiple subnets in forward or backward direction:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.10.0/24');

// Get next 3 subnets (forward direction)
$nextSubnets = $subnet->adjacent(3);
// Returns array: [192.168.11.0/24, 192.168.12.0/24, 192.168.13.0/24]

// Get previous 3 subnets (backward direction)
$previousSubnets = $subnet->adjacent(-3);
// Returns array: [192.168.9.0/24, 192.168.8.0/24, 192.168.7.0/24]

foreach ($nextSubnets as $next) {
    echo $next->cidr() . "\n";
}
```

For large counts, use `adjacentIterator()`, which yields subnets one at a time instead of building the array:

```php
foreach (IPv4\Subnet::fromCidr('10.0.0.0/32')->adjacentIterator(1_000_000) as $host) {
    echo $host->cidr() . "\n";  // 10.0.0.1/32 through 10.15.66.64/32, one in memory at a time
}
```

### Chaining Navigation

Combine navigation methods for complex operations:

```php
$subnet = IPv4\Subnet::fromCidr('10.0.0.0/24');

// Navigate forward
$targetSubnet = $subnet->next()
                       ->next()
                       ->next();  // Results in 10.0.3.0/24

// Navigate forward and back
$back = $subnet->next()->previous();  // Back to 10.0.0.0/24
```

### Practical Use Cases

**Sequential IP Allocation:**
```php
$startSubnet = IPv4\Subnet::fromCidr('10.0.0.0/24');
$allocated = [$startSubnet];

// Allocate 10 consecutive /24 subnets
$current = $startSubnet;
for ($i = 0; $i < 9; $i++) {
    $current = $current->next();
    $allocated[] = $current;
}

// Results in: 10.0.0.0/24 through 10.0.9.0/24
```

**Network Expansion Planning:**
```php
$currentNetwork = IPv4\Subnet::fromCidr('172.16.5.0/24');

// Check if next subnet is available
$nextNetwork = $currentNetwork->next();
echo "Available for expansion: {$nextNetwork->cidr()}\n";

// Plan for future growth
$futureNetworks = $currentNetwork->adjacent(5);
echo "Future expansion space: " . \count($futureNetworks) . " /24 subnets available\n";
```

**Subnet Scanning:**
```php
$subnet = IPv4\Subnet::fromCidr('192.168.0.0/24');

// Scan current and adjacent subnets
$previousSubnet = $subnet->previous();
$nextSubnet = $subnet->next();

$scanList = [$previousSubnet, $subnet, $nextSubnet];

foreach ($scanList as $scanSubnet) {
    echo "Scanning: {$scanSubnet->cidr()}\n";
    // Perform network scan...
}
```

## Next Steps

- **[Advanced Features](advanced-features.md)** - CIDR aggregation, subnet exclusion, utilization analysis, network class information
- **[Reports](reports.md)** - Generate comprehensive network reports in multiple formats
- **[API Reference](api-reference.md)** - Complete method documentation
- **[Real-World Examples](examples.md)** - Practical patterns and use cases
