# Advanced Features

This document covers advanced operations including subnet exclusion, CIDR aggregation, utilization analysis, and legacy network class information.

## Table of Contents
- [Subnet Exclusion and Difference Operations](#subnet-exclusion-and-difference-operations)
- [CIDR Aggregation and Supernetting](#cidr-aggregation-and-supernetting)
- [Utilization Statistics](#utilization-statistics)
- [Network Class Information (Legacy)](#network-class-information-legacy)

## Subnet Exclusion and Difference Operations

Calculate what remains of a subnet after excluding (removing) one or more other subnets. Useful for IP address management (IPAM), reserving address ranges, and carving out specific blocks from larger allocations.

The exclusion methods return the minimal set of CIDR blocks that represent the remaining address space.

### Exclude a Single Subnet

Remove one subnet from another and get the remaining address space:

```php
// Allocate a /24 network but reserve the first /26 for infrastructure
$allocated = IPv4\Subnet::fromCidr('192.168.1.0/24');
$reserved  = IPv4\Subnet::fromCidr('192.168.1.0/26');

$available = $allocated->exclude($reserved);
// Returns array of Subnet instances:
// [0] => 192.168.1.64/26
// [1] => 192.168.1.128/25
// Available address space: 192.168.1.64-192.168.1.255 (192 addresses)

foreach ($available as $subnet) {
    echo $subnet->cidr() . "\n";
}
```

### Exclude Multiple Subnets

Remove multiple subnets from a base subnet:

```php
// Allocate a /24 but reserve multiple ranges
$allocated = IPv4\Subnet::fromCidr('10.0.0.0/24');

$reserved = [
    IPv4\Subnet::fromCidr('10.0.0.0/26'),   // First quarter for infrastructure
    IPv4\Subnet::fromCidr('10.0.0.128/26'), // Third quarter for management
];

$available = $allocated->excludeAll($reserved);
// Returns array of Subnet instances:
// [0] => 10.0.0.64/26
// [1] => 10.0.0.192/26
// Available: 10.0.0.64-127 and 10.0.0.192-255 (128 addresses)

foreach ($available as $subnet) {
    echo "{$subnet->cidr()}: {$subnet->addressCount()} addresses\n";
}
```

### Practical Use Cases

#### Reserve Network and Broadcast Addresses

```php
// Remove network and broadcast addresses from a subnet
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');

$exclusions = [
    IPv4\Subnet::fromCidr('192.168.1.0/32'),   // Network address
    IPv4\Subnet::fromCidr('192.168.1.255/32'), // Broadcast address
];

$usableSpace = $subnet->excludeAll($exclusions);
// Returns CIDR blocks representing 192.168.1.1-254 (254 addresses)

echo "Usable address space after excluding network and broadcast:\n";
foreach ($usableSpace as $block) {
    echo "  {$block->cidr()}\n";
}
```

#### Carve Out Reserved Ranges from Large Allocation

```php
// ISP allocates a /16 but needs to exclude documentation ranges
$allocation = IPv4\Subnet::fromCidr('192.0.0.0/16');
$testNet1   = IPv4\Subnet::fromCidr('192.0.2.0/24'); // TEST-NET-1

$usableSpace = $allocation->exclude($testNet1);
// Returns optimal CIDR blocks for all addresses except 192.0.2.0/24

echo "Usable space: " . count($usableSpace) . " CIDR blocks\n";
foreach ($usableSpace as $block) {
    echo "  {$block->cidr()}\n";
}
```

#### Sequential Subnet Carving

```php
// Start with a large block and carve out assignments
$pool = IPv4\Subnet::fromCidr('10.0.0.0/16');

// Assign subnets to different departments
$engineering = IPv4\Subnet::fromCidr('10.0.0.0/20');
$sales       = IPv4\Subnet::fromCidr('10.0.16.0/20');
$hr          = IPv4\Subnet::fromCidr('10.0.32.0/20');

$remainingPool = $pool->excludeAll([$engineering, $sales, $hr]);
// Returns remaining unallocated address space

echo "Allocated:\n";
echo "  Engineering: {$engineering->cidr()} ({$engineering->addressCount()} IPs)\n";
echo "  Sales: {$sales->cidr()} ({$sales->addressCount()} IPs)\n";
echo "  HR: {$hr->cidr()} ({$hr->addressCount()} IPs)\n";
echo "\nRemaining pool:\n";
foreach ($remainingPool as $block) {
    echo "  {$block->cidr()} ({$block->addressCount()} IPs)\n";
}
```

#### Progressive IPAM Allocation

```php
// Real-world IPAM: Track allocations and remaining space
class IPAMPool {
    private $availableBlocks;

    public function __construct($initialBlock) {
        $this->availableBlocks = [
            IPv4\Subnet::fromCidr($initialBlock)
        ];
    }

    public function allocate($cidr) {
        $allocation = IPv4\Subnet::fromCidr($cidr);
        $newAvailable = [];

        foreach ($this->availableBlocks as $block) {
            if ($block->contains($allocation)) {
                // Exclude allocation from this block
                $remaining = $block->exclude($allocation);
                $newAvailable = array_merge($newAvailable, $remaining);
            } else {
                // Keep block unchanged
                $newAvailable[] = $block;
            }
        }

        $this->availableBlocks = $newAvailable;
        return $allocation;
    }

    public function getAvailableBlocks() {
        return $this->availableBlocks;
    }
}

// Usage
$ipam = new IPAMPool('10.0.0.0/16');

$ipam->allocate('10.0.0.0/24');   // First allocation
$ipam->allocate('10.0.1.0/24');   // Second allocation
$ipam->allocate('10.0.16.0/20');  // Larger allocation

echo "Remaining available blocks:\n";
foreach ($ipam->getAvailableBlocks() as $block) {
    echo "  {$block->cidr()} ({$block->addressCount()} IPs)\n";
}
```

### Behavior Notes

#### Non-overlapping Subnets

If the excluded subnet doesn't overlap with the base subnet, returns the original subnet unchanged:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');
$other  = IPv4\Subnet::fromCidr('192.168.2.0/24');

$result = $subnet->exclude($other);
// Returns: [192.168.1.0/24] (unchanged - no overlap)
```

#### Full Exclusion

If the excluded subnet fully contains the base subnet, returns an empty array:

```php
$small = IPv4\Subnet::fromCidr('10.0.0.0/25');
$large = IPv4\Subnet::fromCidr('10.0.0.0/24');

$result = $small->exclude($large);
// Returns: [] (nothing remains - fully excluded)
```

#### Optimal CIDR Blocks

Results are always properly aligned CIDR blocks (not arbitrary ranges):

```php
$subnet = IPv4\Subnet::fromCidr('10.0.0.0/24');
$middle = IPv4\Subnet::fromCidr('10.0.0.100/32');

$result = $subnet->exclude($middle);
// Returns 8 optimally-sized CIDR blocks representing addresses 10.0.0.0-99 and 10.0.0.101-255
// Result: 10.0.0.0/26, 10.0.0.64/27, 10.0.0.96/30, 10.0.0.101/32, 10.0.0.102/31,
//         10.0.0.104/29, 10.0.0.112/28, 10.0.0.128/25
```

## CIDR Aggregation and Supernetting

Combine multiple subnets into larger summary routes to reduce routing table size. Essential for BGP route summarization, OSPF area design, and efficient network addressing plans.

### Aggregate Multiple Subnets

The `aggregate()` method combines contiguous subnets into the minimal set of larger CIDR blocks. Only truly contiguous subnets are merged - gaps are preserved.

#### Example 1: Two Adjacent /24s Combine into One /23

```php
$subnets = [
    IPv4\Subnet::fromCidr('192.168.0.0/24'),
    IPv4\Subnet::fromCidr('192.168.1.0/24'),
];

$aggregated = IPv4\Subnets::aggregate($subnets);
// Returns: [192.168.0.0/23]

foreach ($aggregated as $summary) {
    echo $summary->cidr() . "\n";
}
```

#### Example 2: Four Consecutive /24s Become One /22

```php
$subnets = [
    IPv4\Subnet::fromCidr('10.0.0.0/24'),
    IPv4\Subnet::fromCidr('10.0.1.0/24'),
    IPv4\Subnet::fromCidr('10.0.2.0/24'),
    IPv4\Subnet::fromCidr('10.0.3.0/24'),
];

$aggregated = IPv4\Subnets::aggregate($subnets);
// Returns: [10.0.0.0/22]

echo "Aggregated " . count($subnets) . " /24s into: {$aggregated[0]->cidr()}\n";
```

#### Example 3: Non-contiguous Subnets Remain Separate

```php
$subnets = [
    IPv4\Subnet::fromCidr('192.168.0.0/24'),
    IPv4\Subnet::fromCidr('192.168.2.0/24'),  // Gap at .1.0/24
];

$aggregated = IPv4\Subnets::aggregate($subnets);
// Returns: [192.168.0.0/24, 192.168.2.0/24] - cannot combine due to gap

echo "Cannot aggregate - gap exists. Result:\n";
foreach ($aggregated as $subnet) {
    echo "  {$subnet->cidr()}\n";
}
```

### Summarize to Single Supernet

The `summarize()` method finds the smallest single CIDR block that contains all input subnets. Unlike `aggregate()`, this always returns a single subnet but may include addresses not in the original subnets (fills gaps).

#### Example 1: Perfect Fit - No Extra Addresses

```php
$subnets = [
    IPv4\Subnet::fromCidr('192.168.0.0/24'),
    IPv4\Subnet::fromCidr('192.168.1.0/24'),
];

$summary = IPv4\Subnets::summarize($subnets);
// Returns: 192.168.0.0/23 (perfect fit, no waste)

echo "Summary: {$summary->cidr()}\n";
```

#### Example 2: Has Gap - Includes Extra Addresses to Cover Range

```php
$subnets = [
    IPv4\Subnet::fromCidr('192.168.0.0/24'),
    IPv4\Subnet::fromCidr('192.168.2.0/24'),  // Missing .1.0/24
];

$summary = IPv4\Subnets::summarize($subnets);
// Returns: 192.168.0.0/22
// Includes .0.x, .1.x (not in input!), .2.x, and .3.x (not in input!)

echo "Summary: {$summary->cidr()}\n";
echo "Note: Includes 192.168.1.0/24 and 192.168.3.0/24 (not in original input)\n";
```

#### Example 3: Sparse Subnets Need Large Covering Block

```php
$subnets = [
    IPv4\Subnet::fromCidr('10.0.0.0/32'),
    IPv4\Subnet::fromCidr('10.0.0.255/32'),
];

$summary = IPv4\Subnets::summarize($subnets);
// Returns: 10.0.0.0/24 (includes all 254 addresses between them)

echo "Summary: {$summary->cidr()}\n";
echo "Covers: {$summary->addressCount()} addresses (includes many not in input)\n";
```

### Practical Use Cases

#### BGP Route Summarization

```php
// Your organization has these 4 regional office subnets
$offices = [
    IPv4\Subnet::fromCidr('10.1.0.0/24'),  // Office A
    IPv4\Subnet::fromCidr('10.1.1.0/24'),  // Office B
    IPv4\Subnet::fromCidr('10.1.2.0/24'),  // Office C
    IPv4\Subnet::fromCidr('10.1.3.0/24'),  // Office D
];

$summary = IPv4\Subnets::aggregate($offices);
// Returns: [10.1.0.0/22]
// Advertise this single route instead of 4 individual routes

echo "BGP Advertisement: {$summary[0]->cidr()}\n";
echo "Reduces routing table by " . (count($offices) - count($summary)) . " entries\n";
```

#### Multiple Data Centers

```php
// Aggregate separate data center allocations
$datacenters = [
    IPv4\Subnet::fromCidr('10.0.0.0/24'),
    IPv4\Subnet::fromCidr('10.0.1.0/24'),
    IPv4\Subnet::fromCidr('192.168.0.0/24'),
    IPv4\Subnet::fromCidr('192.168.1.0/24'),
];

$aggregated = IPv4\Subnets::aggregate($datacenters);
// Returns: [10.0.0.0/23, 192.168.0.0/23]
// Two summary routes for two non-contiguous regions

echo "Data center routes:\n";
foreach ($aggregated as $route) {
    echo "  {$route->cidr()}\n";
}
```

#### Finding Covering Supernet for ACLs

```php
// Allow access to multiple department subnets with one firewall rule
$departments = [
    IPv4\Subnet::fromCidr('172.16.1.0/24'),  // Engineering
    IPv4\Subnet::fromCidr('172.16.2.0/24'),  // Sales
    IPv4\Subnet::fromCidr('172.16.3.0/24'),  // Marketing
];

$allowRule = IPv4\Subnets::summarize($departments);
// Returns: 172.16.0.0/22
// One ACL entry instead of three (includes .0.0/24 which may be acceptable)

echo "Single ACL rule: permit {$allowRule->cidr()}\n";
echo "Covers {$allowRule->addressCount()} addresses\n";
```

#### Routing Table Optimization

```php
// Analyze routing table for aggregation opportunities
$routes = [
    IPv4\Subnet::fromCidr('10.0.0.0/24'),
    IPv4\Subnet::fromCidr('10.0.1.0/24'),
    IPv4\Subnet::fromCidr('10.0.2.0/24'),
    IPv4\Subnet::fromCidr('10.0.3.0/24'),
    IPv4\Subnet::fromCidr('172.16.0.0/24'),
    IPv4\Subnet::fromCidr('172.16.1.0/24'),
];

$optimized = IPv4\Subnets::aggregate($routes);

echo "Original routes: " . count($routes) . "\n";
echo "Optimized routes: " . count($optimized) . "\n";
echo "Reduction: " . (count($routes) - count($optimized)) . " routes\n\n";

echo "Optimized routing table:\n";
foreach ($optimized as $route) {
    echo "  {$route->cidr()}\n";
}
```

### Behavior Notes

#### Deduplication and Overlap Handling

Both methods automatically handle duplicates and overlapping subnets:

```php
$subnets = [
    IPv4\Subnet::fromCidr('192.168.0.0/23'),  // Larger subnet
    IPv4\Subnet::fromCidr('192.168.0.0/24'),  // Contained within /23
    IPv4\Subnet::fromCidr('192.168.0.0/24'),  // Duplicate
];

$aggregated = IPv4\Subnets::aggregate($subnets);
// Returns: [192.168.0.0/23] - duplicates removed, smaller subnet absorbed
```

#### Alignment Requirements

Subnets must be properly aligned to merge. Misaligned adjacent blocks cannot combine:

```php
$subnets = [
    IPv4\Subnet::fromCidr('10.0.1.0/24'),  // Starts at odd boundary
    IPv4\Subnet::fromCidr('10.0.2.0/24'),
];

$aggregated = IPv4\Subnets::aggregate($subnets);
// Returns: [10.0.1.0/24, 10.0.2.0/24]
// Cannot merge - 10.0.1.0 is not aligned for /23 (would need to start at 10.0.0.0)
```

#### Empty Input

```php
$result1 = IPv4\Subnets::aggregate([]);   // Returns: []
// $result2 = IPv4\Subnets::summarize([]);   // Throws: InvalidArgumentException
```

## Utilization Statistics

Analyze subnet efficiency and perform capacity planning. These methods help choose optimal subnet sizes and minimize IP address waste.

### Get Usable Host Percentage

Calculate what percentage of the subnet's total IP addresses are usable as hosts (accounting for network and broadcast address overhead):

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');

$percentage = $subnet->usableHostPercentage();  // 99.22% (254 usable of 256 total)

echo "Usable hosts: {$percentage}%\n";

// Different subnet sizes have different efficiency
$sizes = [24, 25, 26, 27, 28, 29, 30];
foreach ($sizes as $size) {
    $s = IPv4\Subnet::fromCidr("192.168.1.0/{$size}");
    echo "/{$size}: {$s->usableHostPercentage()}% usable\n";
}
```

**RFC 3021 Special Cases - No Overhead:**

```php
// Point-to-point link (/31)
$p2p = IPv4\Subnet::fromCidr('10.0.0.0/31');
$p2p->usableHostPercentage();  // 100.0% (2 usable of 2 total)

// Single host (/32)
$single = IPv4\Subnet::fromCidr('10.0.0.1/32');
$single->usableHostPercentage();  // 100.0% (1 usable of 1 total)
```

### Count Unusable Addresses

Get the count of addresses that cannot be used as hosts (network address + broadcast address):

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');

$unusable = $subnet->unusableAddressCount();  // 2 (network + broadcast)

echo "Unusable addresses: {$unusable}\n";

// RFC 3021 special cases - no unusable addresses
$p2p = IPv4\Subnet::fromCidr('10.0.0.0/31');
echo "P2P unusable: {$p2p->unusableAddressCount()}\n";  // 0 (both addresses usable)

$single = IPv4\Subnet::fromCidr('10.0.0.1/32');
echo "Single host unusable: {$single->unusableAddressCount()}\n";  // 0
```

### Calculate Utilization for Host Requirements

Determine how efficiently a subnet would be utilized for a specific number of required hosts:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');  // 254 usable hosts

// Good fit - 78.74% utilization
$utilization = $subnet->utilizationFor(200);
echo "200 hosts in /24: {$utilization}% utilization\n";  // 78.74%

// Perfect fit - 100% utilization
$utilization = $subnet->utilizationFor(254);
echo "254 hosts in /24: {$utilization}% utilization\n";  // 100.0%

// Oversized subnet - wasting addresses
$utilization = $subnet->utilizationFor(50);
echo "50 hosts in /24: {$utilization}% utilization\n";   // 19.69% (inefficient)

// Insufficient capacity - more than 100%
$utilization = $subnet->utilizationFor(300);
echo "300 hosts in /24: {$utilization}% utilization\n";  // 118.11% (too small!)
```

### Calculate Wasted Addresses

Determine how many usable addresses would be wasted (or how many more are needed) for a specific host requirement:

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');  // 254 usable hosts

// 54 addresses wasted
$wasted = $subnet->wastedAddressesFor(200);
echo "200 hosts in /24: {$wasted} addresses wasted\n";  // 54 (254 - 200)

// Perfect fit - no waste
$wasted = $subnet->wastedAddressesFor(254);
echo "254 hosts in /24: {$wasted} addresses wasted\n";  // 0

// Lots of wasted addresses
$wasted = $subnet->wastedAddressesFor(50);
echo "50 hosts in /24: {$wasted} addresses wasted\n";   // 204 (254 - 50)

// Insufficient capacity - negative value
$wasted = $subnet->wastedAddressesFor(300);
echo "300 hosts in /24: {$wasted} addresses wasted\n";  // -46 (need 46 more addresses!)
```

### Practical Use Case - Choosing Optimal Subnet Size

```php
// You need to allocate 100 hosts
$requiredHosts = 100;

// Compare different subnet sizes
$sizes = [24, 25, 26, 27];

echo "Finding optimal subnet size for {$requiredHosts} required hosts:\n\n";

$bestSubnet = null;
$bestUtilization = 0;

foreach ($sizes as $size) {
    $subnet = IPv4\Subnet::fromCidr("192.168.1.0/{$size}");
    $usableHosts = $subnet->hostCount();

    // Skip if subnet is too small
    if ($usableHosts < $requiredHosts) {
        echo "/{$size}: {$usableHosts} usable - TOO SMALL\n";
        continue;
    }

    $utilization = $subnet->utilizationFor($requiredHosts);
    $wasted = $subnet->wastedAddressesFor($requiredHosts);
    $usablePercent = $subnet->usableHostPercentage();

    echo "/{$size}: {$usableHosts} usable, {$utilization}% utilized, {$wasted} wasted, {$usablePercent}% efficiency\n";

    // Track best fit (highest utilization that meets requirements)
    if ($utilization > $bestUtilization && $utilization <= 100) {
        $bestUtilization = $utilization;
        $bestSubnet = $size;
    }
}

echo "\nRecommendation: /{$bestSubnet} provides the best fit ({$bestUtilization}% utilization)\n";

/*
Output:
Finding optimal subnet size for 100 required hosts:

/24: 254 usable, 39.37% utilized, 154 wasted, 99.22% efficiency
/25: 126 usable, 79.37% utilized, 26 wasted, 98.44% efficiency
/26: 62 usable - TOO SMALL
/27: 30 usable - TOO SMALL

Recommendation: /25 provides the best fit (79.37% utilization)
*/
```

### Network Planning Example

```php
// Department subnet planning
$departments = [
    'Engineering' => 150,
    'Sales'       => 50,
    'Marketing'   => 30,
    'HR'          => 20,
    'IT'          => 10,
];

echo "Department Subnet Planning:\n\n";

foreach ($departments as $dept => $requiredHosts) {
    // Find optimal prefix
    $optimalPrefix = IPv4\SubnetParser::optimalPrefixForHosts($requiredHosts);
    $subnet = IPv4\Subnet::fromCidr("10.0.0.0/{$optimalPrefix}");

    $utilization = $subnet->utilizationFor($requiredHosts);
    $wasted = $subnet->wastedAddressesFor($requiredHosts);
    $usable = $subnet->hostCount();

    echo "{$dept}:\n";
    echo "  Required: {$requiredHosts} hosts\n";
    echo "  Optimal: /{$optimalPrefix} ({$usable} usable)\n";
    echo "  Utilization: {$utilization}%\n";
    echo "  Wasted: {$wasted} addresses\n\n";
}
```

## Network Class Information (Legacy)

While classful networking is obsolete (RFC 4632 established CIDR), legacy network class information is still referenced in education, certifications, and some legacy systems.

### Get Network Class

Determine the legacy classful network class:

```php
$classA = IPv4\Subnet::fromCidr('10.0.0.0/8');
$classB = IPv4\Subnet::fromCidr('172.16.0.0/16');
$classC = IPv4\Subnet::fromCidr('192.168.1.0/24');
$classD = IPv4\Subnet::fromCidr('224.0.0.1/32');
$classE = IPv4\Subnet::fromCidr('240.0.0.0/32');

// networkClass() now returns IPv4\NetworkClass enum
echo "Class A: " . $classA->networkClass()->value . "\n";  // 'A'
echo "Class B: " . $classB->networkClass()->value . "\n";  // 'B'
echo "Class C: " . $classC->networkClass()->value . "\n";  // 'C'
echo "Class D: " . $classD->networkClass()->value . "\n";  // 'D' (Multicast)
echo "Class E: " . $classE->networkClass()->value . "\n";  // 'E' (Reserved)
```

**Working with the NetworkClass Enum:**

```php
$subnet = IPv4\Subnet::fromCidr('192.168.1.0/24');
$class = $subnet->networkClass();  // Returns IPv4\NetworkClass::C

// Get string value
echo $class->value;  // 'C'

// Type-safe comparison
if ($class === IPv4\NetworkClass::C) {
    echo "This is a Class C network\n";
}

// Use in match expressions
$range = match ($class) {
    IPv4\NetworkClass::A => '0.0.0.0 - 127.255.255.255',
    IPv4\NetworkClass::B => '128.0.0.0 - 191.255.255.255',
    IPv4\NetworkClass::C => '192.0.0.0 - 223.255.255.255',
    IPv4\NetworkClass::D => '224.0.0.0 - 239.255.255.255',
    IPv4\NetworkClass::E => '240.0.0.0 - 255.255.255.255',
};
```

### Get Default Classful Mask

Get the default subnet mask and prefix for the network's class:

```php
$subnet = IPv4\Subnet::fromCidr('10.0.0.0/24');

$defaultMask   = $subnet->defaultClassMask();    // '255.0.0.0' (Class A default)
$defaultPrefix = $subnet->defaultClassPrefix();  // 8 (Class A default /8)

echo "Network: {$subnet->cidr()}\n";
echo "Class: {$subnet->networkClass()->value}\n";  // Use ->value for string
echo "Default mask: {$defaultMask}\n";
echo "Default prefix: /{$defaultPrefix}\n";
```

### Check if Subnet Uses Classful Mask

Determine if a subnet is using its default classful boundary or if it's been subnetted/supernetted:

```php
$classfulA   = IPv4\Subnet::fromCidr('10.0.0.0/8');
$subnettedA  = IPv4\Subnet::fromCidr('10.0.0.0/24');
$classfulB   = IPv4\Subnet::fromCidr('172.16.0.0/16');
$supernettedB = IPv4\Subnet::fromCidr('172.16.0.0/12');

echo "10.0.0.0/8 is classful: "  . ($classfulA->isClassful() ? 'Yes' : 'No') . "\n";     // Yes
echo "10.0.0.0/24 is classful: " . ($subnettedA->isClassful() ? 'Yes' : 'No') . "\n";    // No (subnetted)
echo "172.16.0.0/16 is classful: " . ($classfulB->isClassful() ? 'Yes' : 'No') . "\n";   // Yes
echo "172.16.0.0/12 is classful: " . ($supernettedB->isClassful() ? 'Yes' : 'No') . "\n"; // No (supernetted)
```

### Class Definitions

| Class | First Octet | Default Mask | Default Prefix | Purpose |
|-------|-------------|--------------|----------------|---------|
| A | 0-127 | 255.0.0.0 | /8 | Large networks (includes 0.x.x.x and 127.x.x.x) |
| B | 128-191 | 255.255.0.0 | /16 | Medium networks |
| C | 192-223 | 255.255.255.0 | /24 | Small networks |
| D | 224-239 | N/A | N/A | Multicast |
| E | 240-255 | N/A | N/A | Reserved for future use |

### Practical Example - Educational Tool

```php
// Subnet analysis tool for learning classful networking
function analyzeSubnet($cidr) {
    $subnet = IPv4\Subnet::fromCidr($cidr);

    echo "Subnet: {$subnet->cidr()}\n";
    echo "Class: {$subnet->networkClass()->value}\n";  // Use ->value for string
    echo "Default classful mask: {$subnet->defaultClassMask()} (/{$subnet->defaultClassPrefix()})\n";
    echo "Actual mask: {$subnet->mask()} (/{$subnet->networkSize()})\n";

    if ($subnet->isClassful()) {
        echo "This subnet uses its natural classful boundary.\n";
    } else {
        $actualPrefix = $subnet->networkSize();
        $classfulPrefix = $subnet->defaultClassPrefix();

        if ($actualPrefix > $classfulPrefix) {
            $bits = $actualPrefix - $classfulPrefix;
            echo "This subnet has been subnetted using {$bits} additional bit(s).\n";
        } else {
            $bits = $classfulPrefix - $actualPrefix;
            echo "This subnet has been supernetted using {$bits} fewer bit(s).\n";
        }
    }

    echo "\n";
}

// Examples
analyzeSubnet('10.0.0.0/8');    // Classful Class A
analyzeSubnet('10.0.0.0/16');   // Subnetted Class A
analyzeSubnet('172.16.0.0/16'); // Classful Class B
analyzeSubnet('172.16.0.0/12'); // Supernetted Class B
```

## Next Steps

- **[Core Features](core-features.md)** - Network calculations, IP operations, overlap detection
- **[Reports](reports.md)** - Generate comprehensive network reports in multiple formats
- **[API Reference](api-reference.md)** - Complete method documentation
- **[Real-World Examples](examples.md)** - Practical patterns and use cases
