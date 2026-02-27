# Getting Started with IPv4 Subnet Calculator

This guide will help you install and start using the IPv4 Subnet Calculator library in your PHP projects.

## Table of Contents
- [Installation](#installation)
- [System Requirements](#system-requirements)
- [Quick Start](#quick-start)
- [Creating Subnet Calculators](#creating-subnet-calculators)
- [Basic Network Information](#basic-network-information)
- [Next Steps](#next-steps)

## Installation

### Using Composer (Recommended)

Install the library using Composer from the command line:

```bash
composer require markrogoyski/ipv4-subnet-calculator:5.*
```

### Alternative: Manual composer.json

Alternatively, add the library to your `composer.json` file:

```json
{
  "require": {
    "markrogoyski/ipv4-subnet-calculator": "5.*"
  }
}
```

Then install with:

```bash
php composer.phar install
```

### Autoloading

Composer will install IPv4 Subnet Calculator inside your `vendor` folder. Include the autoloader in your PHP files:

```php
require_once(__DIR__ . '/vendor/autoload.php');
```

## System Requirements

### Requirements
* **PHP 8.1+**
  * (For PHP 7.2–8.0, use [v4.4](https://github.com/markrogoyski/ipv4-subnet-calculator-php/releases/tag/v4.4.0))
  * (For PHP 5.5–7.1, use [v3.1](https://github.com/markrogoyski/ipv4-subnet-calculator-php/releases/tag/v3.1.0))
* **64-bit architecture**
  * (For 32-bit architecture, use [v4.4](https://github.com/markrogoyski/ipv4-subnet-calculator-php/releases/tag/v4.4.0))
***Composer** for dependency management

### Why 64-bit?

IPv4 addresses range from 0 to 4,294,967,295 (2³² - 1). On 64-bit PHP, these values fit
natively in signed integers. On 32-bit PHP, addresses >= 128.0.0.0 overflow, causing
incorrect calculations. All modern PHP installations are 64-bit; 32-bit builds are rare
and found only on legacy systems.

A runtime check will throw `\RuntimeException` if you attempt to use the library on 32-bit PHP.

> **Upgrading from v4?** See the [Migration Guide: v4 to v5](migration-v4-to-v5.md) for a complete list of breaking changes and step-by-step upgrade instructions.

### Legacy PHP Support

If you need support for older PHP versions:
- **PHP 7.2 through 8.0**: Use [v4.4.0](https://github.com/markrogoyski/ipv4-subnet-calculator-php/releases/tag/v4.4.0)
  ```bash
  composer require markrogoyski/ipv4-subnet-calculator:4.*
  ```
- **PHP 5.5 through 7.1**: Use [v3.1](https://github.com/markrogoyski/ipv4-subnet-calculator-php/releases/tag/v3.1.0)
  ```bash
  composer require markrogoyski/ipv4-subnet-calculator:3.*
  ```

## Quick Start

Here's a simple example to get you started immediately:

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Create a subnet for 192.168.1.0/24
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');

// Get basic network information
echo $subnet->networkAddress();        // 192.168.1.0
echo $subnet->hostCount();             // 254
echo $subnet->broadcastAddress();      // 192.168.1.255
echo $subnet->mask();                  // 255.255.255.0

// Check if an IP is in this subnet
$isInSubnet = $subnet->containsIP('192.168.1.100');  // true
```

That's it! You're now calculating subnets.

## Creating Subnet Calculators

There are multiple ways to create a `Subnet` instance depending on your input format.

### From CIDR Notation (Most Common)

The most common way to create a subnet is from CIDR notation:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.112.203/23');
```

### From IP Address and Prefix Length

Using the direct constructor:

```php
$subnet = new IPv4\Subnet('192.168.112.203', 23);
```

### From IP Address and Subnet Mask

When you have a subnet mask instead of prefix length:

```php
$subnet = IPv4\SubnetParser::fromMask('192.168.112.0', '255.255.254.0');
```

### From IP Address Range

When you know the network and broadcast addresses:

```php
$subnet = IPv4\SubnetParser::fromRange('192.168.112.0', '192.168.113.255');
```

### From Required Host Count

Calculate the smallest subnet that can accommodate a specific number of hosts:

```php
// Need space for 100 hosts? Get a /25 network with 126 usable hosts
$subnet = IPv4\SubnetParser::fromHostCount('192.168.112.0', 100);
```

### Calculate Optimal Prefix

If you just need to know what prefix size to use for a given host count:

```php
$prefix = IPv4\SubnetParser::optimalPrefixForHosts(100);  // Returns: 25
$prefix = IPv4\SubnetParser::optimalPrefixForHosts(500);  // Returns: 23

// Handles RFC 3021 special cases
$prefix = IPv4\SubnetParser::optimalPrefixForHosts(1);    // Returns: 32 (single host)
$prefix = IPv4\SubnetParser::optimalPrefixForHosts(2);    // Returns: 31 (point-to-point)
```

## Basic Network Information

Once you have a `Subnet` instance, you can retrieve various network properties. Version 5 returns **immutable value objects** for IP addresses, masks, and ranges:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.112.203/23');

// Network identification
$cidrNotation = $subnet->cidr();          // '192.168.112.203/23'
$networkSize  = $subnet->networkSize();   // 23

// Capacity information
$totalIPs     = $subnet->addressCount();  // 512
$usableHosts  = $subnet->hostCount();     // 510

// Network boundaries (return IPAddress objects)
$networkAddr  = $subnet->networkAddress();     // IPAddress: 192.168.112.0
$broadcast    = $subnet->broadcastAddress();   // IPAddress: 192.168.113.255

// Address ranges (return IPRange objects)
$ipRange      = $subnet->addressRange();   // IPRange: 192.168.112.0 - 192.168.113.255
$hostRange    = $subnet->hostRange();      // IPRange: 192.168.112.1 - 192.168.113.254
```

### Subnet Masks

```php
// Returns SubnetMask object
$subnetMask = $subnet->mask();            // SubnetMask: 255.255.254.0

// Returns WildcardMask object (Cisco ACL format)
$wildcardMask = $subnet->wildcardMask();  // WildcardMask: 0.0.1.255
```

### Original IP Address

Get information about the IP address used to create the subnet:

```php
// Returns IPAddress object
$ipAddress = $subnet->ipAddress();        // IPAddress: 192.168.112.203
```

## Common Use Cases

### Check if IP is in Subnet

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');

$isInSubnet = $subnet->containsIP('192.168.1.100');  // true
$isInSubnet = $subnet->containsIP('192.168.2.100');  // false
```

### Iterate Over All IPs

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/29');

// All IPs including network and broadcast (returns IPRange)
foreach ($subnet->addressRange() as $ip) {
    echo $ip . "\n";  // IPAddress objects, automatically converted to string
}

// Only usable host IPs (excludes network and broadcast)
foreach ($subnet->hostRange() as $ip) {
    echo $ip . "\n";  // IPAddress objects
}
```

### Get Min and Max Hosts

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');

$minHost = $subnet->minHost();  // IPAddress: 192.168.1.1
$maxHost = $subnet->maxHost();  // IPAddress: 192.168.1.254
```

## Multiple Output Formats

In version 5, IP addresses and masks are **value objects** that provide multiple format methods:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.112.203/23');

// Get the IP address as a value object
$ip = $subnet->ipAddress();

// Call format methods on the value object
$ipQuads   = $ip->asQuads();      // '192.168.112.203' (dotted decimal)
$ipArray   = $ip->asArray();      // ['192', '168', '112', '203']
$ipHex     = $ip->asHex();        // 'C0A870CB'
$ipBinary  = $ip->asBinary();     // '11000000101010000111000011001011'
$ipInteger = $ip->asInteger();    // 3232264395

// Same pattern for subnet mask, wildcard mask, network/broadcast addresses, etc.
$mask = $subnet->mask();
$maskQuads   = $mask->asQuads();    // '255.255.254.0'
$maskArray   = $mask->asArray();    // ['255', '255', '254', '0']
$maskHex     = $mask->asHex();      // 'FFFFFE00'
$maskBinary  = $mask->asBinary();   // '11111111111111111111111000000000'
$maskInteger = $mask->asInteger();  // 4294966784

// All value objects convert to strings automatically
echo $subnet->networkAddress();     // "192.168.112.0"
echo $subnet->mask();               // "255.255.254.0"
```

## Troubleshooting

### Invalid IP Address

```php
// This will throw an InvalidArgumentException
$subnet = IPv4\Subnet::fromCidr('256.1.1.1/24');  // Error: Invalid IP
```

**Solution**: Ensure all octets are between 0-255.

### Invalid CIDR Prefix

```php
// This will throw an InvalidArgumentException
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/33');  // Error: Invalid prefix
```

**Solution**: CIDR prefix must be between 0-32.

### Invalid Subnet Mask

```php
// This will throw an InvalidArgumentException
$subnet = IPv4\SubnetParser::fromMask('192.168.1.0', '255.255.255.1');  // Error: Not a valid mask
```

**Solution**: Subnet masks must be contiguous 1-bits followed by 0-bits (e.g., 255.255.255.0, not 255.255.255.1).

### Composer Not Found

If you get `command not found: composer`:

1. Install Composer: https://getcomposer.org/download/
2. Or use `php composer.phar` instead of `composer`

### Autoloader Not Working

If you get `Class 'IPv4\Subnet' not found`:

1. Ensure you've run `composer install`
2. Check that `vendor/autoload.php` exists
3. Verify the `require_once` path is correct

## Working with /0 Networks

The /0 network represents the entire IPv4 address space (0.0.0.0 to 255.255.255.255).
This is commonly used for:

- Default routes in routing tables (`0.0.0.0/0`)
- "Any IP" rules in firewall/ACL configurations
- CIDR summarization that encompasses all addresses

```php
$default = Subnet::fromCidr('0.0.0.0/0');
$default->addressCount();  // 4,294,967,296
$default->containsIP('192.168.1.1');  // true for any IP
```

**Warning**: Enumeration operations (`foreach`, `toArray()`) on /0 networks will
attempt to iterate over 4+ billion IP addresses. Use with caution.

## Next Steps

Now that you have the basics, explore more advanced features:

- **[Core Features](core-features.md)** - Network overlap detection, IP type classification, subnet operations
- **[Advanced Features](advanced-features.md)** - CIDR aggregation, subnet exclusion, utilization analysis
- **[Reports](reports.md)** - Generate formatted reports in multiple formats
- **[API Reference](api-reference.md)** - Complete method documentation
- **[Real-World Examples](examples.md)** - Practical use cases and patterns

## Additional Resources

- [Project README](../README.md) - Project overview and quick reference
- [GitHub Repository](https://github.com/markrogoyski/ipv4-subnet-calculator-php)
- [Packagist Package](https://packagist.org/packages/markrogoyski/ipv4-subnet-calculator)
- [Report Issues](https://github.com/markrogoyski/ipv4-subnet-calculator-php/issues)
