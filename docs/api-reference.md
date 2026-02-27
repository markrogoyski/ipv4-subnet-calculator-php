# API Reference - Version 5.0

Complete reference of all public methods and classes in the IPv4 Subnet Calculator library v5.0.

## Table of Contents
- [Main Classes](#main-classes)
- [Subnet Class](#subnet-class)
- [SubnetParser Class](#subnetparser-class)
- [Subnets Class](#subnets-class)
- [Value Object Classes](#value-object-classes)
  - [IPAddress](#ipaddress-class)
  - [SubnetMask](#subnetmask-class)
  - [WildcardMask](#wildcardmask-class)
  - [IPRange](#iprange-class)
- [Enum Classes](#enum-classes)

---

## Main Classes

### Overview

v5.0 introduces a clean object-oriented architecture:

- **`Subnet`** - Main class for subnet operations
- **`SubnetParser`** - Factory for creating subnets from various input formats
- **`Subnets`** - Collection operations on arrays of Subnet objects
- **`IPAddress`** - Immutable IP address value object
- **`SubnetMask`** - Immutable subnet mask value object
- **`WildcardMask`** - Immutable wildcard mask value object
- **`IPRange`** - Immutable IP range value object (iterable)

All value objects implement the `Stringable` interface and provide format methods.

---

## Subnet Class

`IPv4\Subnet` - Main entry point for subnet calculations. Immutable, provides access to all subnet information through value objects.

### Construction

#### __construct()
```php
new Subnet(string $ipAddress, int $networkSize): Subnet
```
Create a subnet from IP address and prefix length.

**Parameters:**
- `$ipAddress` - IP address in dotted decimal format
- `$networkSize` - CIDR prefix (0-32)

**Example:**
```php
$subnet = new Subnet('192.168.1.100', 24);
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
$subnet = Subnet::fromCidr('192.168.1.0/24');
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

All classification methods delegate to the `ipAddress()` object.

#### isPrivate()
```php
isPrivate(): bool
```
Check if the IP is in private address space (RFC 1918).

#### isPublic()
```php
isPublic(): bool
```
Check if the IP is a public address.

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

This range is deprecated as of RFC 7526.

**Links:** RFC 3068, RFC 7526

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

**Example:**
```php
$utilization = $subnet->utilizationFor(100);  // 39.37% for /24
```

#### wastedAddressesFor()
```php
wastedAddressesFor(int $requiredHosts): int
```
Calculate wasted addresses for a given host requirement.

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

**Example:**
```php
$smaller = $subnet->split(26);  // Split /24 into four /26 subnets
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

#### exclude()
```php
exclude(Subnet $subnet): Subnet[]
```
Exclude a subnet, returning the remaining address space.

**Example:**
```php
$remaining = $allocated->exclude($reserved);
```

#### excludeAll()
```php
excludeAll(array $subnets): Subnet[]
```
Exclude multiple subnets, returning the remaining address space.

### Navigation

#### next()
```php
next(): Subnet
```
Get the next adjacent subnet of the same size.

**Example:**
```php
$next = $subnet->next();  // 192.168.2.0/24
```

#### previous()
```php
previous(): Subnet
```
Get the previous adjacent subnet of the same size.

#### adjacent()
```php
adjacent(int $count): Subnet[]
```
Get adjacent subnets. Positive count for forward, negative for backward.

**Example:**
```php
$forward = $subnet->adjacent(3);   // Next 3 subnets
$backward = $subnet->adjacent(-3); // Previous 3 subnets
```

### Membership

#### containsIP()
```php
containsIP(string|IPAddress $ip): bool
```
Check if an IP address is within this subnet.

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

### Output

#### arpaDomain()
```php
arpaDomain(): string
```
Get the reverse DNS ARPA domain for the input IP address.

**Example:**
```php
$arpa = $subnet->arpaDomain();  // "1.168.192.in-addr.arpa"
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
String representation in CIDR notation (e.g., "192.168.1.100/24"). Use `toPrintable()` for detailed formatted reports.

**Example:**
```php
echo $subnet;  // "192.168.1.100/24"
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
SubnetParser::fromMask(string $ipAddress, string $subnetMask): Subnet
```
Create subnet from IP address and subnet mask string.

**Example:**
```php
$subnet = SubnetParser::fromMask('192.168.1.0', '255.255.255.0');
```

#### fromRange()
```php
SubnetParser::fromRange(string $startIp, string $endIp): Subnet
```
Create subnet from IP range (must be valid CIDR block).

**Example:**
```php
$subnet = SubnetParser::fromRange('192.168.1.0', '192.168.1.255');
```

#### fromHostCount()
```php
SubnetParser::fromHostCount(string $ipAddress, int $hostCount): Subnet
```
Create smallest subnet that accommodates the required host count.

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

---

## Value Object Classes

All value objects are immutable (readonly) and implement `Stringable`.

### IPAddress Class

`IPv4\IPAddress` - Immutable IPv4 address value object.

#### Construction

```php
new IPAddress(string $ipAddress): IPAddress
```

**Example:**
```php
$ip = new IPAddress('192.168.1.100');
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

Same as IPAddress: `asQuads()`, `asArray()`, `asHex()`, `asBinary()`, `asInteger()`, `__toString()`

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

Same as IPAddress: `asQuads()`, `asArray()`, `asHex()`, `asBinary()`, `asInteger()`, `__toString()`

---

### IPRange Class

`IPv4\IPRange` - Immutable IP range value object. Implements `IteratorAggregate`, `Countable`, and `Stringable`.

**Interface Support:**
- **`IteratorAggregate`** - Use `foreach` to iterate over all IP addresses in the range
- **`Countable`** - Use PHP's `count()` function to get the number of IPs
- **`Stringable`** - Automatically converts to "start - end" format

#### Construction

```php
new IPRange(IPAddress $start, IPAddress $end): IPRange
```

**Example:**
```php
$range = new IPRange(
    new IPAddress('192.168.1.0'),
    new IPAddress('192.168.1.255')
);
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

// Using PHP's count() function (recommended)
$count = count($range);  // 256
```

##### contains()
```php
contains(string|IPAddress $ip): bool
```
Check if an IP is within the range.

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

##### __toString()
```php
__toString(): string
```
Returns range as "start - end" format.

**Example:** `"192.168.1.0 - 192.168.1.255"`

---

## Enum Classes

### AddressType

`IPv4\AddressType` - Enum for IP address types.

**Values:**
- `AddressType::Private` - RFC 1918 private addresses
- `AddressType::Public` - Publicly routable addresses
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
- `AddressType::ThisNetwork` - 0.0.0.0/8 "this" network

### NetworkClass

`IPv4\NetworkClass` - Enum for classful network classes.

**Values:**
- `NetworkClass::A`
- `NetworkClass::B`
- `NetworkClass::C`
- `NetworkClass::D`
- `NetworkClass::E`

---

## Migration from v4.x

See [Migration Guide](migration-v4-to-v5.md) for detailed information on migrating from version 4.x to 5.0.

## Additional Resources

- [Getting Started](getting-started.md) - Basic usage and examples
- [Core Features](core-features.md) - Common operations
- [Advanced Features](advanced-features.md) - Complex operations
- [Examples](examples.md) - Real-world use cases
