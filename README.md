![IPV4 Subnet Calculator Logo](https://github.com/markrogoyski/ipv4-subnet-calculator-php/blob/master/docs/image/ipv4-subnet-calculator-logo.png?raw=true)

IPv4 Subnet Calculator (PHP)
============================

Network calculator for subnet mask and other classless (CIDR) network information.

[![Coverage Status](https://coveralls.io/repos/github/markrogoyski/ipv4-subnet-calculator-php/badge.svg?branch=master)](https://coveralls.io/github/markrogoyski/ipv4-subnet-calculator-php?branch=master)
[![License](https://poser.pugx.org/markrogoyski/math-php/license)](https://packagist.org/packages/markrogoyski/ipv4-subnet-calculator-php)

Features
--------
Given an IP address and CIDR network size, it calculates the network information and provides all-in-one aggregated reports.

Includes flexible factory methods for creating subnet calculators from various input formats (CIDR notation, subnet mask, IP range, or required host count).

### Use Cases
 * Network planning and IP address allocation (IPAM)
 * Firewall and routing configuration validation
 * Network overlap and conflict detection
 * DHCP scope planning
 * DNS reverse zone configuration
 * Network automation and infrastructure-as-code

### Calculations
 * IP address
 * Network size
 * Subnet mask
 * Network portion
 * Host portion
 * Number of IP addresses in the network
 * Number of addressable hosts in the network
 * IP address range
 * Broadcast address
 * Min and max host
 * All IP addresses
 * Is an IP address in the subnet
 * Network overlap and conflict detection
   * Check if subnets overlap
   * Check if one subnet contains another
   * Check if subnet is contained within another
 * IP address range type detection
   * Private, public, loopback, link-local, multicast
   * Carrier-grade NAT, documentation, benchmarking
   * Reserved, limited broadcast, "this" network
   * RFC-compliant classification
 * IPv4 ARPA domain

Provides each data in dotted quads, hexadecimal, and binary formats, as well as array of quads.

### Aggregated Network Calculation Reports
 * Associative array
 * JSON
 * String
 * Printed to STDOUT

### Standard Interfaces
 * JsonSerializable

Setup
-----

 Add the library to your `composer.json` file in your project:

```javascript
{
  "require": {
      "markrogoyski/ipv4-subnet-calculator": "4.*"
  }
}
```

Use [composer](http://getcomposer.org) to install the library:

```bash
$ php composer.phar install
```

Composer will install IPv4 Subnet Calculator inside your vendor folder. Then you can add the following to your
.php files to the use library with Autoloading.

```php
require_once(__DIR__ . '/vendor/autoload.php');
```

Alternatively, use composer on the command line to require and install IPv4 SubnetCalculator:

```
$ php composer.phar require markrogoyski/ipv4-subnet-calculator:4.*
```

### Minimum Requirements
 * PHP 7.2

 Note: For PHP 5.5 through 7.1, use v3.0 (`markrogoyski/ipv4-subnet-calculator:3.*`)

Usage
-----

### Create New SubnetCalculator

#### Direct Constructor
```php
// For network 192.168.112.203/23
$sub = new IPv4\SubnetCalculator('192.168.112.203', 23);
```

#### Using Factory Methods

##### From CIDR Notation
```php
// Create from CIDR notation string
$sub = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');
```

##### From Subnet Mask
```php
// Create from IP address and subnet mask
$sub = IPv4\SubnetCalculatorFactory::fromMask('192.168.112.0', '255.255.254.0');
```

##### From IP Range
```php
// Create from IP address range (network and broadcast addresses)
$sub = IPv4\SubnetCalculatorFactory::fromRange('192.168.112.0', '192.168.113.255');
```

##### From Required Host Count
```php
// Create the smallest subnet that can accommodate the required number of hosts
$sub = IPv4\SubnetCalculatorFactory::fromHostCount('192.168.112.0', 100);  // Returns a /25 network with 126 usable hosts
```

### Various Network Information
```php
$cidrNotation         = $sub->getCidrNotation();           // 192.168.112.203/23
$numberIpAddresses    = $sub->getNumberIPAddresses();      // 512
$numberHosts          = $sub->getNumberAddressableHosts(); // 510
$addressRange         = $sub->getIPAddressRange();         // [192.168.112.0, 192.168.113.255]
$addressableHostRange = $sub->getAddressableHostRange();   // [192.168.112.1, 192.168.113.254]
$networkSize          = $sub->getNetworkSize();            // 23
$broadcastAddress     = $sub->getBroadcastAddress();       // 192.168.113.255
```

### IP Address
```php
$ipAddress        = $sub->getIPAddress();        // 192.168.112.203
$ipAddressQuads   = $sub->getIPAddressQuads();   // [192, 168, 112, 203]
$ipAddressHex     = $sub->getIPAddressHex();     // C0A870CB
$ipAddressBinary  = $sub->getIPAddressBinary();  // 11000000101010000111000011001011
$ipAddressInteger = $sub->getIPAddressInteger(); // 3232264395;
```

### Subnet Mask
```php
$subnetMask        = $sub->getSubnetMask();        // 255.255.254.0
$subnetMaskQuads   = $sub->getSubnetMaskQuads();   // [255, 255, 254, 0]
$subnetMaskHex     = $sub->getSubnetMaskHex();     // FFFFFE00
$subnetMaskBinary  = $sub->getSubnetMaskBinary();  // 11111111111111111111111000000000
$subnetMaskInteger = $sub->getSubnetMaskInteger(); // 4294966784
```

### Network Portion
```php
$network        = $sub->getNetworkPortion();        // 192.168.112.0
$networkQuads   = $sub->getNetworkPortionQuads();   // [192, 168, 112, 0]
$networkHex     = $sub->getNetworkPortionHex();     // C0A87000
$networkBinary  = $sub->getNetworkPortionBinary();  // 11000000101010000111000000000000
$networkInteger = $sub->getNetworkPortionInteger(); // 3232264192
```

### Host Portion
```php
$host        = $sub->getHostPortion();        // 0.0.0.203
$hostQuads   = $sub->getHostPortionQuads();   // [0, 0, 0, 203]
$hostHex     = $sub->getHostPortionHex();     // 000000CB
$hostBinary  = $sub->getHostPortionBinary();  // 00000000000000000000000011001011
$hostInteger = $sub->getHostPortionInteger(); // 203
```

### Min and Max Host
```php
$minHost        = $sub->getMinHost();        // 192.168.112.1
$minHostQuads   = $sub->getMinHostQuads();   // [192, 168, 112, 1]
$minHostHex     = $sub->getMinHostHex();     // C0A87001
$minHostBinary  = $sub->getMinHostBinary();  // 11000000101010000111000000000001
$minHostInteger = $sub->getMinHostInteger(); // 3232264193

$maxHost        = $sub->getMaxHost();        // 192.168.113.254
$maxHostQuads   = $sub->getMaxHostQuads();   // [192, 168, 113, 254]
$maxHostHex     = $sub->getMaxHostHex();     // C0A871FE
$maxHostBinary  = $sub->getMaxHostBinary();  // 11000000101010000111000111111110
$maxHostInteger = $sub->getMaxHostInteger(); // 3232264702
```

### All IP Addresses
```php
foreach ($sub->getAllIPAddresses() as $ipAddress) {
    echo $ipAddress;
}

foreach ($sub->getAllHostIPAddresses() as $hostAddress) {
    echo $hostAddress;
}
```

### Is IP Address in Subnet
```php
$boolTrue  = $sub->isIPAddressInSubnet('192.168.112.5');
$boolFalse = $sub->isIPAddressInSubnet('192.168.111.5');
```

### Network Overlap and Conflict Detection

Useful for network planning, firewall rule validation, and routing table conflict detection.

#### Check if Two Subnets Overlap
```php
$subnet1 = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');
$subnet2 = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.128/25');

$overlaps = $subnet1->overlaps($subnet2);  // true - they share IP addresses
```

#### Check if One Subnet Contains Another
```php
$large = IPv4\SubnetCalculatorFactory::fromCidr('10.0.0.0/8');
$small = IPv4\SubnetCalculatorFactory::fromCidr('10.1.2.0/24');

$contains = $large->contains($small);  // true - 10.0.0.0/8 contains 10.1.2.0/24
```

#### Check if Subnet is Contained Within Another
```php
$small = IPv4\SubnetCalculatorFactory::fromCidr('172.16.0.0/16');
$large = IPv4\SubnetCalculatorFactory::fromCidr('172.16.0.0/12');

$isContained = $small->isContainedIn($large);  // true - 172.16.0.0/16 is within 172.16.0.0/12
```

### IP Address Range Type Detection

Useful for security validation, routing decisions, and network classification. All methods comply with IANA IPv4 Special-Purpose Address Registry and relevant RFCs.

#### Check if IP is Private (RFC 1918)
```php
$private = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.100/24');
$public  = IPv4\SubnetCalculatorFactory::fromCidr('8.8.8.8/32');

$private->isPrivate();  // true - 192.168.0.0/16 is RFC 1918 private
$public->isPrivate();   // false - 8.8.8.8 is public
```

#### Check if IP is Publicly Routable
```php
$ip = IPv4\SubnetCalculatorFactory::fromCidr('1.1.1.1/32');

$ip->isPublic();  // true - not in any special-purpose range
```

#### Check Special-Purpose Ranges
```php
$ip = IPv4\SubnetCalculatorFactory::fromCidr('127.0.0.1/32');

$ip->isLoopback();         // true - 127.0.0.0/8
$ip->isLinkLocal();        // false
$ip->isMulticast();        // false
$ip->isCarrierGradeNat();  // false - CGN is 100.64.0.0/10
$ip->isDocumentation();    // false - TEST-NET ranges
$ip->isBenchmarking();     // false - 198.18.0.0/15
$ip->isReserved();         // false - 240.0.0.0/4
$ip->isLimitedBroadcast(); // false - only 255.255.255.255
$ip->isThisNetwork();      // false - 0.0.0.0/8
```

#### Get Address Type Classification
```php
$classifications = [
    '192.168.1.1'    => 'private',
    '8.8.8.8'        => 'public',
    '127.0.0.1'      => 'loopback',
    '169.254.1.1'    => 'link-local',
    '224.0.0.1'      => 'multicast',
    '100.64.0.1'     => 'carrier-grade-nat',
    '192.0.2.1'      => 'documentation',
    '198.18.0.1'     => 'benchmarking',
    '240.0.0.1'      => 'reserved',
    '255.255.255.255'=> 'limited-broadcast',
    '0.0.0.1'        => 'this-network',
];

foreach ($classifications as $ip => $expectedType) {
    $subnet = IPv4\SubnetCalculatorFactory::fromCidr("{$ip}/32");
    echo $subnet->getAddressType();  // Returns the expected type string
}
```

#### Supported Range Types

| Method | Range | RFC | Description |
|--------|-------|-----|-------------|
| `isPrivate()` | 10.0.0.0/8<br>172.16.0.0/12<br>192.168.0.0/16 | RFC 1918 | Private network addresses |
| `isLoopback()` | 127.0.0.0/8 | RFC 1122 | Loopback addresses |
| `isLinkLocal()` | 169.254.0.0/16 | RFC 3927 | Link-local/APIPA addresses |
| `isMulticast()` | 224.0.0.0/4 | RFC 5771 | Multicast addresses |
| `isCarrierGradeNat()` | 100.64.0.0/10 | RFC 6598 | Shared Address Space (CGN) |
| `isDocumentation()` | 192.0.2.0/24<br>198.51.100.0/24<br>203.0.113.0/24 | RFC 5737 | TEST-NET-1/2/3 documentation |
| `isBenchmarking()` | 198.18.0.0/15 | RFC 2544 | Benchmarking addresses |
| `isReserved()` | 240.0.0.0/4 | RFC 1112 | Reserved for future use |
| `isLimitedBroadcast()` | 255.255.255.255/32 | RFC 919 | Limited broadcast address |
| `isThisNetwork()` | 0.0.0.0/8 | RFC 1122 | "This" network addresses |
| `isPublic()` | All others | - | Publicly routable addresses |

### Reverse DNS Lookup (ARPA Domain)
```php
$ipv4ArpaDomain = $sub->getIPv4ArpaDomain(); // 203.112.168.192.in-addr.arpa
```

### Split the Network into Smaller Networks
```php
$sub             = new IPv4\SubnetCalculator('192.168.112.203', 23);
$smallerNetworks = $sub->split(25);  // Array of SubnetCalculators [192.168.112.0/25, 192.168.112.128/25, 192.168.113.0/25, 192.168.113.128/25
```

### Reports

#### Printed Report
```php
$sub->printSubnetReport();
/*
192.168.112.203/23           Quads      Hex                           Binary    Integer
------------------ --------------- -------- -------------------------------- ----------
IP Address:        192.168.112.203 C0A870CB 11000000101010000111000011001011 3232264395
Subnet Mask:         255.255.254.0 FFFFFE00 11111111111111111111111000000000 4294966784
Network Portion:     192.168.112.0 C0A87000 11000000101010000111000000000000 3232264192
Host Portion:            0.0.0.203 000000CB 00000000000000000000000011001011        203

Number of IP Addresses:      512
Number of Addressable Hosts: 510
IP Address Range:            192.168.112.0 - 192.168.113.255
Broadcast Address:           192.168.113.255
Min Host:                    192.168.112.1
Max Host:                    192.168.113.254
IPv4 ARPA Domain:            203.112.168.192.in-addr.arpa
*/
```

#### Array Report
```php
$sub->getSubnetArrayReport();
/*
(
    [ip_address_with_network_size] => 192.168.112.203/23
    [ip_address] => Array
        (
            [quads] => 192.168.112.203
            [hex] => C0A870CB
            [binary] => 11000000101010000111000011001011
            [integer] => 3232264395
        )

    [subnet_mask] => Array
        (
            [quads] => 255.255.254.0
            [hex] => FFFFFE00
            [binary] => 11111111111111111111111000000000
            [integer] => 4294966784
        )

    [network_portion] => Array
        (
            [quads] => 192.168.112.0
            [hex] => C0A87000
            [binary] => 11000000101010000111000000000000
            [integer] => 3232264192
        )

    [host_portion] => Array
        (
            [quads] => 0.0.0.203
            [hex] => 000000CB
            [binary] => 00000000000000000000000011001011
            [integer] => 203
        )

    [network_size] => 23
    [number_of_ip_addresses] => 512
    [number_of_addressable_hosts] => 510
    [ip_address_range] => Array
        (
            [0] => 192.168.112.0
            [1] => 192.168.113.255
        )

    [broadcast_address] => 192.168.113.255
    [min_host] => 192.168.112.1
    [max_host] => 192.168.113.254
    [ipv4_arpa_domain] => 203.112.168.192.in-addr.arpa
)
*/
```

#### JSON Report
```php
$sub->getSubnetJSONReport();
/*
{
    "ip_address_with_network_size": "192.168.112.203\/23",
    "ip_address": {
        "quads": "192.168.112.203",
        "hex": "C0A870CB",
        "binary": "11000000101010000111000011001011",
        "integer": 3232264395
    },
    "subnet_mask": {
        "quads": "255.255.254.0",
        "hex": "FFFFFE00",
        "binary": "11111111111111111111111000000000",
        "integer": 4294966784
    },
    "network_portion": {
        "quads": "192.168.112.0",
        "hex": "C0A87000",
        "binary": "11000000101010000111000000000000",
        "integer": 3232264192
    },
    "host_portion": {
        "quads": "0.0.0.203",
        "hex": "000000CB",
        "binary": "00000000000000000000000011001011",
        "integer": 203
    },
    "network_size": 23,
    "number_of_ip_addresses": 512,
    "number_of_addressable_hosts": 510,
    "ip_address_range": [
        "192.168.112.0",
        "192.168.113.255"
    ],
    "broadcast_address": "192.168.113.255",
    "min_host": "192.168.112.1",
    "max_host": "192.168.113.254",
    "ipv4_arpa_domain": "203.112.168.192.in-addr.arpa"
}
*/
```

#### String Report
```php
$stringReport = $sub->getPrintableReport();
/*
192.168.112.203/23           Quads      Hex                           Binary    Integer
------------------ --------------- -------- -------------------------------- ----------
IP Address:        192.168.112.203 C0A870CB 11000000101010000111000011001011 3232264395
Subnet Mask:         255.255.254.0 FFFFFE00 11111111111111111111111000000000 4294966784
Network Portion:     192.168.112.0 C0A87000 11000000101010000111000000000000 3232264192
Host Portion:            0.0.0.203 000000CB 00000000000000000000000011001011        203

Number of IP Addresses:      512
Number of Addressable Hosts: 510
IP Address Range:            192.168.112.0 - 192.168.113.255
Broadcast Address:           192.168.113.255
Min Host:                    192.168.112.1
Max Host:                    192.168.113.254
IPv4 ARPA Domain:            203.112.168.192.in-addr.arpa
*/
```

#### Printing - String Representation
```php
print($sub);
/*
192.168.112.203/23           Quads      Hex                           Binary    Integer
------------------ --------------- -------- -------------------------------- ----------
IP Address:        192.168.112.203 C0A870CB 11000000101010000111000011001011 3232264395
Subnet Mask:         255.255.254.0 FFFFFE00 11111111111111111111111000000000 4294966784
Network Portion:     192.168.112.0 C0A87000 11000000101010000111000000000000 3232264192
Host Portion:            0.0.0.203 000000CB 00000000000000000000000011001011        203

Number of IP Addresses:      512
Number of Addressable Hosts: 510
IP Address Range:            192.168.112.0 - 192.168.113.255
Broadcast Address:           192.168.113.255
Min Host:                    192.168.112.1
Max Host:                    192.168.113.254
IPv4 ARPA Domain:            203.112.168.192.in-addr.arpa
*/
```

### Standard Interfaces

#### JsonSerializable
```php
$json = \json_encode($sub);
```

Unit Tests
----------

```bash
$ cd tests
$ phpunit
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
