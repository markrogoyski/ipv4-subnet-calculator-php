# Migration Guide: v4 to v5

This guide helps you migrate from IPv4 Subnet Calculator v4.x to v5.0, which introduces a clean object-oriented architecture with immutable value objects.

## Table of Contents
- [What Changed](#what-changed)
- [Breaking Changes](#breaking-changes)
- [Quick Migration](#quick-migration)
- [Detailed API Changes](#detailed-api-changes)
- [Benefits of v5](#benefits-of-v5)

## What Changed

Version 5 introduces a major architectural refactoring:

### Old (v4.x)
- Single monolithic `SubnetCalculator` class (1,800+ lines)
- Methods with format suffixes: `getIPAddress()`, `getIPAddressHex()`, `getIPAddressBinary()`, etc.
- Strings and arrays for all return values

### New (v5.0)
- **Clean class hierarchy** with value objects
- **Immutable objects**: `IPAddress`, `SubnetMask`, `WildcardMask`, `IPRange`
- **Format methods on objects**: `ipAddress()->asHex()`, `ipAddress()->asBinary()`
- **Simplified method names**: `networkAddress()` instead of `getNetworkPortion()`

## Breaking Changes

| Category | v4.x | v5.0 |
|----------|------|------|
| **Main Class** | `SubnetCalculator` | `Subnet` |
| **Factory Classes** | `SubnetCalculatorFactory` | `SubnetParser` (parsing) & `Subnets` (collection ops) |
| **Simple Creation** | `SubnetCalculatorFactory::fromCidr()` | `Subnet::fromCidr()` or `new Subnet()` |
| **Method Naming** | `getNetworkPortion()` | `networkAddress()` |
| **Return Types** | Strings/arrays | Immutable value objects |
| **Format Methods** | Multiple methods per concept | Single method returning value object with format methods |
| **Classification** | `getAddressType()`, `getNetworkClass()` return strings | `addressType()`, `networkClass()` return enums |

## Quick Migration

### Creating Subnets

```php
// v4.x
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');
$subnet = new IPv4\SubnetCalculator('192.168.1.0', 24);
$subnet = IPv4\SubnetCalculatorFactory::fromMask('192.168.1.0', '255.255.255.0');

// v5.0
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');
$subnet = new IPv4\Subnet('192.168.1.0', 24);
$subnet = IPv4\SubnetParser::fromMask('192.168.1.0', '255.255.255.0');
```

### Basic Network Information

```php
// v4.x
$subnet->getIPAddress();               // '192.168.1.100'
$subnet->getNetworkSize();             // 24
$subnet->getNetworkPortion();          // '192.168.1.0'
$subnet->getBroadcastAddress();        // '192.168.1.255'
$subnet->getNumberAddressableHosts();  // 254
$subnet->getSubnetMask();              // '255.255.255.0'

// v5.0
$subnet->ipAddress();           // IPAddress: 192.168.1.100
$subnet->networkSize();         // 24
$subnet->networkAddress();      // IPAddress: 192.168.1.0
$subnet->broadcastAddress();    // IPAddress: 192.168.1.255
$subnet->hostCount();           // 254
$subnet->mask();                // SubnetMask: 255.255.255.0
```

### Multiple Formats

```php
// v4.x - Separate methods for each format
$subnet->getIPAddress();        // '192.168.1.100'
$subnet->getIPAddressHex();     // 'C0A80164'
$subnet->getIPAddressBinary();  // '11000000101010000000000101100100'
$subnet->getIPAddressInteger(); // 3232235876
$subnet->getIPAddressQuads();   // ['192', '168', '1', '100']

// v5.0 - Methods on value object
$ip = $subnet->ipAddress();
$ip->asQuads();    // '192.168.1.100'
$ip->asHex();      // 'C0A80164'
$ip->asBinary();   // '11000000101010000000000101100100'
$ip->asInteger();  // 3232235876
$ip->asArray();    // ['192', '168', '1', '100']
```

### IP Containment

```php
// v4.x
$subnet->isIPAddressInSubnet('192.168.1.100');

// v5.0
$subnet->containsIP('192.168.1.100');
```

### Ranges and Iteration

```php
// v4.x
$range = $subnet->getIPAddressRange();         // [start, end]
$hostRange = $subnet->getAddressableHostRange(); // [start, end]
foreach ($subnet->getAllIPAddresses() as $ip) { }
foreach ($subnet->getAllHostIPAddresses() as $ip) { }

// v5.0 - IPRange value objects
$range = $subnet->addressRange();      // IPRange object (iterable)
$hostRange = $subnet->hostRange();     // IPRange object (iterable)
foreach ($subnet->addressRange() as $ip) { }  // Iterate directly
foreach ($subnet->hostRange() as $ip) { }     // Iterate directly
```

### Min/Max Hosts

```php
// v4.x
$subnet->getMinHost();  // '192.168.1.1'
$subnet->getMaxHost();  // '192.168.1.254'

// v5.0
$subnet->minHost();     // IPAddress: 192.168.1.1
$subnet->maxHost();     // IPAddress: 192.168.1.254
```

### Network Operations

```php
// v4.x
$subnet->getNextSubnet();
$subnet->getPreviousSubnet();

// v5.0
$subnet->next();
$subnet->previous();
```

### Utilization

```php
// v4.x
$subnet->getUtilizationForHosts(100);
$subnet->getWastedAddresses(100);

// v5.0
$subnet->utilizationFor(100);
$subnet->wastedAddressesFor(100);
```

### CIDR Operations

```php
// v4.x
IPv4\SubnetCalculatorFactory::aggregate($subnets);
IPv4\SubnetCalculatorFactory::summarize($subnets);
IPv4\SubnetCalculatorFactory::optimalPrefixForHosts(100);

// v5.0
IPv4\Subnets::aggregate($subnets);
IPv4\Subnets::summarize($subnets);
IPv4\SubnetParser::optimalPrefixForHosts(100);
```

### Reports

```php
// v4.x
$subnet->getSubnetArrayReport();
$subnet->getSubnetJsonReport();
$subnet->getPrintableReport();
$subnet->printSubnetReport();

// v5.0
$subnet->toArray();
$subnet->toJson();
$subnet->toPrintable();
echo $subnet->toPrintable();  // echo $subnet alone prints only the CIDR
```

## SubnetCalculatorFactory Split (v5 Architecture Update)

In v5, the original `SubnetCalculatorFactory` class has been split into two focused classes for better semantic clarity:

### SubnetParser (Parsing/Creation)

Handles creating Subnet objects from various input formats:

```php
use IPv4\SubnetParser;

// From subnet mask
$subnet = SubnetParser::fromMask('192.168.1.0', '255.255.255.0');

// From IP range
$subnet = SubnetParser::fromRange('192.168.1.0', '192.168.1.255');

// From host count
$subnet = SubnetParser::fromHostCount('192.168.1.0', 100);

// Calculate optimal prefix
$prefix = SubnetParser::optimalPrefixForHosts(100);  // Returns: 25
```

### Subnets (Collection Operations)

Handles operations on arrays of Subnet objects:

```php
use IPv4\Subnet;
use IPv4\Subnets;

$subnets = [
    Subnet::fromCidr('192.168.0.0/24'),
    Subnet::fromCidr('192.168.1.0/24'),
];

// Aggregate into minimal CIDR blocks
$aggregated = Subnets::aggregate($subnets);
// Returns: [Subnet('192.168.0.0/23')]

// Summarize into single supernet
$summary = Subnets::summarize($subnets);
// Returns: Subnet('192.168.0.0/23')
```

### Migration Summary

| Old (SubnetCalculatorFactory) | New Class | Method |
|---------------------|-----------|--------|
| `SubnetCalculatorFactory::fromMask()` | `SubnetParser` | `fromMask()` |
| `SubnetCalculatorFactory::fromRange()` | `SubnetParser` | `fromRange()` |
| `SubnetCalculatorFactory::fromHostCount()` | `SubnetParser` | `fromHostCount()` |
| `SubnetCalculatorFactory::optimalPrefixForHosts()` | `SubnetParser` | `optimalPrefixForHosts()` |
| `SubnetCalculatorFactory::aggregate()` | `Subnets` | `aggregate()` |
| `SubnetCalculatorFactory::summarize()` | `Subnets` | `summarize()` |

## Detailed API Changes

### Complete Method Mapping

| v4.x Method | v5.0 Equivalent | Return Type |
|-------------|-----------------|-------------|
| `getIPAddress()` | `ipAddress()->asQuads()` or `(string) ipAddress()` | `IPAddress` |
| `getIPAddressHex()` | `ipAddress()->asHex()` | string via `IPAddress` |
| `getIPAddressBinary()` | `ipAddress()->asBinary()` | string via `IPAddress` |
| `getIPAddressInteger()` | `ipAddress()->asInteger()` | int via `IPAddress` |
| `getIPAddressQuads()` | `ipAddress()->asArray()` | array via `IPAddress` |
| `getNetworkSize()` | `networkSize()` | int |
| `getCidrNotation()` | `cidr()` | string |
| `getNetworkPortion()` | `networkAddress()->asQuads()` | `IPAddress` |
| `getNetworkPortionQuads()` | `networkAddress()->asArray()` | array via `IPAddress` |
| `getNetworkPortionHex()` | `networkAddress()->asHex()` | string via `IPAddress` |
| `getNetworkPortionBinary()` | `networkAddress()->asBinary()` | string via `IPAddress` |
| `getNetworkPortionInteger()` | `networkAddress()->asInteger()` | int via `IPAddress` |
| `getBroadcastAddress()` | `broadcastAddress()->asQuads()` | `IPAddress` |
| `getNumberIPAddresses()` | `addressCount()` | int |
| `getNumberAddressableHosts()` | `hostCount()` | int |
| `getSubnetMask()` | `mask()->asQuads()` or `(string) mask()` | `SubnetMask` |
| `getSubnetMaskHex()` | `mask()->asHex()` | string via `SubnetMask` |
| `getSubnetMaskBinary()` | `mask()->asBinary()` | string via `SubnetMask` |
| `getSubnetMaskInteger()` | `mask()->asInteger()` | int via `SubnetMask` |
| `getSubnetMaskQuads()` | `mask()->asArray()` | array via `SubnetMask` |
| `getWildcardMask()` | `wildcardMask()->asQuads()` | `WildcardMask` |
| `getWildcardMaskQuads()` | `wildcardMask()->asArray()` | array via `WildcardMask` |
| `getWildcardMaskHex()` | `wildcardMask()->asHex()` | string via `WildcardMask` |
| `getWildcardMaskBinary()` | `wildcardMask()->asBinary()` | string via `WildcardMask` |
| `getWildcardMaskInteger()` | `wildcardMask()->asInteger()` | int via `WildcardMask` |
| `getHostPortion()` | `hostPortion()->asQuads()` | `IPAddress` |
| `getHostPortionQuads()` | `hostPortion()->asArray()` | array via `IPAddress` |
| `getHostPortionHex()` | `hostPortion()->asHex()` | string via `IPAddress` |
| `getHostPortionBinary()` | `hostPortion()->asBinary()` | string via `IPAddress` |
| `getHostPortionInteger()` | `hostPortion()->asInteger()` | int via `IPAddress` |
| `getMinHost()` | `minHost()->asQuads()` | `IPAddress` |
| `getMinHostQuads()` | `minHost()->asArray()` | array via `IPAddress` |
| `getMinHostHex()` | `minHost()->asHex()` | string via `IPAddress` |
| `getMinHostBinary()` | `minHost()->asBinary()` | string via `IPAddress` |
| `getMinHostInteger()` | `minHost()->asInteger()` | int via `IPAddress` |
| `getMaxHost()` | `maxHost()->asQuads()` | `IPAddress` |
| `getMaxHostQuads()` | `maxHost()->asArray()` | array via `IPAddress` |
| `getMaxHostHex()` | `maxHost()->asHex()` | string via `IPAddress` |
| `getMaxHostBinary()` | `maxHost()->asBinary()` | string via `IPAddress` |
| `getMaxHostInteger()` | `maxHost()->asInteger()` | int via `IPAddress` |
| `getIPAddressRange()` | `addressRange()` | `IPRange` (iterable) |
| `getAddressableHostRange()` | `hostRange()` | `IPRange` (iterable) |
| `getAllIPAddresses()` | `addressRange()` | `IPRange` (iterable) |
| `getAllHostIPAddresses()` | `hostRange()` | `IPRange` (iterable) |
| `isIPAddressInSubnet($ip)` | `containsIP($ip)` | bool |
| `getNextSubnet()` | `next()` | `Subnet` |
| `getPreviousSubnet()` | `previous()` | `Subnet` |
| `getAdjacentSubnets($n)` | `adjacent($n)` | `Subnet[]` |
| `getUtilizationForHosts($n)` | `utilizationFor($n)` | float |
| `getWastedAddresses($n)` | `wastedAddressesFor($n)` | int |
| `getUsableHostPercentage()` | `usableHostPercentage()` | float |
| `getUnusableAddressCount()` | `unusableAddressCount()` | int |
| `getAddressType()` | `addressType()` | `AddressType` enum |
| `getNetworkClass()` | `networkClass()` | `NetworkClass` enum |
| `getDefaultClassMask()` | `defaultClassMask()` | ?string |
| `getDefaultClassPrefix()` | `defaultClassPrefix()` | ?int |
| `getIPv4ArpaDomain()` | `arpaDomain()` | string |
| `getSubnetArrayReport()` | `toArray()` | array |
| `getSubnetJsonReport()` | `toJson()` | string |
| `getPrintableReport()` | `toPrintable()` | string |
| `printSubnetReport()` | `echo $subnet->toPrintable();` | void |
| `new SubnetCalculator($ip, $size, $report)` | `new Subnet($ip, $size)` | `Subnet` (the `SubnetReportInterface` parameter is removed; reports are built in via `toArray()`, `toJson()` and `toPrintable()`) |

### Classification Methods

These methods keep the same name and signature in both versions:

- `isPrivate()`
- `isPublic()`
- `isLoopback()`
- `isLinkLocal()`
- `isMulticast()`
- `isCarrierGradeNat()`
- `isDocumentation()`
- `isBenchmarking()`
- `isReserved()`
- `isLimitedBroadcast()`
- `isThisNetwork()`
- `isClassful()`

v5 adds `isIetfProtocol()`, `is6to4Relay()`, and `isIanaReserved()`.

#### Address type and network class are now enums

`getAddressType()` is renamed to `addressType()` and returns an `AddressType` enum instead of a string.
`getNetworkClass()` is renamed to `networkClass()` and returns a `NetworkClass` enum. String comparisons
against the new methods do not throw; they silently evaluate to `false`:

```php
// v4.x
if ($subnet->getAddressType() === 'private') { ... }
if ($subnet->getNetworkClass() === 'C') { ... }

// v5.0 - WRONG: compares an enum to a string, always false
if ($subnet->addressType() === 'private') { ... }

// v5.0 - compare enum cases
use IPv4\AddressType;
use IPv4\NetworkClass;

if ($subnet->addressType() === AddressType::Private) { ... }
if ($subnet->networkClass() === NetworkClass::C) { ... }

// v5.0 - or use the backing string value
$type = $subnet->addressType()->value;   // 'private'
$class = $subnet->networkClass()->value; // 'C'
```

The string values are the same as v4's return values, so stored or serialized types remain compatible.

#### New special-purpose ranges

v5 recognizes five IANA special-purpose ranges that v4 classified as public. For addresses in these ranges,
`isPublic()` now returns `false`, and `addressType()` returns a new type instead of `public`:

| Range | v4 `getAddressType()` | v5 `addressType()` |
|-------|-----------------------|--------------------|
| `192.0.0.0/24` | `'public'` | `AddressType::IetfProtocol` (`'ietf-protocol'`) |
| `192.88.99.0/24` | `'public'` | `AddressType::Deprecated6to4` (`'deprecated-6to4'`) |
| `192.31.196.0/24`, `192.52.193.0/24`, `192.175.48.0/24` | `'public'` | `AddressType::IanaReserved` (`'iana-reserved'`) |

### Network Operations (Unchanged)

These methods remain the same in both versions:

- `overlaps($other)`
- `contains($other)`
- `isContainedIn($other)`
- `split($newPrefix)`
- `exclude($subnet)`
- `excludeAll($subnets)`

## Benefits of v5

### 1. Cleaner API

```php
// v4.x - Verbose
$subnet->getIPAddressHex();
$subnet->getSubnetMaskBinary();
$subnet->getWildcardMaskInteger();

// v5.0 - Discoverable
$subnet->ipAddress()->asHex();
$subnet->mask()->asBinary();
$subnet->wildcardMask()->asInteger();
```

### 2. Type Safety

```php
// v5.0 - Strong typing with value objects
function processIPAddress(IPAddress $ip) {
    // Type-safe
}

function processMask(SubnetMask $mask) {
    // Clear intent
}
```

### 3. Immutability

All value objects are immutable (readonly), preventing accidental modifications:

```php
$ip = $subnet->ipAddress();
// Cannot modify $ip - it's readonly
```

### 4. Separation of Concerns

The v4 monolithic `SubnetCalculator` is split into focused classes: value objects for addresses, masks and ranges, `SubnetParser` for creation, and `Subnets` for collection operations.

### 5. Better IDE Support

Value objects provide better autocomplete and type hints in modern IDEs.

## Need Help?

- [v5 API Reference](api-reference.md) - Complete method documentation
- [Getting Started Guide](getting-started.md) - v5 basics
- [GitHub Issues](https://github.com/markrogoyski/ipv4-subnet-calculator-php/issues) - Report problems or ask questions

## Staying on v4.x

If you cannot migrate immediately, v4.x remains available:

```bash
composer require markrogoyski/ipv4-subnet-calculator:4.*
```

However, v5.0 is recommended for all new projects.
