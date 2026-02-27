# Core Features

This document covers the fundamental operations and methods available in the IPv4 Subnet Calculator library.

## Table of Contents
- [Network Component Access](#network-component-access)
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
var_dump($subnet1->equals($subnet2));  // true
```

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
$totalIPs = count($subnet->addressRange());  // 256

// Count only usable hosts
$usableHosts = count($subnet->hostRange());  // 254

echo "Total IPs: {$totalIPs}\n";
echo "Usable hosts: {$usableHosts}\n";
```

#### Combining Iteration and Counting

```php
$subnet = IPv4\Subnet::fromCidr('10.0.0.0/28');

$range = $subnet->hostRange();

echo "Processing {" . count($range) . "} hosts:\n";

$index = 1;
foreach ($range as $ip) {
    echo "[{$index}/" . count($range) . "] Processing {$ip}\n";
    $index++;
}
```

#### IPRange String Representation

IPRange provides a readable string format:

```php
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
echo "Range contains: " . count($range) . " IPs\n";  // 10

// Iterate the range
foreach ($range as $ip) {
    echo $ip . "\n";
}

// Check if IP is in range
$isInRange = $range->contains('10.0.0.5');  // true
```

**Performance Note:** For large subnets (e.g., /16 or larger), iterating all IPs may consume significant memory and time. IPRange uses a generator for memory efficiency, but iteration itself will still process millions of addresses.

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

Check if an IP address is private (RFC 1918) or publicly routable:

```php
$private = IPv4\Subnet::fromCidr('192.168.1.100/24');
$public  = IPv4\Subnet::fromCidr('8.8.8.8/32');

$private->isPrivate();  // true - 192.168.0.0/16 is RFC 1918 private
$public->isPrivate();   // false - 8.8.8.8 is public

$private->isPublic();   // false - private addresses are not public
$public->isPublic();    // true - not in any special-purpose range
```

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
    IPv4\AddressType::Public => 'Publicly routable address',
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
| `is6to4Relay()` | 192.88.99.0/24 | RFC 3068, RFC 7526 | 6to4 relay anycast (deprecated) |
| `isReserved()` | 240.0.0.0/4 | RFC 1112 | Reserved for future use |
| `isLimitedBroadcast()` | 255.255.255.255/32 | RFC 919 | Limited broadcast address |
| `isThisNetwork()` | 0.0.0.0/8 | RFC 1122 | "This" network addresses |
| `isPublic()` | All others | - | Publicly routable addresses |

### Use Cases

**Security Validation:**
```php
$userIP = IPv4\Subnet::fromCidr($_SERVER['REMOTE_ADDR'] . '/32');

if ($userIP->isPrivate()) {
    // Internal user
    $accessLevel = 'full';
} else {
    // External user
    $accessLevel = 'limited';
}
```

**Routing Decisions:**
```php
$destination = IPv4\Subnet::fromCidr($targetIP . '/32');

if ($destination->isPrivate()) {
    $route = 'internal-gateway';
} elseif ($destination->isPublic()) {
    $route = 'external-gateway';
} else {
    // Special handling for loopback, multicast, etc.
}
```

**Network Configuration Validation:**
```php
$staticIP = IPv4\Subnet::fromCidr($configuredIP . '/32');

if ($staticIP->isLoopback() || $staticIP->isMulticast() || $staticIP->isReserved()) {
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
echo "Future expansion space: " . count($futureNetworks) . " /24 subnets available\n";
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
