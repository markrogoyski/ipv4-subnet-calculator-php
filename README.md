![IPV4 Subnet Calculator Logo](https://github.com/markrogoyski/ipv4-subnet-calculator-php/blob/master/docs/image/ipv4-subnet-calculator-logo.png?raw=true)

IPv4 Subnet Calculator (PHP)
============================

Comprehensive PHP library for IPv4 subnet calculations, network planning, and CIDR operations.

[![Coverage Status](https://coveralls.io/repos/github/markrogoyski/ipv4-subnet-calculator-php/badge.svg?branch=master)](https://coveralls.io/github/markrogoyski/ipv4-subnet-calculator-php?branch=master)
[![License](https://poser.pugx.org/markrogoyski/ipv4-subnet-calculator/license)](https://packagist.org/packages/markrogoyski/ipv4-subnet-calculator-php)
[![Latest Stable Version](https://poser.pugx.org/markrogoyski/ipv4-subnet-calculator/v)](https://packagist.org/packages/markrogoyski/ipv4-subnet-calculator)
[![Downloads](https://poser.pugx.org/markrogoyski/ipv4-subnet-calculator/downloads)](https://packagist.org/packages/markrogoyski/ipv4-subnet-calculator)

Quick Start
-----------

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Create from CIDR notation
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');

// Get network information
echo $subnet->getNetworkPortion();           // 192.168.1.0
echo $subnet->getBroadcastAddress();         // 192.168.1.255
echo $subnet->getNumberAddressableHosts();   // 254

// Check if an IP is in the subnet
$subnet->isIPAddressInSubnet('192.168.1.100');  // true
```

**[View Complete Documentation →](docs/)**

Features
--------

### Core Capabilities
 * **Network Calculations** - Subnet masks, wildcard masks, network/host portions, broadcast addresses
 * **IP Address Operations** - Range detection, type classification (private, public, loopback, multicast, etc.)
 * **Network Analysis** - Overlap detection, containment checking, conflict validation
 * **CIDR Operations** - Aggregation, supernetting, subnet splitting
 * **Capacity Planning** - Utilization analysis, optimal subnet sizing, waste calculation
 * **Advanced IPAM** - Subnet exclusion, address space carving, sequential allocation

### Output Formats
 * Multiple formats: dotted decimal, hex, binary, integer, quads array
 * Reports: JSON, associative arrays, formatted text, printed output
 * Reverse DNS: IPv4 ARPA domain generation

### Use Cases
 * Network planning and IP address management (IPAM)
 * Firewall rule validation and conflict detection
 * BGP route summarization and optimization
 * DHCP scope configuration
 * DNS reverse zone setup
 * Network automation and infrastructure-as-code

**[Complete Feature List →](docs/core-features.md)**

Installation
------------

### Using Composer (Command Line)

```bash
composer require markrogoyski/ipv4-subnet-calculator:4.*
```

### Or Add to composer.json

```json
{
  "require": {
    "markrogoyski/ipv4-subnet-calculator": "4.*"
  }
}
```

Then run:
```bash
composer install
```

### Requirements
 * **PHP 7.2+** (For PHP 5.5-7.1, use [v3.1](https://github.com/markrogoyski/ipv4-subnet-calculator-php/releases/tag/v3.1.0))

**[Detailed Installation Guide →](docs/getting-started.md)**

Usage
-----

### Creating Subnet Calculators

```php
// From CIDR notation
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');

// From subnet mask
$subnet = IPv4\SubnetCalculatorFactory::fromMask('192.168.1.0', '255.255.255.0');

// From IP range
$subnet = IPv4\SubnetCalculatorFactory::fromRange('192.168.1.0', '192.168.1.255');

// From host count requirement
$subnet = IPv4\SubnetCalculatorFactory::fromHostCount('192.168.1.0', 100);
```

**[All Creation Methods →](docs/getting-started.md#creating-subnet-calculators)**

### Basic Network Information

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

// Network properties
$subnet->getNetworkPortion();           // 192.168.112.0
$subnet->getBroadcastAddress();         // 192.168.113.255
$subnet->getNumberAddressableHosts();   // 510

// Subnet mask and wildcard
$subnet->getSubnetMask();               // 255.255.254.0
$subnet->getWildcardMask();             // 0.0.1.255 (for Cisco ACLs)

// Address ranges
$subnet->getIPAddressRange();           // [192.168.112.0, 192.168.113.255]
$subnet->getMinHost();                  // 192.168.112.1
$subnet->getMaxHost();                  // 192.168.113.254
```

**[Network Component Access →](docs/core-features.md#network-component-access)**

### Network Overlap & Conflict Detection

```php
$subnet1 = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');
$subnet2 = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.128/25');

// Check for overlaps
$subnet1->overlaps($subnet2);           // true

// Check containment
$subnet1->contains($subnet2);           // true
$subnet2->isContainedIn($subnet1);      // true

// Check if IP is in subnet
$subnet1->isIPAddressInSubnet('192.168.1.100');  // true
```

**[Overlap Detection Guide →](docs/core-features.md#network-overlap-and-containment)**

### IP Address Type Classification

```php
$private = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.1/32');
$private->isPrivate();    // true (RFC 1918)
$private->isPublic();     // false

$loopback = IPv4\SubnetCalculatorFactory::fromCidr('127.0.0.1/32');
$loopback->isLoopback();  // true
```

Supported types: private, public, loopback, link-local, multicast, carrier-grade-nat, documentation, benchmarking, reserved, and more.

**[All IP Address Types →](docs/core-features.md#ip-address-type-detection)**

### CIDR Aggregation & Route Summarization

```php
// Aggregate multiple subnets into larger blocks
$subnets = [
    IPv4\SubnetCalculatorFactory::fromCidr('192.168.0.0/24'),
    IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24'),
];
$aggregated = IPv4\SubnetCalculatorFactory::aggregate($subnets);
// Returns: [192.168.0.0/23]

// Summarize to single supernet
$summary = IPv4\SubnetCalculatorFactory::summarize($subnets);
// Returns: 192.168.0.0/23
```

**[CIDR Aggregation Guide →](docs/advanced-features.md#cidr-aggregation-and-supernetting)**

### Subnet Exclusion (IPAM)

```php
// Allocate a /24 but reserve the first /26 for infrastructure
$allocated = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');
$reserved  = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/26');

$available = $allocated->exclude($reserved);
// Returns: [192.168.1.64/26, 192.168.1.128/25]

// Exclude multiple subnets
$available = $allocated->excludeAll([$reserved1, $reserved2, $reserved3]);
```

**[Subnet Exclusion Guide →](docs/advanced-features.md#subnet-exclusion-and-difference-operations)**

### Utilization & Capacity Planning

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');

// Analyze utilization for host requirements
$utilization = $subnet->getUtilizationForHosts(100);  // 39.37%
$wasted = $subnet->getWastedAddresses(100);           // 154

// Find optimal subnet size
$optimalPrefix = IPv4\SubnetCalculatorFactory::optimalPrefixForHosts(100);
// Returns: 25 (a /25 provides 126 usable hosts)
```

**[Utilization Analysis Guide →](docs/advanced-features.md#utilization-statistics)**

### Subnet Operations

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.0/23');

// Split into smaller subnets
$smaller = $subnet->split(25);  // Split /23 into /25 subnets

// Navigate to adjacent subnets
$next = $subnet->getNextSubnet();       // 192.168.114.0/23
$prev = $subnet->getPreviousSubnet();   // 192.168.110.0/23
```

**[Subnet Operations Guide →](docs/core-features.md#subnet-operations)**

### Generate Reports

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

// Print formatted report
$subnet->printSubnetReport();

// Get as JSON
$json = $subnet->getSubnetJSONReport();

// Get as array
$array = $subnet->getSubnetArrayReport();

// Get as string
$report = $subnet->getPrintableReport();
```

**Example Report Output:**
```
192.168.112.203/23           Quads      Hex                           Binary    Integer
------------------ --------------- -------- -------------------------------- ----------
IP Address:        192.168.112.203 C0A870CB 11000000101010000111000011001011 3232264395
Subnet Mask:         255.255.254.0 FFFFFE00 11111111111111111111111000000000 4294966784
Network Portion:     192.168.112.0 C0A87000 11000000101010000111000000000000 3232264192

IP Address Type:             private
Number of IP Addresses:      512
Number of Addressable Hosts: 510
IP Address Range:            192.168.112.0 - 192.168.113.255
```

**[Complete Report Documentation →](docs/reports.md)**

Documentation
-------------

### Getting Started
- **[Installation & Setup](docs/getting-started.md)** - Get up and running quickly
- **[Creating Subnet Calculators](docs/getting-started.md#creating-subnet-calculators)** - All factory methods
- **[Basic Usage Examples](docs/getting-started.md#basic-network-information)** - Common operations

### Core Features
- **[Network Component Access](docs/core-features.md#network-component-access)** - IP addresses, masks, network/host portions
- **[Network Overlap & Containment](docs/core-features.md#network-overlap-and-containment)** - Conflict detection
- **[IP Address Type Detection](docs/core-features.md#ip-address-type-detection)** - Classify addresses (private, public, etc.)
- **[Subnet Operations](docs/core-features.md#subnet-operations)** - Split, navigate, reverse DNS
- **[Adjacent Subnet Navigation](docs/core-features.md#adjacent-subnet-navigation)** - Sequential subnet allocation

### Advanced Features
- **[Subnet Exclusion](docs/advanced-features.md#subnet-exclusion-and-difference-operations)** - IPAM and address carving
- **[CIDR Aggregation](docs/advanced-features.md#cidr-aggregation-and-supernetting)** - Route summarization
- **[Utilization Statistics](docs/advanced-features.md#utilization-statistics)** - Capacity planning
- **[Network Classes](docs/advanced-features.md#network-class-information-legacy)** - Legacy classful networking

### Reference & Examples
- **[API Reference](docs/api-reference.md)** - Complete method documentation
- **[Reports](docs/reports.md)** - All output formats and examples
- **[Real-World Examples](docs/examples.md)** - IPAM, firewall rules, BGP, DHCP, DNS, automation

### Quick Links
- **[Documentation Index](docs/)** - Complete navigation and learning paths
- **[Quick Reference](docs/README.md#quick-reference)** - Common operations at a glance

Unit Tests
----------

```bash
cd tests
phpunit
```

[![Coverage Status](https://coveralls.io/repos/github/markrogoyski/ipv4-subnet-calculator-php/badge.svg?branch=master)](https://coveralls.io/github/markrogoyski/ipv4-subnet-calculator-php?branch=master)

Standards
---------

IPv4 Subnet Calculator (PHP) conforms to the following standards:

 * PSR-1 - Basic coding standard (http://www.php-fig.org/psr/psr-1/)
 * PSR-4 - Autoloader (http://www.php-fig.org/psr/psr-4/)
 * PSR-12 - Extended coding style guide (http://www.php-fig.org/psr/psr-12/)

License
-------

IPv4 Subnet Calculator (PHP) is licensed under the MIT License.
