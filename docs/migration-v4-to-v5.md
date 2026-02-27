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
$subnet->getIPAddressQuads();   // [192, 168, 1, 100]

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
echo $subnet;  // Automatically converts to string
```

## SubnetFactory Split (v5 Architecture Update)

In v5, the original `SubnetFactory` class has been split into two focused classes for better semantic clarity:

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

| Old (SubnetFactory) | New Class | Method |
|---------------------|-----------|--------|
| `SubnetFactory::fromMask()` | `SubnetParser` | `fromMask()` |
| `SubnetFactory::fromRange()` | `SubnetParser` | `fromRange()` |
| `SubnetFactory::fromHostCount()` | `SubnetParser` | `fromHostCount()` |
| `SubnetFactory::optimalPrefixForHosts()` | `SubnetParser` | `optimalPrefixForHosts()` |
| `SubnetFactory::aggregate()` | `Subnets` | `aggregate()` |
| `SubnetFactory::summarize()` | `Subnets` | `summarize()` |

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
| `getBroadcastAddress()` | `broadcastAddress()->asQuads()` | `IPAddress` |
| `getNumberIPAddresses()` | `addressCount()` | int |
| `getNumberAddressableHosts()` | `hostCount()` | int |
| `getSubnetMask()` | `mask()->asQuads()` or `(string) mask()` | `SubnetMask` |
| `getSubnetMaskHex()` | `mask()->asHex()` | string via `SubnetMask` |
| `getSubnetMaskBinary()` | `mask()->asBinary()` | string via `SubnetMask` |
| `getSubnetMaskInteger()` | `mask()->asInteger()` | int via `SubnetMask` |
| `getSubnetMaskQuads()` | `mask()->asArray()` | array via `SubnetMask` |
| `getWildcardMask()` | `wildcardMask()->asQuads()` | `WildcardMask` |
| `getHostPortion()` | `hostPortion()->asQuads()` | `IPAddress` |
| `getMinHost()` | `minHost()->asQuads()` | `IPAddress` |
| `getMaxHost()` | `maxHost()->asQuads()` | `IPAddress` |
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
| `getSubnetArrayReport()` | `toArray()` | array |
| `getSubnetJsonReport()` | `toJson()` | string |
| `getPrintableReport()` | `toPrintable()` | string |
| `printSubnetReport()` | `echo $subnet;` | void |

### Classification Methods (Unchanged)

These methods remain the same in both versions:

- `isPrivate()`
- `isPublic()`
- `isLoopback()`
- `isLinkLocal()`
- `isMulticast()`
- `isCarrierGradeNat()`
- `isDocumentation()`
- `isBenchmarking()`
- `isReserved()`
- `addressType()`

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

### 4. Smaller Codebase

- v4.x: ~2,370 lines (SubnetCalculator + Factory + Report)
- v5.0: ~1,105 lines (distributed across specialized classes)
- 53% reduction in code size
- Better separation of concerns

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
