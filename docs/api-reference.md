# API Reference

Complete reference of all public methods available in the IPv4 Subnet Calculator library.

## Table of Contents
- [Factory Methods](#factory-methods)
- [Network Information](#network-information)
- [IP Address Methods](#ip-address-methods)
- [Subnet Mask Methods](#subnet-mask-methods)
- [Wildcard Mask Methods](#wildcard-mask-methods)
- [Network Portion Methods](#network-portion-methods)
- [Host Portion Methods](#host-portion-methods)
- [Min/Max Host Methods](#minmax-host-methods)
- [IP Address Operations](#ip-address-operations)
- [Network Operations](#network-operations)
- [Type Detection Methods](#type-detection-methods)
- [Network Class Methods](#network-class-methods)
- [Utilization Methods](#utilization-methods)
- [Navigation Methods](#navigation-methods)
- [Report Generation](#report-generation)

---

## Factory Methods

Static methods on `SubnetCalculatorFactory` for creating SubnetCalculator instances.

### fromCidr()
```php
IPv4\SubnetCalculatorFactory::fromCidr(string $cidr): SubnetCalculator
```
Create from CIDR notation string (e.g., '192.168.1.0/24').

**See:** [Getting Started - Creating Subnet Calculators](getting-started.md#from-cidr-notation-most-common)

### fromMask()
```php
IPv4\SubnetCalculatorFactory::fromMask(string $ipAddress, string $subnetMask): SubnetCalculator
```
Create from IP address and subnet mask (e.g., '192.168.1.0', '255.255.255.0').

**See:** [Getting Started - From IP Address and Subnet Mask](getting-started.md#from-ip-address-and-subnet-mask)

### fromRange()
```php
IPv4\SubnetCalculatorFactory::fromRange(string $networkAddress, string $broadcastAddress): SubnetCalculator
```
Create from network and broadcast address range.

**See:** [Getting Started - From IP Address Range](getting-started.md#from-ip-address-range)

### fromHostCount()
```php
IPv4\SubnetCalculatorFactory::fromHostCount(string $ipAddress, int $requiredHosts): SubnetCalculator
```
Create the smallest subnet that can accommodate the required number of hosts.

**See:** [Getting Started - From Required Host Count](getting-started.md#from-required-host-count)

### optimalPrefixForHosts()
```php
IPv4\SubnetCalculatorFactory::optimalPrefixForHosts(int $requiredHosts): int
```
Calculate the optimal CIDR prefix (network size) for a given number of hosts. Returns just the prefix number without creating a SubnetCalculator instance.

**See:** [Getting Started - Calculate Optimal Prefix](getting-started.md#calculate-optimal-prefix-for-host-count)

### aggregate()
```php
IPv4\SubnetCalculatorFactory::aggregate(array $subnets): SubnetCalculator[]
```
Combine contiguous subnets into the minimal set of larger CIDR blocks.

**Parameters:**
- `$subnets` - Array of SubnetCalculator instances

**Returns:** Array of SubnetCalculator instances

**See:** [Advanced Features - CIDR Aggregation](advanced-features.md#aggregate-multiple-subnets)

### summarize()
```php
IPv4\SubnetCalculatorFactory::summarize(array $subnets): SubnetCalculator
```
Find the smallest single CIDR block that contains all input subnets.

**Parameters:**
- `$subnets` - Array of SubnetCalculator instances

**Returns:** Single SubnetCalculator instance

**See:** [Advanced Features - Summarize to Single Supernet](advanced-features.md#summarize-to-single-supernet)

---

## Network Information

Core network properties and metadata.

### getCidrNotation()
```php
getCidrNotation(): string
```
Get CIDR notation (e.g., '192.168.112.203/23').

### getNetworkSize()
```php
getNetworkSize(): int
```
Get the network prefix length (0-32).

### getNumberIPAddresses()
```php
getNumberIPAddresses(): int
```
Get total number of IP addresses in the subnet (including network and broadcast).

### getNumberAddressableHosts()
```php
getNumberAddressableHosts(): int
```
Get number of usable host addresses (excludes network and broadcast, except for /31 and /32).

**See:** [Core Features - Network Component Access](core-features.md#network-component-access)

### getIPAddressRange()
```php
getIPAddressRange(): array
```
Get network and broadcast addresses as array: `[networkAddress, broadcastAddress]`.

### getAddressableHostRange()
```php
getAddressableHostRange(): array
```
Get first and last usable host addresses as array: `[minHost, maxHost]`.

### getBroadcastAddress()
```php
getBroadcastAddress(): string
```
Get broadcast address in dotted decimal format.

---

## IP Address Methods

Access the IP address used to create the subnet in multiple formats.

### getIPAddress()
```php
getIPAddress(): string
```
Get IP address in dotted decimal format (e.g., '192.168.112.203').

### getIPAddressQuads()
```php
getIPAddressQuads(): array
```
Get IP address as array of octets (e.g., `[192, 168, 112, 203]`).

### getIPAddressHex()
```php
getIPAddressHex(): string
```
Get IP address in hexadecimal format (e.g., 'C0A870CB').

### getIPAddressBinary()
```php
getIPAddressBinary(): string
```
Get IP address in binary format (e.g., '11000000101010000111000011001011').

### getIPAddressInteger()
```php
getIPAddressInteger(): int
```
Get IP address as integer (e.g., 3232264395).

**See:** [Core Features - IP Address](core-features.md#ip-address)

---

## Subnet Mask Methods

Access subnet mask in multiple formats.

### getSubnetMask()
```php
getSubnetMask(): string
```
Get subnet mask in dotted decimal format (e.g., '255.255.254.0').

### getSubnetMaskQuads()
```php
getSubnetMaskQuads(): array
```
Get subnet mask as array of octets (e.g., `[255, 255, 254, 0]`).

### getSubnetMaskHex()
```php
getSubnetMaskHex(): string
```
Get subnet mask in hexadecimal format (e.g., 'FFFFFE00').

### getSubnetMaskBinary()
```php
getSubnetMaskBinary(): string
```
Get subnet mask in binary format (e.g., '11111111111111111111111000000000').

### getSubnetMaskInteger()
```php
getSubnetMaskInteger(): int
```
Get subnet mask as integer (e.g., 4294966784).

**See:** [Core Features - Subnet Mask](core-features.md#subnet-mask)

---

## Wildcard Mask Methods

Access wildcard mask (inverse of subnet mask) in multiple formats.

### getWildcardMask()
```php
getWildcardMask(): string
```
Get wildcard mask in dotted decimal format (e.g., '0.0.1.255').

### getWildcardMaskQuads()
```php
getWildcardMaskQuads(): array
```
Get wildcard mask as array of octets (e.g., `[0, 0, 1, 255]`).

### getWildcardMaskHex()
```php
getWildcardMaskHex(): string
```
Get wildcard mask in hexadecimal format (e.g., '000001FF').

### getWildcardMaskBinary()
```php
getWildcardMaskBinary(): string
```
Get wildcard mask in binary format (e.g., '00000000000000000000000111111111').

### getWildcardMaskInteger()
```php
getWildcardMaskInteger(): int
```
Get wildcard mask as integer (e.g., 511).

**See:** [Core Features - Wildcard Mask](core-features.md#wildcard-mask)

---

## Network Portion Methods

Access network portion (IP address with host bits zeroed) in multiple formats.

### getNetworkPortion()
```php
getNetworkPortion(): string
```
Get network portion in dotted decimal format (e.g., '192.168.112.0').

### getNetworkPortionQuads()
```php
getNetworkPortionQuads(): array
```
Get network portion as array of octets (e.g., `[192, 168, 112, 0]`).

### getNetworkPortionHex()
```php
getNetworkPortionHex(): string
```
Get network portion in hexadecimal format (e.g., 'C0A87000').

### getNetworkPortionBinary()
```php
getNetworkPortionBinary(): string
```
Get network portion in binary format (e.g., '11000000101010000111000000000000').

### getNetworkPortionInteger()
```php
getNetworkPortionInteger(): int
```
Get network portion as integer (e.g., 3232264192).

**See:** [Core Features - Network Portion](core-features.md#network-portion)

---

## Host Portion Methods

Access host portion (IP address with network bits zeroed) in multiple formats.

### getHostPortion()
```php
getHostPortion(): string
```
Get host portion in dotted decimal format (e.g., '0.0.0.203').

### getHostPortionQuads()
```php
getHostPortionQuads(): array
```
Get host portion as array of octets (e.g., `[0, 0, 0, 203]`).

### getHostPortionHex()
```php
getHostPortionHex(): string
```
Get host portion in hexadecimal format (e.g., '000000CB').

### getHostPortionBinary()
```php
getHostPortionBinary(): string
```
Get host portion in binary format (e.g., '00000000000000000000000011001011').

### getHostPortionInteger()
```php
getHostPortionInteger(): int
```
Get host portion as integer (e.g., 203).

**See:** [Core Features - Host Portion](core-features.md#host-portion)

---

## Min/Max Host Methods

Access first and last usable host addresses in multiple formats.

### getMinHost()
```php
getMinHost(): string
```
Get first usable host address in dotted decimal format.

### getMinHostQuads()
```php
getMinHostQuads(): array
```
Get first usable host address as array of octets.

### getMinHostHex()
```php
getMinHostHex(): string
```
Get first usable host address in hexadecimal format.

### getMinHostBinary()
```php
getMinHostBinary(): string
```
Get first usable host address in binary format.

### getMinHostInteger()
```php
getMinHostInteger(): int
```
Get first usable host address as integer.

### getMaxHost()
```php
getMaxHost(): string
```
Get last usable host address in dotted decimal format.

### getMaxHostQuads()
```php
getMaxHostQuads(): array
```
Get last usable host address as array of octets.

### getMaxHostHex()
```php
getMaxHostHex(): string
```
Get last usable host address in hexadecimal format.

### getMaxHostBinary()
```php
getMaxHostBinary(): string
```
Get last usable host address in binary format.

### getMaxHostInteger()
```php
getMaxHostInteger(): int
```
Get last usable host address as integer.

**See:** [Core Features - Minimum and Maximum Host](core-features.md#minimum-and-maximum-host)

---

## IP Address Operations

Operations involving individual IP addresses.

### getAllIPAddresses()
```php
getAllIPAddresses(): \Generator
```
Get generator that yields all IP addresses in subnet (including network and broadcast).

**Returns:** Generator yielding string IP addresses

### getAllHostIPAddresses()
```php
getAllHostIPAddresses(): \Generator
```
Get generator that yields all usable host IP addresses (excludes network and broadcast).

**Returns:** Generator yielding string IP addresses

**See:** [Core Features - Iterate All IP Addresses](core-features.md#iterate-all-ip-addresses)

### isIPAddressInSubnet()
```php
isIPAddressInSubnet(string $ipAddress): bool
```
Check if an IP address is within this subnet.

**Parameters:**
- `$ipAddress` - IP address to check

**Returns:** true if IP is in subnet, false otherwise

**See:** [Core Features - Check if IP is in Subnet](core-features.md#check-if-ip-is-in-subnet)

---

## Network Operations

Operations involving other subnets.

### overlaps()
```php
overlaps(SubnetCalculator $other): bool
```
Check if this subnet overlaps with another subnet.

**Parameters:**
- `$other` - Another SubnetCalculator instance

**Returns:** true if subnets share any IP addresses

**See:** [Core Features - Check if Two Subnets Overlap](core-features.md#check-if-two-subnets-overlap)

### contains()
```php
contains(SubnetCalculator $other): bool
```
Check if this subnet completely contains another subnet.

**Parameters:**
- `$other` - Another SubnetCalculator instance

**Returns:** true if this subnet contains all IPs of the other subnet

**See:** [Core Features - Check if One Subnet Contains Another](core-features.md#check-if-one-subnet-contains-another)

### isContainedIn()
```php
isContainedIn(SubnetCalculator $other): bool
```
Check if this subnet is completely contained within another subnet.

**Parameters:**
- `$other` - Another SubnetCalculator instance

**Returns:** true if all IPs of this subnet are within the other subnet

**See:** [Core Features - Check if Subnet is Contained Within Another](core-features.md#check-if-subnet-is-contained-within-another)

### exclude()
```php
exclude(SubnetCalculator $excluded): SubnetCalculator[]
```
Exclude a subnet from this subnet, returning the remaining address space.

**Parameters:**
- `$excluded` - SubnetCalculator to exclude

**Returns:** Array of SubnetCalculator instances representing remaining space

**See:** [Advanced Features - Exclude a Single Subnet](advanced-features.md#exclude-a-single-subnet)

### excludeAll()
```php
excludeAll(array $excluded): SubnetCalculator[]
```
Exclude multiple subnets from this subnet, returning the remaining address space.

**Parameters:**
- `$excluded` - Array of SubnetCalculator instances to exclude

**Returns:** Array of SubnetCalculator instances representing remaining space

**See:** [Advanced Features - Exclude Multiple Subnets](advanced-features.md#exclude-multiple-subnets)

### split()
```php
split(int $newPrefix): SubnetCalculator[]
```
Split this subnet into smaller subnets with the specified prefix length.

**Parameters:**
- `$newPrefix` - New prefix length (must be larger/more specific than current)

**Returns:** Array of SubnetCalculator instances

**See:** [Core Features - Split Network into Smaller Subnets](core-features.md#split-network-into-smaller-subnets)

---

## Type Detection Methods

Determine the type of IP address according to IANA and RFC classifications.

### isPrivate()
```php
isPrivate(): bool
```
Check if IP is in private address space (RFC 1918: 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16).

### isPublic()
```php
isPublic(): bool
```
Check if IP is publicly routable (not in any special-purpose range).

### isLoopback()
```php
isLoopback(): bool
```
Check if IP is loopback address (RFC 1122: 127.0.0.0/8).

### isLinkLocal()
```php
isLinkLocal(): bool
```
Check if IP is link-local/APIPA (RFC 3927: 169.254.0.0/16).

### isMulticast()
```php
isMulticast(): bool
```
Check if IP is multicast (RFC 5771: 224.0.0.0/4).

### isCarrierGradeNat()
```php
isCarrierGradeNat(): bool
```
Check if IP is in Carrier-Grade NAT range (RFC 6598: 100.64.0.0/10).

### isDocumentation()
```php
isDocumentation(): bool
```
Check if IP is documentation/TEST-NET range (RFC 5737: 192.0.2.0/24, 198.51.100.0/24, 203.0.113.0/24).

### isBenchmarking()
```php
isBenchmarking(): bool
```
Check if IP is benchmarking range (RFC 2544: 198.18.0.0/15).

### isReserved()
```php
isReserved(): bool
```
Check if IP is reserved for future use (RFC 1112: 240.0.0.0/4).

### isLimitedBroadcast()
```php
isLimitedBroadcast(): bool
```
Check if IP is limited broadcast address (RFC 919: 255.255.255.255/32).

### isThisNetwork()
```php
isThisNetwork(): bool
```
Check if IP is "this" network (RFC 1122: 0.0.0.0/8).

### getAddressType()
```php
getAddressType(): string
```
Get string representation of address type classification.

**Returns:** One of: 'private', 'public', 'loopback', 'link-local', 'multicast', 'carrier-grade-nat', 'documentation', 'benchmarking', 'reserved', 'limited-broadcast', 'this-network'

**See:** [Core Features - IP Address Type Detection](core-features.md#ip-address-type-detection)

---

## Network Class Methods

Legacy classful networking information (Class A, B, C, D, E).

### getNetworkClass()
```php
getNetworkClass(): string
```
Get legacy network class (A, B, C, D, or E).

**Returns:** 'A', 'B', 'C', 'D', or 'E'

### getDefaultClassMask()
```php
getDefaultClassMask(): string
```
Get default subnet mask for the network's class.

**Returns:** Default mask (e.g., '255.0.0.0' for Class A)

### getDefaultClassPrefix()
```php
getDefaultClassPrefix(): int
```
Get default prefix length for the network's class.

**Returns:** Default prefix (e.g., 8 for Class A)

### isClassful()
```php
isClassful(): bool
```
Check if subnet uses its default classful mask.

**Returns:** true if using natural class boundary, false if subnetted/supernetted

**See:** [Advanced Features - Network Class Information](advanced-features.md#network-class-information-legacy)

---

## Utilization Methods

Analyze subnet efficiency and capacity planning.

### getUsableHostPercentage()
```php
getUsableHostPercentage(): float
```
Get percentage of total IP addresses that are usable as hosts.

**Returns:** Percentage (0-100)

### getUnusableAddressCount()
```php
getUnusableAddressCount(): int
```
Get count of unusable addresses (network + broadcast).

**Returns:** Count of unusable addresses (0 for /31 and /32)

### getUtilizationForHosts()
```php
getUtilizationForHosts(int $requiredHosts): float
```
Calculate utilization percentage for a specific host requirement.

**Parameters:**
- `$requiredHosts` - Number of hosts needed

**Returns:** Utilization percentage (can exceed 100 if insufficient capacity)

### getWastedAddresses()
```php
getWastedAddresses(int $requiredHosts): int
```
Calculate wasted addresses for a specific host requirement.

**Parameters:**
- `$requiredHosts` - Number of hosts needed

**Returns:** Number of wasted addresses (negative if insufficient capacity)

**See:** [Advanced Features - Utilization Statistics](advanced-features.md#utilization-statistics)

---

## Navigation Methods

Navigate to adjacent subnets of the same size.

### getNextSubnet()
```php
getNextSubnet(): SubnetCalculator
```
Get the subnet immediately following this one (same prefix length).

**Returns:** SubnetCalculator for next subnet

### getPreviousSubnet()
```php
getPreviousSubnet(): SubnetCalculator
```
Get the subnet immediately preceding this one (same prefix length).

**Returns:** SubnetCalculator for previous subnet

### getAdjacentSubnets()
```php
getAdjacentSubnets(int $count): SubnetCalculator[]
```
Get multiple adjacent subnets.

**Parameters:**
- `$count` - Number of subnets to get (positive for forward, negative for backward)

**Returns:** Array of SubnetCalculator instances

**See:** [Core Features - Adjacent Subnet Navigation](core-features.md#adjacent-subnet-navigation)

---

## Report Generation

Generate comprehensive network reports in multiple formats.

### printSubnetReport()
```php
printSubnetReport(): void
```
Print formatted subnet report to STDOUT.

### getSubnetArrayReport()
```php
getSubnetArrayReport(): array
```
Get subnet information as associative array.

**Returns:** Associative array with complete subnet information

### getSubnetJsonReport()
```php
getSubnetJsonReport(): string
```
Get subnet information as JSON string.

**Returns:** JSON string

### getPrintableReport()
```php
getPrintableReport(): string
```
Get formatted subnet report as string.

**Returns:** Formatted string report

### getIPv4ArpaDomain()
```php
getIPv4ArpaDomain(): string
```
Get reverse DNS lookup domain (in-addr.arpa format).

**Returns:** ARPA domain string (e.g., '203.112.168.192.in-addr.arpa')

**See:** [Reports](reports.md)

---

## Standard PHP Interfaces

### __toString()
```php
__toString(): string
```
Get string representation of subnet (formatted report).

**Returns:** Same as `getPrintableReport()`

### jsonSerialize()
```php
jsonSerialize(): array
```
Serialize to JSON (implements JsonSerializable).

**Returns:** Same as `getSubnetArrayReport()`

**See:** [Reports - Standard Interfaces](reports.md#standard-interfaces)

---

## Quick Reference by Use Case

### Creating Subnets
- `fromCidr()` - From CIDR notation
- `fromMask()` - From subnet mask
- `fromRange()` - From IP range
- `fromHostCount()` - From host requirements

### Basic Information
- `getCidrNotation()` - Get CIDR string
- `getNumberAddressableHosts()` - Host capacity
- `getBroadcastAddress()` - Broadcast address
- `getIPAddressRange()` - Network range

### Network Planning
- `split()` - Divide into smaller subnets
- `aggregate()` - Combine into larger subnets
- `exclude()` / `excludeAll()` - Remove subnets from allocation

### Capacity Analysis
- `getUtilizationForHosts()` - Efficiency for host count
- `getWastedAddresses()` - Waste calculation
- `optimalPrefixForHosts()` - Find best size

### Conflict Detection
- `overlaps()` - Check for overlaps
- `contains()` - Check containment
- `isIPAddressInSubnet()` - Check IP membership

### Documentation
- `printSubnetReport()` - Print to console
- `getSubnetJsonReport()` - Export to JSON
- `getSubnetArrayReport()` - Get as array

---

## Related Documentation

- **[Getting Started](getting-started.md)** - Installation and basic usage
- **[Core Features](core-features.md)** - Detailed feature documentation
- **[Advanced Features](advanced-features.md)** - Advanced operations
- **[Reports](reports.md)** - Report generation
- **[Real-World Examples](examples.md)** - Practical use cases
