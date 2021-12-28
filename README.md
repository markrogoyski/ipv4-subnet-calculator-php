IPv4 Subnet Calculator (PHP)
============================

Network calculator for subnet mask and other classless (CIDR) network information.

[![Coverage Status](https://coveralls.io/repos/github/markrogoyski/ipv4-subnet-calculator-php/badge.svg?branch=master)](https://coveralls.io/github/markrogoyski/ipv4-subnet-calculator-php?branch=master)
[![License](https://poser.pugx.org/markrogoyski/math-php/license)](https://packagist.org/packages/markrogoyski/ipv4-subnet-calculator-php)

Features
--------
Given an IP address and CIDR network size, it calculates the network information and provides all-in-one aggregated reports.

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
```php
// For network 192.168.112.203/23
$sub = new IPv4\SubnetCalculator('192.168.112.203', 23);
```

### Various Network Information
```php
$numbeIpAddresses     = $sub->getNumberIPAddresses();      // 512
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

### Reverse DNS Lookup (ARPA Domain)
```php
$ipv4ArpaDomain = $sub->getIPv4ArpaDomain(); // 203.112.168.192.in-addr.arpa
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
