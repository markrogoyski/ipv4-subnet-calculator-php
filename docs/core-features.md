# Core Features

This document covers the fundamental operations and methods available in the IPv4 Subnet Calculator library.

## Table of Contents
- [Network Component Access](#network-component-access)
- [Network Overlap and Containment](#network-overlap-and-containment)
- [IP Address Type Detection](#ip-address-type-detection)
- [Reverse DNS (ARPA Domain)](#reverse-dns-arpa-domain)
- [Subnet Operations](#subnet-operations)
- [Adjacent Subnet Navigation](#adjacent-subnet-navigation)

## Network Component Access

All network components (IP addresses, masks, network/host portions) are available in multiple formats: dotted decimal quads, quads array, hexadecimal, binary, and integer.

### IP Address

Access the IP address used to create the subnet in various formats:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

$ipAddress        = $subnet->getIPAddress();        // '192.168.112.203'
$ipAddressQuads   = $subnet->getIPAddressQuads();   // [192, 168, 112, 203]
$ipAddressHex     = $subnet->getIPAddressHex();     // 'C0A870CB'
$ipAddressBinary  = $subnet->getIPAddressBinary();  // '11000000101010000111000011001011'
$ipAddressInteger = $subnet->getIPAddressInteger(); // 3232264395
```

### Subnet Mask

The subnet mask defines which portion of an IP address represents the network:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

$subnetMask        = $subnet->getSubnetMask();        // '255.255.254.0'
$subnetMaskQuads   = $subnet->getSubnetMaskQuads();   // [255, 255, 254, 0]
$subnetMaskHex     = $subnet->getSubnetMaskHex();     // 'FFFFFE00'
$subnetMaskBinary  = $subnet->getSubnetMaskBinary();  // '11111111111111111111111000000000'
$subnetMaskInteger = $subnet->getSubnetMaskInteger(); // 4294966784
```

### Wildcard Mask

Wildcard masks are the inverse of subnet masks, commonly used in Cisco ACLs and OSPF configurations.

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

$wildcardMask        = $subnet->getWildcardMask();        // '0.0.1.255'
$wildcardMaskQuads   = $subnet->getWildcardMaskQuads();   // [0, 0, 1, 255]
$wildcardMaskHex     = $subnet->getWildcardMaskHex();     // '000001FF'
$wildcardMaskBinary  = $subnet->getWildcardMaskBinary();  // '00000000000000000000000111111111'
$wildcardMaskInteger = $subnet->getWildcardMaskInteger(); // 511
```

**Use Cases:**
- Cisco ACL configuration
- OSPF area configuration
- Router wildcard matching

### Network Portion

The network portion is the IP address with host bits set to zero:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

$network        = $subnet->getNetworkPortion();        // '192.168.112.0'
$networkQuads   = $subnet->getNetworkPortionQuads();   // [192, 168, 112, 0]
$networkHex     = $subnet->getNetworkPortionHex();     // 'C0A87000'
$networkBinary  = $subnet->getNetworkPortionBinary();  // '11000000101010000111000000000000'
$networkInteger = $subnet->getNetworkPortionInteger(); // 3232264192
```

### Host Portion

The host portion is the IP address with network bits set to zero:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

$host        = $subnet->getHostPortion();        // '0.0.0.203'
$hostQuads   = $subnet->getHostPortionQuads();   // [0, 0, 0, 203]
$hostHex     = $subnet->getHostPortionHex();     // '000000CB'
$hostBinary  = $subnet->getHostPortionBinary();  // '00000000000000000000000011001011'
$hostInteger = $subnet->getHostPortionInteger(); // 203
```

### Minimum and Maximum Host

Get the first and last usable host addresses in the subnet:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

// Minimum (first usable host)
$minHost        = $subnet->getMinHost();        // '192.168.112.1'
$minHostQuads   = $subnet->getMinHostQuads();   // [192, 168, 112, 1]
$minHostHex     = $subnet->getMinHostHex();     // 'C0A87001'
$minHostBinary  = $subnet->getMinHostBinary();  // '11000000101010000111000000000001'
$minHostInteger = $subnet->getMinHostInteger(); // 3232264193

// Maximum (last usable host)
$maxHost        = $subnet->getMaxHost();        // '192.168.113.254'
$maxHostQuads   = $subnet->getMaxHostQuads();   // [192, 168, 113, 254]
$maxHostHex     = $subnet->getMaxHostHex();     // 'C0A871FE'
$maxHostBinary  = $subnet->getMaxHostBinary();  // '11000000101010000111000111111110'
$maxHostInteger = $subnet->getMaxHostInteger(); // 3232264702
```

**RFC 3021 Special Cases:**

For /31 networks (point-to-point links), both addresses are usable:
```php
$p2p = IPv4\SubnetCalculatorFactory::fromCidr('10.0.0.0/31');
$p2p->getMinHost();  // '10.0.0.0'
$p2p->getMaxHost();  // '10.0.0.1'
```

For /32 networks (single host), the only address is both min and max:
```php
$single = IPv4\SubnetCalculatorFactory::fromCidr('10.0.0.1/32');
$single->getMinHost();  // '10.0.0.1'
$single->getMaxHost();  // '10.0.0.1'
```

### Iterate All IP Addresses

Loop through all IP addresses in a subnet:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/29');

// All IP addresses (including network and broadcast)
foreach ($subnet->getAllIPAddresses() as $ipAddress) {
    echo $ipAddress . "\n";
}
// Output:
// 192.168.1.0 (network)
// 192.168.1.1
// 192.168.1.2
// 192.168.1.3
// 192.168.1.4
// 192.168.1.5
// 192.168.1.6
// 192.168.1.7 (broadcast)

// Only usable host addresses (excludes network and broadcast)
foreach ($subnet->getAllHostIPAddresses() as $hostAddress) {
    echo $hostAddress . "\n";
}
// Output:
// 192.168.1.1
// 192.168.1.2
// 192.168.1.3
// 192.168.1.4
// 192.168.1.5
// 192.168.1.6
```

**Performance Note:** For large subnets (e.g., /16 or larger), iterating all IPs may consume significant memory and time. Consider using range methods instead.

### Check if IP is in Subnet

Determine whether a specific IP address belongs to the subnet:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.0/23');

$inSubnet = $subnet->isIPAddressInSubnet('192.168.112.5');   // true
$inSubnet = $subnet->isIPAddressInSubnet('192.168.113.200'); // true
$inSubnet = $subnet->isIPAddressInSubnet('192.168.111.5');   // false
$inSubnet = $subnet->isIPAddressInSubnet('192.168.114.1');   // false

// Network and broadcast addresses are included
$inSubnet = $subnet->isIPAddressInSubnet('192.168.112.0');   // true (network address)
$inSubnet = $subnet->isIPAddressInSubnet('192.168.113.255'); // true (broadcast address)
```

**Use Cases:**
- Access control and authorization
- Log analysis and filtering
- Network segmentation validation
- Firewall rule verification

## Network Overlap and Containment

Useful for network planning, firewall rule validation, and routing table conflict detection.

### Check if Two Subnets Overlap

Determine if two subnets share any IP addresses:

```php
$subnet1 = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');
$subnet2 = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.128/25');

$overlaps = $subnet1->overlaps($subnet2);  // true - they share 192.168.1.128-255

// Non-overlapping subnets
$subnet3 = IPv4\SubnetCalculatorFactory::fromCidr('192.168.2.0/24');
$overlaps = $subnet1->overlaps($subnet3);  // false - completely separate
```

**Overlap Detection Rules:**
- If one subnet completely contains the other, they overlap
- If they share any IP addresses, they overlap
- Adjacent subnets do not overlap (192.168.1.0/24 and 192.168.2.0/24)

**Use Cases:**
- Validate firewall rules for conflicts
- Detect routing table issues
- Prevent duplicate IP assignments
- Network planning and IPAM

### Check if One Subnet Contains Another

Determine if a subnet completely contains another subnet:

```php
$large = IPv4\SubnetCalculatorFactory::fromCidr('10.0.0.0/8');
$small = IPv4\SubnetCalculatorFactory::fromCidr('10.1.2.0/24');

$contains = $large->contains($small);  // true - 10.0.0.0/8 contains 10.1.2.0/24

// Equal subnets
$subnet1 = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');
$subnet2 = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');
$contains = $subnet1->contains($subnet2);  // true - a subnet contains itself

// Larger does not contain smaller
$small = IPv4\SubnetCalculatorFactory::fromCidr('172.16.0.0/16');
$large = IPv4\SubnetCalculatorFactory::fromCidr('172.16.0.0/12');
$contains = $small->contains($large);  // false - smaller cannot contain larger
```

**Containment Rules:**
- A subnet contains itself
- A larger prefix length subnet cannot contain a smaller prefix length subnet
  (e.g., /24 cannot contain /16)
- All IPs of the contained subnet must be within the containing subnet

### Check if Subnet is Contained Within Another

The inverse operation - check if this subnet is contained within another:

```php
$small = IPv4\SubnetCalculatorFactory::fromCidr('172.16.0.0/16');
$large = IPv4\SubnetCalculatorFactory::fromCidr('172.16.0.0/12');

$isContained = $small->isContainedIn($large);  // true - 172.16.0.0/16 is within 172.16.0.0/12

// Not contained
$subnet1 = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');
$subnet2 = IPv4\SubnetCalculatorFactory::fromCidr('192.168.2.0/24');
$isContained = $subnet1->isContainedIn($subnet2);  // false - different networks
```

**Practical Example - Validate Subnet Assignment:**

```php
// Corporate policy: All department subnets must be within 10.0.0.0/8
$corporateNetwork = IPv4\SubnetCalculatorFactory::fromCidr('10.0.0.0/8');
$proposedSubnet   = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');

if (!$proposedSubnet->isContainedIn($corporateNetwork)) {
    echo "Error: Proposed subnet is outside corporate address space\n";
}
```

## IP Address Type Detection

Useful for security validation, routing decisions, and network classification. All methods comply with IANA IPv4 Special-Purpose Address Registry and relevant RFCs.

### Private vs Public Addresses

Check if an IP address is private (RFC 1918) or publicly routable:

```php
$private = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.100/24');
$public  = IPv4\SubnetCalculatorFactory::fromCidr('8.8.8.8/32');

$private->isPrivate();  // true - 192.168.0.0/16 is RFC 1918 private
$public->isPrivate();   // false - 8.8.8.8 is public

$private->isPublic();   // false - private addresses are not public
$public->isPublic();    // true - not in any special-purpose range
```

**Private Address Ranges (RFC 1918):**
- 10.0.0.0/8
- 172.16.0.0/12
- 192.168.0.0/16

### Special-Purpose Address Ranges

Check for various special-purpose IP address types:

```php
$loopback    = IPv4\SubnetCalculatorFactory::fromCidr('127.0.0.1/32');
$linkLocal   = IPv4\SubnetCalculatorFactory::fromCidr('169.254.1.1/32');
$multicast   = IPv4\SubnetCalculatorFactory::fromCidr('224.0.0.1/32');
$cgn         = IPv4\SubnetCalculatorFactory::fromCidr('100.64.0.1/32');
$docs        = IPv4\SubnetCalculatorFactory::fromCidr('192.0.2.1/32');
$benchmark   = IPv4\SubnetCalculatorFactory::fromCidr('198.18.0.1/32');
$reserved    = IPv4\SubnetCalculatorFactory::fromCidr('240.0.0.1/32');
$broadcast   = IPv4\SubnetCalculatorFactory::fromCidr('255.255.255.255/32');
$thisNetwork = IPv4\SubnetCalculatorFactory::fromCidr('0.0.0.1/32');

$loopback->isLoopback();            // true - 127.0.0.0/8
$linkLocal->isLinkLocal();          // true - 169.254.0.0/16 (APIPA)
$multicast->isMulticast();          // true - 224.0.0.0/4
$cgn->isCarrierGradeNat();          // true - 100.64.0.0/10 (Shared Address Space)
$docs->isDocumentation();           // true - TEST-NET ranges
$benchmark->isBenchmarking();       // true - 198.18.0.0/15
$reserved->isReserved();            // true - 240.0.0.0/4
$broadcast->isLimitedBroadcast();   // true - 255.255.255.255 only
$thisNetwork->isThisNetwork();      // true - 0.0.0.0/8
```

### Get Address Type Classification

Get a string representation of the address type:

```php
$classifications = [
    '192.168.1.1'     => 'private',
    '8.8.8.8'         => 'public',
    '127.0.0.1'       => 'loopback',
    '169.254.1.1'     => 'link-local',
    '224.0.0.1'       => 'multicast',
    '100.64.0.1'      => 'carrier-grade-nat',
    '192.0.2.1'       => 'documentation',
    '198.18.0.1'      => 'benchmarking',
    '240.0.0.1'       => 'reserved',
    '255.255.255.255' => 'limited-broadcast',
    '0.0.0.1'         => 'this-network',
];

foreach ($classifications as $ip => $expectedType) {
    $subnet = IPv4\SubnetCalculatorFactory::fromCidr("{$ip}/32");
    echo "{$ip}: " . $subnet->getAddressType() . "\n";
}
```

### Supported Range Types Reference

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

### Use Cases

**Security Validation:**
```php
$userIP = IPv4\SubnetCalculatorFactory::fromCidr($_SERVER['REMOTE_ADDR'] . '/32');

if ($userIP->isPrivate()) {
    // Internal user
    $accessLevel = 'full';
} else {
    // External user
    $accessLevel = 'limited';
}
```

**Routing Decisions:**
```php
$destination = IPv4\SubnetCalculatorFactory::fromCidr($targetIP . '/32');

if ($destination->isPrivate()) {
    $route = 'internal-gateway';
} elseif ($destination->isPublic()) {
    $route = 'external-gateway';
} else {
    // Special handling for loopback, multicast, etc.
}
```

**Network Configuration Validation:**
```php
$staticIP = IPv4\SubnetCalculatorFactory::fromCidr($configuredIP . '/32');

if ($staticIP->isLoopback() || $staticIP->isMulticast() || $staticIP->isReserved()) {
    throw new \Exception("Invalid static IP: Cannot use special-purpose address");
}
```

## Reverse DNS (ARPA Domain)

Get the reverse DNS lookup domain (in-addr.arpa format) for an IP address:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

$arpaDomain = $subnet->getIPv4ArpaDomain();  // '203.112.168.192.in-addr.arpa'
```

**Use Cases:**
- Configuring reverse DNS zones
- PTR record setup
- DNS server configuration
- Email server configuration (SPF, reverse lookup verification)

**Note:** The ARPA domain is based on the IP address used to create the subnet, not the network address.

## Subnet Operations

### Split Network into Smaller Subnets

Divide a subnet into smaller, equal-sized subnets:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.0/23');

// Split into /25 subnets
$smallerNetworks = $subnet->split(25);

// Returns array of SubnetCalculator instances:
// [0] => 192.168.112.0/25
// [1] => 192.168.112.128/25
// [2] => 192.168.113.0/25
// [3] => 192.168.113.128/25

foreach ($smallerNetworks as $network) {
    echo $network->getCidrNotation() . "\n";
}
```

**Rules:**
- New prefix must be larger than (more specific than) current prefix
- Cannot split /32 (single host)
- Result is always an array of SubnetCalculator instances

**Practical Example - Department Allocation:**

```php
// Split a /22 into four /24 subnets for departments
$allocated = IPv4\SubnetCalculatorFactory::fromCidr('10.0.0.0/22');
$departments = $allocated->split(24);

$assignments = [
    'Engineering' => $departments[0],  // 10.0.0.0/24
    'Sales'       => $departments[1],  // 10.0.1.0/24
    'Marketing'   => $departments[2],  // 10.0.2.0/24
    'HR'          => $departments[3],  // 10.0.3.0/24
];

foreach ($assignments as $dept => $subnet) {
    echo "{$dept}: {$subnet->getCidrNotation()} ";
    echo "({$subnet->getNumberAddressableHosts()} hosts)\n";
}
```

## Adjacent Subnet Navigation

Navigate to neighboring subnets of the same size for sequential IP allocation and network expansion planning.

### Get Next Subnet

Get the subnet immediately following the current one (same size):

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.1.0/24');
$next   = $subnet->getNextSubnet();  // Returns SubnetCalculator for 192.168.2.0/24

echo $next->getCidrNotation();  // '192.168.2.0/24'

// Works with any network size
$smallSubnet = IPv4\SubnetCalculatorFactory::fromCidr('10.0.0.0/30');
$next        = $smallSubnet->getNextSubnet();  // Returns 10.0.0.4/30
```

### Get Previous Subnet

Get the subnet immediately preceding the current one:

```php
$subnet   = IPv4\SubnetCalculatorFactory::fromCidr('192.168.5.0/24');
$previous = $subnet->getPreviousSubnet();  // Returns SubnetCalculator for 192.168.4.0/24

echo $previous->getCidrNotation();  // '192.168.4.0/24'
```

### Get Multiple Adjacent Subnets

Get multiple subnets in forward or backward direction:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.10.0/24');

// Get next 3 subnets (forward direction)
$nextSubnets = $subnet->getAdjacentSubnets(3);
// Returns array: [192.168.11.0/24, 192.168.12.0/24, 192.168.13.0/24]

// Get previous 3 subnets (backward direction)
$previousSubnets = $subnet->getAdjacentSubnets(-3);
// Returns array: [192.168.9.0/24, 192.168.8.0/24, 192.168.7.0/24]

foreach ($nextSubnets as $next) {
    echo $next->getCidrNotation() . "\n";
}
```

### Chaining Navigation

Combine navigation methods for complex operations:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('10.0.0.0/24');

// Navigate forward
$targetSubnet = $subnet->getNextSubnet()
                       ->getNextSubnet()
                       ->getNextSubnet();  // Results in 10.0.3.0/24

// Navigate forward and back
$back = $subnet->getNextSubnet()->getPreviousSubnet();  // Back to 10.0.0.0/24
```

### Practical Use Cases

**Sequential IP Allocation:**
```php
$startSubnet = IPv4\SubnetCalculatorFactory::fromCidr('10.0.0.0/24');
$allocated = [$startSubnet];

// Allocate 10 consecutive /24 subnets
$current = $startSubnet;
for ($i = 0; $i < 9; $i++) {
    $current = $current->getNextSubnet();
    $allocated[] = $current;
}

// Results in: 10.0.0.0/24 through 10.0.9.0/24
```

**Network Expansion Planning:**
```php
$currentNetwork = IPv4\SubnetCalculatorFactory::fromCidr('172.16.5.0/24');

// Check if next subnet is available
$nextNetwork = $currentNetwork->getNextSubnet();
echo "Available for expansion: {$nextNetwork->getCidrNotation()}\n";

// Plan for future growth
$futureNetworks = $currentNetwork->getAdjacentSubnets(5);
echo "Future expansion space: " . count($futureNetworks) . " /24 subnets available\n";
```

**Subnet Scanning:**
```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.0.0/24');

// Scan current and adjacent subnets
$previousSubnet = $subnet->getPreviousSubnet();
$nextSubnet = $subnet->getNextSubnet();

$scanList = [$previousSubnet, $subnet, $nextSubnet];

foreach ($scanList as $scanSubnet) {
    echo "Scanning: {$scanSubnet->getCidrNotation()}\n";
    // Perform network scan...
}
```

## Next Steps

- **[Advanced Features](advanced-features.md)** - CIDR aggregation, subnet exclusion, utilization analysis, network class information
- **[Reports](reports.md)** - Generate comprehensive network reports in multiple formats
- **[API Reference](api-reference.md)** - Complete method documentation
- **[Real-World Examples](examples.md)** - Practical patterns and use cases
