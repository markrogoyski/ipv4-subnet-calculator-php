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
composer require markrogoyski/ipv4-subnet-calculator:4.*
```

### Alternative: Manual composer.json

Alternatively, add the library to your `composer.json` file:

```json
{
  "require": {
    "markrogoyski/ipv4-subnet-calculator": "4.*"
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

- **PHP 7.2 or higher**
- **Composer** for dependency management

### Legacy PHP Support

If you need support for older PHP versions:
- **PHP 5.5 through 7.1**: Use [v3.1](https://github.com/markrogoyski/ipv4-subnet-calculator-php/releases/tag/v3.1.0)
  ```bash
  composer require markrogoyski/ipv4-subnet-calculator:3.*
  ```

## Quick Start

Here's a simple example to get you started immediately:

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Create a subnet calculator for 192.168.1.0/24
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');

// Get basic network information
echo $subnet->getNetworkPortion();           // 192.168.1.0
echo $subnet->getNumberAddressableHosts();   // 254
echo $subnet->getBroadcastAddress();         // 192.168.1.255
echo $subnet->getSubnetMask();               // 255.255.255.0

// Check if an IP is in this subnet
$isInSubnet = $subnet->isIPAddressInSubnet('192.168.1.100');  // true
```

That's it! You're now calculating subnets.

## Creating Subnet Calculators

There are multiple ways to create a `SubnetCalculator` instance depending on your input format.

### From CIDR Notation (Most Common)

The most common way to create a subnet calculator is from CIDR notation:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');
```

### From IP Address and Prefix Length

Using the direct constructor:

```php
$subnet = new IPv4\SubnetCalculator('192.168.112.203', 23);
```

### From IP Address and Subnet Mask

When you have a subnet mask instead of prefix length:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromMask('192.168.112.0', '255.255.254.0');
```

### From IP Address Range

When you know the network and broadcast addresses:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromRange('192.168.112.0', '192.168.113.255');
```

### From Required Host Count

Calculate the smallest subnet that can accommodate a specific number of hosts:

```php
// Need space for 100 hosts? Get a /25 network with 126 usable hosts
$subnet = IPv4\SubnetCalculatorFactory::fromHostCount('192.168.112.0', 100);
```

### Calculate Optimal Prefix

If you just need to know what prefix size to use for a given host count:

```php
$prefix = IPv4\SubnetCalculatorFactory::optimalPrefixForHosts(100);  // Returns: 25
$prefix = IPv4\SubnetCalculatorFactory::optimalPrefixForHosts(500);  // Returns: 23

// Handles RFC 3021 special cases
$prefix = IPv4\SubnetCalculatorFactory::optimalPrefixForHosts(1);    // Returns: 32 (single host)
$prefix = IPv4\SubnetCalculatorFactory::optimalPrefixForHosts(2);    // Returns: 31 (point-to-point)
```

## Basic Network Information

Once you have a `SubnetCalculator` instance, you can retrieve various network properties:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

// Network identification
$cidrNotation = $subnet->getCidrNotation();    // '192.168.112.203/23'
$networkSize  = $subnet->getNetworkSize();     // 23

// Capacity information
$totalIPs     = $subnet->getNumberIPAddresses();      // 512
$usableHosts  = $subnet->getNumberAddressableHosts(); // 510

// Network boundaries
$networkAddr  = $subnet->getNetworkPortion();         // '192.168.112.0'
$broadcast    = $subnet->getBroadcastAddress();       // '192.168.113.255'

// Address ranges
$ipRange      = $subnet->getIPAddressRange();         // ['192.168.112.0', '192.168.113.255']
$hostRange    = $subnet->getAddressableHostRange();   // ['192.168.112.1', '192.168.113.254']
```

### Subnet Masks

```php
$subnetMask = $subnet->getSubnetMask();        // '255.255.254.0'
$wildcardMask = $subnet->getWildcardMask();    // '0.0.1.255' (Cisco ACL format)
```

### Original IP Address

Get information about the IP address used to create the subnet:

```php
$ipAddress = $subnet->getIPAddress();          // '192.168.112.203'
```

## Common Use Cases

### Check if IP is in Subnet

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');

$isInSubnet = $subnet->isIPAddressInSubnet('192.168.1.100');  // true
$isInSubnet = $subnet->isIPAddressInSubnet('192.168.2.100');  // false
```

### Iterate Over All IPs

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/29');

// All IPs including network and broadcast
foreach ($subnet->getAllIPAddresses() as $ip) {
    echo $ip . "\n";
}

// Only usable host IPs (excludes network and broadcast)
foreach ($subnet->getAllHostIPAddresses() as $ip) {
    echo $ip . "\n";
}
```

### Get Min and Max Hosts

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');

$minHost = $subnet->getMinHost();  // '192.168.1.1'
$maxHost = $subnet->getMaxHost();  // '192.168.1.254'
```

## Multiple Output Formats

Most methods that return IP addresses, masks, and network portions are available in multiple formats:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

// IP Address in different formats
$ipQuads   = $subnet->getIPAddress();        // '192.168.112.203' (dotted decimal)
$ipArray   = $subnet->getIPAddressQuads();   // [192, 168, 112, 203]
$ipHex     = $subnet->getIPAddressHex();     // 'C0A870CB'
$ipBinary  = $subnet->getIPAddressBinary();  // '11000000101010000111000011001011'
$ipInteger = $subnet->getIPAddressInteger(); // 3232264395

// Same pattern for subnet mask, wildcard mask, network portion, host portion, etc.
$maskQuads   = $subnet->getSubnetMaskQuads();    // [255, 255, 254, 0]
$maskHex     = $subnet->getSubnetMaskHex();      // 'FFFFFE00'
$maskBinary  = $subnet->getSubnetMaskBinary();   // '11111111111111111111111000000000'
$maskInteger = $subnet->getSubnetMaskInteger();  // 4294966784
```

## Troubleshooting

### Invalid IP Address

```php
// This will throw an exception
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('256.1.1.1/24');  // Error: Invalid IP
```

**Solution**: Ensure all octets are between 0-255.

### Invalid CIDR Prefix

```php
// This will throw an exception
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/33');  // Error: Invalid prefix
```

**Solution**: CIDR prefix must be between 0-32.

### Invalid Subnet Mask

```php
// This will throw an exception
$subnet = IPv4\SubnetCalculatorFactory::fromMask('192.168.1.0', '255.255.255.1');  // Error: Not a valid mask
```

**Solution**: Subnet masks must be contiguous 1-bits followed by 0-bits (e.g., 255.255.255.0, not 255.255.255.1).

### Composer Not Found

If you get `command not found: composer`:

1. Install Composer: https://getcomposer.org/download/
2. Or use `php composer.phar` instead of `composer`

### Autoloader Not Working

If you get `Class 'IPv4\SubnetCalculator' not found`:

1. Ensure you've run `composer install`
2. Check that `vendor/autoload.php` exists
3. Verify the `require_once` path is correct

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
- [Packagist Package](https://packagist.org/packages/markrogoyski/ipv4-subnet-calculator-php)
- [Report Issues](https://github.com/markrogoyski/ipv4-subnet-calculator-php/issues)
