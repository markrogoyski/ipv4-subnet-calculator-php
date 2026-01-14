# IPv4 Subnet Calculator Documentation

Welcome to the comprehensive documentation for the IPv4 Subnet Calculator PHP library. This documentation provides everything you need to effectively use the library for network planning, IP address management, and subnet calculations.

## Quick Navigation

### For New Users
- **[Getting Started](getting-started.md)** - Installation, setup, and your first subnet calculations
- **[Quick Reference](#quick-reference)** - Common operations at a glance

### Feature Documentation
- **[Core Features](core-features.md)** - Essential network calculations and operations
- **[Advanced Features](advanced-features.md)** - CIDR aggregation, subnet exclusion, utilization analysis
- **[Reports](reports.md)** - Generate comprehensive network reports

### Reference & Examples
- **[API Reference](api-reference.md)** - Complete method documentation
- **[Real-World Examples](examples.md)** - Practical patterns and use cases

## Learning Path

### Beginner Path
New to the library? Follow this path:

1. **[Getting Started](getting-started.md)** - Learn installation and basic usage
2. **[Core Features - Network Component Access](core-features.md#network-component-access)** - Understand IP addresses, masks, and network portions
3. **[Core Features - Check if IP is in Subnet](core-features.md#check-if-ip-is-in-subnet)** - Basic subnet operations
4. **[Reports](reports.md)** - Generate your first network report

### Intermediate Path
Already familiar with basics? Explore these topics:

1. **[Core Features - Network Overlap and Containment](core-features.md#network-overlap-and-containment)** - Detect network conflicts
2. **[Core Features - IP Address Type Detection](core-features.md#ip-address-type-detection)** - Classify IP addresses
3. **[Core Features - Subnet Operations](core-features.md#subnet-operations)** - Split and navigate subnets
4. **[Examples - Firewall and Security](examples.md#firewall-and-security)** - Apply to real scenarios

### Advanced Path
Ready for complex operations? Master these:

1. **[Advanced Features - Subnet Exclusion](advanced-features.md#subnet-exclusion-and-difference-operations)** - IPAM and address carving
2. **[Advanced Features - CIDR Aggregation](advanced-features.md#cidr-aggregation-and-supernetting)** - Route summarization
3. **[Advanced Features - Utilization Statistics](advanced-features.md#utilization-statistics)** - Capacity planning
4. **[Examples - Network Planning and IPAM](examples.md#network-planning-and-ipam)** - Build complete solutions

## Quick Reference

### Creating a Subnet Calculator

```php
// From CIDR notation (most common)
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');

// From IP and subnet mask
$subnet = IPv4\SubnetCalculatorFactory::fromMask('192.168.1.0', '255.255.255.0');

// From IP range
$subnet = IPv4\SubnetCalculatorFactory::fromRange('192.168.1.0', '192.168.1.255');

// From host count requirement
$subnet = IPv4\SubnetCalculatorFactory::fromHostCount('192.168.1.0', 100);
```

### Getting Network Information

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');

$subnet->getNetworkPortion();           // '192.168.1.0'
$subnet->getBroadcastAddress();         // '192.168.1.255'
$subnet->getSubnetMask();               // '255.255.255.0'
$subnet->getNumberAddressableHosts();   // 254
$subnet->getIPAddressRange();           // ['192.168.1.0', '192.168.1.255']
```

### Common Operations

```php
// Check if IP is in subnet
$subnet->isIPAddressInSubnet('192.168.1.100');  // true

// Check for overlaps
$subnet1->overlaps($subnet2);

// Split into smaller subnets
$smaller = $subnet->split(26);  // Split /24 into /26 subnets

// Get adjacent subnets
$next = $subnet->getNextSubnet();
$prev = $subnet->getPreviousSubnet();

// Check IP type
$subnet->isPrivate();   // true for RFC 1918 addresses
$subnet->isPublic();    // true for publicly routable addresses
```

### Advanced Operations

```php
// Exclude subnets (IPAM)
$remaining = $allocated->exclude($reserved);

// Aggregate routes (BGP)
$summary = IPv4\SubnetCalculatorFactory::aggregate($subnets);

// Utilization analysis
$utilization = $subnet->getUtilizationForHosts(100);
$wasted = $subnet->getWastedAddresses(100);
```

### Generate Reports

```php
// Print to console
$subnet->printSubnetReport();

// Get as JSON
$json = $subnet->getSubnetJsonReport();

// Get as array
$array = $subnet->getSubnetArrayReport();
```

## Documentation by Use Case

### Network Planning & IPAM
- [Getting Started - From Required Host Count](getting-started.md#from-required-host-count)
- [Core Features - Subnet Operations](core-features.md#subnet-operations)
- [Advanced Features - Subnet Exclusion](advanced-features.md#subnet-exclusion-and-difference-operations)
- [Advanced Features - Utilization Statistics](advanced-features.md#utilization-statistics)
- [Examples - Network Planning and IPAM](examples.md#network-planning-and-ipam)

### Firewall & Security
- [Core Features - Network Overlap and Containment](core-features.md#network-overlap-and-containment)
- [Core Features - IP Address Type Detection](core-features.md#ip-address-type-detection)
- [Examples - Firewall and Security](examples.md#firewall-and-security)

### BGP & Routing
- [Advanced Features - CIDR Aggregation](advanced-features.md#cidr-aggregation-and-supernetting)
- [Core Features - Adjacent Subnet Navigation](core-features.md#adjacent-subnet-navigation)
- [Examples - BGP Route Summarization](examples.md#bgp-route-summarization)

### DHCP Configuration
- [Getting Started - From Required Host Count](getting-started.md#from-required-host-count)
- [Advanced Features - Utilization Statistics](advanced-features.md#utilization-statistics)
- [Examples - DHCP Scope Planning](examples.md#dhcp-scope-planning)

### DNS Configuration
- [Core Features - Reverse DNS](core-features.md#reverse-dns-arpa-domain)
- [Examples - DNS Configuration](examples.md#dns-configuration)

### Network Automation
- [Reports](reports.md)
- [Examples - Network Automation](examples.md#network-automation)

## Feature Matrix

| Feature | Core | Advanced | Examples |
|---------|:----:|:--------:|:--------:|
| IP Address & Masks | ✓ | | |
| Network/Host Portions | ✓ | | |
| IP in Subnet Check | ✓ | | ✓ |
| Network Overlap Detection | ✓ | | ✓ |
| IP Type Classification | ✓ | | ✓ |
| Subnet Splitting | ✓ | | |
| Adjacent Subnet Navigation | ✓ | | |
| Subnet Exclusion (IPAM) | | ✓ | ✓ |
| CIDR Aggregation | | ✓ | ✓ |
| Utilization Analysis | | ✓ | ✓ |
| Network Class Info | | ✓ | |
| Report Generation | ✓ | | ✓ |

## Searching the Documentation

### Finding Methods
All public methods are documented in the [API Reference](api-reference.md), organized by category:
- Factory Methods
- Network Information
- IP Address Operations
- Network Operations
- Type Detection
- Navigation
- Reports

### Finding Examples
Practical examples are organized by use case in [Real-World Examples](examples.md):
- Network Planning & IPAM
- Firewall & Security
- BGP Route Summarization
- DHCP Scope Planning
- DNS Configuration
- Network Automation

### Finding Specific Topics

**IP Addresses:**
- [Core Features - IP Address](core-features.md#ip-address)
- [API Reference - IP Address Methods](api-reference.md#ip-address-methods)

**Subnet Masks:**
- [Core Features - Subnet Mask](core-features.md#subnet-mask)
- [Core Features - Wildcard Mask](core-features.md#wildcard-mask)

**Network Calculations:**
- [Core Features - Network Component Access](core-features.md#network-component-access)
- [Getting Started - Basic Network Information](getting-started.md#basic-network-information)

**Overlaps & Conflicts:**
- [Core Features - Network Overlap and Containment](core-features.md#network-overlap-and-containment)
- [Examples - Firewall Rule Validation](examples.md#firewall-rule-validation)

**IPAM Operations:**
- [Advanced Features - Subnet Exclusion](advanced-features.md#subnet-exclusion-and-difference-operations)
- [Examples - Progressive IPAM](examples.md#progressive-ipam-with-allocation-tracking)

**Route Optimization:**
- [Advanced Features - CIDR Aggregation](advanced-features.md#cidr-aggregation-and-supernetting)
- [Examples - BGP Route Optimization](examples.md#multi-region-route-aggregation)

## RFC References

The library implements standards and best practices from various RFCs:

- **RFC 1918** - Private Address Space
- **RFC 1122** - Host Requirements (Loopback, This Network)
- **RFC 919** - Broadcasting
- **RFC 3927** - Link-Local Addresses
- **RFC 5771** - Multicast
- **RFC 6598** - Carrier-Grade NAT
- **RFC 5737** - Documentation Addresses (TEST-NET)
- **RFC 2544** - Benchmarking
- **RFC 1112** - Reserved Addresses
- **RFC 3021** - Point-to-Point (/31) Networks
- **RFC 4632** - CIDR (Classless Inter-Domain Routing)

See [Core Features - IP Address Type Detection](core-features.md#ip-address-type-detection) for detailed RFC compliance information.

## Version Support

- **Current Version:** Requires PHP 7.2+
- **Legacy Support:** PHP 5.5-7.1 users should use [v3.1](https://github.com/markrogoyski/ipv4-subnet-calculator-php/releases/tag/v3.1.0)

## Contributing to Documentation

Found an issue or have a suggestion for improving the documentation?

- [Report Documentation Issues](https://github.com/markrogoyski/ipv4-subnet-calculator-php/issues)
- [Project Repository](https://github.com/markrogoyski/ipv4-subnet-calculator-php)

## External Resources

- [Main README](../README.md) - Project overview and quick reference
- [GitHub Repository](https://github.com/markrogoyski/ipv4-subnet-calculator-php)
- [Packagist Package](https://packagist.org/packages/markrogoyski/ipv4-subnet-calculator)
- [IPv4 Address Space](https://www.iana.org/assignments/ipv4-address-space/)
- [CIDR Notation](https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing)

---

## Documentation Index

### Complete Documentation Set

1. **[Getting Started](getting-started.md)**
   - Installation
   - System Requirements
   - Quick Start
   - Creating Subnet Calculators
   - Basic Network Information
   - Troubleshooting

2. **[Core Features](core-features.md)**
   - Network Component Access (IP, Masks, Portions)
   - Network Overlap and Containment
   - IP Address Type Detection
   - Reverse DNS (ARPA Domain)
   - Subnet Operations (Split)
   - Adjacent Subnet Navigation

3. **[Advanced Features](advanced-features.md)**
   - Subnet Exclusion and Difference Operations
   - CIDR Aggregation and Supernetting
   - Utilization Statistics
   - Network Class Information (Legacy)

4. **[Reports](reports.md)**
   - Printed Report
   - Array Report
   - JSON Report
   - String Report
   - Standard Interfaces
   - Use Cases

5. **[API Reference](api-reference.md)**
   - Complete method documentation
   - Organized by category
   - Quick reference by use case

6. **[Real-World Examples](examples.md)**
   - Network Planning and IPAM
   - Firewall and Security
   - BGP Route Summarization
   - DHCP Scope Planning
   - DNS Configuration
   - Network Automation

---

**Ready to get started?** Begin with [Getting Started](getting-started.md) →
