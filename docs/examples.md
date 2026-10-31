# Real-World Examples

Practical examples demonstrating how to use the IPv4 Subnet Calculator library in real-world scenarios.

Run each example separately after loading Composer's autoloader:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

## Table of Contents
- [Network Planning and IPAM](#network-planning-and-ipam)
- [Firewall and Security](#firewall-and-security)
- [BGP Route Summarization](#bgp-route-summarization)
- [DHCP Scope Planning](#dhcp-scope-planning)
- [DNS Configuration](#dns-configuration)
- [Network Automation](#network-automation)

---

## Network Planning and IPAM

### Retrieve an Indexed Subnet and Host

Retrieve a numbered `/24` from a large allocation, then select a host within it without enumerating earlier subnets or hosts:

```php
$allocation = IPv4\Subnet::fromCidr('10.0.0.0/8');
$office = $allocation->subnetAt(24, 513); // Zero-based: 10.2.1.0/24
$hosts = $office->hostRange();

echo $office->cidr() . "\n"; // 10.2.1.0/24
echo $hosts->at(0) . "\n"; // 10.2.1.1, first usable host
echo $hosts->at(99) . "\n"; // 10.2.1.100

$index = $hosts->indexOf('10.2.1.100');
if ($index !== null) {
    echo "Host index: {$index}\n"; // Host index: 99
}

// To select a random usable host, choose a valid index.
$randomHost = $hosts->at(\random_int(0, $hosts->count() - 1));
```

Indices start at zero and must be below the range or child count; invalid indices throw `\OutOfRangeException`. Child prefixes follow the splitting rules and are validated before indices. `indexOf()` returns `null` for an address outside the range and rejects malformed strings with `\InvalidArgumentException`. Random selection does not reserve an address or prevent repeats.

### Office Subnet Allocation

Allocate subnets to different office locations from a corporate address block.

```php
<?php
// Corporate allocation: 10.0.0.0/16
$corporateBlock = IPv4\Subnet::fromCidr('10.0.0.0/16');

// Office requirements
$offices = [
    'New York'     => 500,
    'London'       => 300,
    'Tokyo'        => 250,
    'Sydney'       => 150,
    'Branch-Small' => 50,
];

echo "Corporate IP Allocation Plan\n";
echo \str_repeat('=', 80) . "\n\n";
echo "Available: {$corporateBlock->cidr()}\n";
echo "Total IPs: {$corporateBlock->addressCount()}\n\n";

// Allocate largest offices first (VLSM) so each subnet starts on a boundary
// aligned to its size. Allocating a larger subnet after a smaller one
// would round its network address backward into space already in use.
\arsort($offices);

$currentNetwork = $corporateBlock->networkAddress()->asQuads();
$allocations = [];

foreach ($offices as $office => $requiredHosts) {
    // Find optimal subnet size
    $subnet = IPv4\SubnetParser::fromHostCount($currentNetwork, $requiredHosts);

    // Verify subnet fits within corporate block
    if (!$corporateBlock->contains($subnet)) {
        echo "ERROR: Insufficient space for {$office}\n";
        break;
    }

    $allocations[$office] = $subnet;

    echo "{$office}:\n";
    echo "  Required hosts: {$requiredHosts}\n";
    echo "  Allocated: {$subnet->cidr()}\n";
    echo "  Usable hosts: {$subnet->hostCount()}\n";
    echo "  Range: {$subnet->hostRange()}\n";
    echo "  Utilization: {$subnet->utilizationFor($requiredHosts)}%\n\n";

    // Move to next available network
    $currentNetwork = $subnet->next()->networkAddress()->asQuads();
}

// Calculate remaining space
$remaining = $corporateBlock->excludeAll(\array_values($allocations));

echo "Remaining unallocated space:\n";
$totalRemaining = 0;
foreach ($remaining as $block) {
    echo "  {$block->cidr()} ({$block->addressCount()} IPs)\n";
    $totalRemaining += $block->addressCount();
}
echo "\nTotal remaining: {$totalRemaining} IP addresses\n";
```

### Progressive IPAM with Allocation Tracking

Build a simple IPAM system that tracks allocations and automatically manages remaining space.

```php
<?php

class SimpleIPAM {
    private $name;
    private $availableBlocks;
    private $allocations;

    public function __construct($name, $initialBlock) {
        $this->name = $name;
        $this->availableBlocks = [
            IPv4\Subnet::fromCidr($initialBlock)
        ];
        $this->allocations = [];
    }

    public function allocate($name, $cidr) {
        // Reject duplicate names before touching the pool, or the earlier
        // allocation would be overwritten and its block lost from tracking
        if (isset($this->allocations[$name])) {
            throw new \Exception("Allocation name {$name} is already in use");
        }

        $allocation = IPv4\Subnet::fromCidr($cidr);

        foreach ($this->availableBlocks as $index => $block) {
            if ($block->contains($allocation)) {
                // Replace the containing free block with what remains of it.
                \array_splice($this->availableBlocks, $index, 1, $block->exclude($allocation));
                $this->allocations[$name] = $allocation;
                return $allocation;
            }
        }

        throw new \Exception("Requested subnet {$cidr} is not available in pool");
    }

    public function findAvailableSubnet($requiredHosts) {
        $optimalPrefix = IPv4\SubnetParser::optimalPrefixForHosts($requiredHosts);

        foreach ($this->availableBlocks as $block) {
            if ($block->networkSize() <= $optimalPrefix) {
                // A smaller subnet starting at this block's network address always fits.
                return (new IPv4\Subnet($block->networkAddress(), $optimalPrefix))->cidr();
            }
        }

        return null;
    }

    public function getUtilizationReport() {
        $report = "IPAM Pool: {$this->name}\n";
        $report .= \str_repeat('=', 80) . "\n\n";

        $report .= "Allocations:\n";
        $report .= \str_repeat('-', 80) . "\n";
        foreach ($this->allocations as $name => $subnet) {
            $report .= \sprintf("  %-30s %s (%d IPs)\n",
                $name,
                $subnet->cidr(),
                $subnet->addressCount()
            );
        }

        $report .= "\nAvailable Blocks:\n";
        $report .= \str_repeat('-', 80) . "\n";
        $totalAvailable = 0;
        foreach ($this->availableBlocks as $block) {
            $report .= \sprintf("  %s (%d IPs)\n",
                $block->cidr(),
                $block->addressCount()
            );
            $totalAvailable += $block->addressCount();
        }
        $report .= "\nTotal available: {$totalAvailable} IP addresses\n";

        return $report;
    }
}

// Usage example
$ipam = new SimpleIPAM('Corporate Network', '10.0.0.0/16');

// Make allocations
$ipam->allocate('Web Servers', '10.0.0.0/24');
$ipam->allocate('Database Servers', '10.0.1.0/24');
$ipam->allocate('Application Servers', '10.0.2.0/23');

// Find available space for new requirement
$suggested = $ipam->findAvailableSubnet(100);
echo "Suggested subnet for 100 hosts: {$suggested}\n\n";

// Show utilization
echo $ipam->getUtilizationReport();
```

### Strict Network Configuration Import

Collect valid network blocks and report rejected configuration entries. `fromNetworkCidr()` preserves the normal CIDR validation errors and adds rejection of host bits. Use `isNetworkAddress()` to inspect alignment on an existing subnet; `fromCidr()` and the constructor still accept host inputs.

```php
$configuredCidrs = ['192.168.1.0/24', '192.168.1.50/24', '192.0.2.6/31', '255.255.255.255/32'];
$networks = [];
$errors = [];

foreach ($configuredCidrs as $cidr) {
    try {
        $networks[] = IPv4\Subnet::fromNetworkCidr($cidr);
    } catch (\InvalidArgumentException $error) {
        $errors[] = $error->getMessage();
    }
}

echo \implode(', ', \array_map(static fn (IPv4\Subnet $network): string => $network->cidr(), $networks));
// 192.168.1.0/24, 192.0.2.6/31, 255.255.255.255/32
echo "\n" . \implode("\n", $errors) . "\n";
// Invalid network CIDR: host bits are set in '192.168.1.50/24'

$host = IPv4\Subnet::fromCidr('192.168.1.50/24');
\var_dump($host->isNetworkAddress()); // false; the original IP is retained
```

Only `0.0.0.0/0` is aligned for `/0`; `/31` requires an even IP and every valid `/32` IP is aligned.

### Sort Network Blocks

Sort by numeric network address, with broader prefixes first at an equal start. `compare()` returns exactly `-1`, `0` or `1` and ignores input host bits; zero agrees with `equals()`.

```php
$networks = \array_map(static fn (string $cidr): IPv4\Subnet => IPv4\Subnet::fromCidr($cidr), [
    '10.0.0.1/16',
    '255.255.255.255/32',
    '2.0.0.1/8',
    '10.255.255.255/8',
    '192.0.2.7/31',
    '255.255.255.255/0',
]);
\usort($networks, static fn (IPv4\Subnet $a, IPv4\Subnet $b): int => $a->compare($b));

foreach ($networks as $network) {
    echo $network->networkCidr() . "\n";
}
// 0.0.0.0/0
// 2.0.0.0/8
// 10.0.0.0/8
// 10.0.0.0/16
// 192.0.2.6/31
// 255.255.255.255/32
```

The objects retain their original host IPs; `networkCidr()` formats the sorted blocks canonically. Numeric order places `2.0.0.0/8` before `10.0.0.0/8`, while `/8` precedes `/16` at the shared `10.0.0.0` start.

### Find Adjacent Network Blocks

Use `isAdjacent()` to find neighbors of one allocation without treating overlap, containment or a gap as adjacency. The check covers the full range:

```php
$current = IPv4\Subnet::fromCidr('10.0.1.50/24');
$candidates = ['10.0.0.0/24', '10.0.2.0/25', '10.0.2.128/25', '10.0.1.128/25', '10.0.3.0/24'];

foreach ($candidates as $cidr) {
    $other = IPv4\Subnet::fromCidr($cidr);
    if ($current->isAdjacent($other)) {
        echo $other->networkCidr() . "\n";
    }
}
// 10.0.0.0/24
// 10.0.2.0/25
```

The relation is symmetric and permits unequal prefixes; host bits in `$current` do not change its range. Touching blocks need not merge: `10.0.1.0/24` and `10.0.2.0/24` are adjacent but are not aligned siblings. `/0` has no adjacent block; consecutive `/31` or `/32` blocks can touch, and the maximum IPv4 address never wraps to zero.

---

## Firewall and Security

### Convert an Allowed Range to CIDRs

For a firewall that accepts CIDR entries, convert an inclusive address range without permitting extra addresses:

```php
$allowed = new IPv4\IPRange(
    new IPv4\IPAddress('192.0.2.1'),
    new IPv4\IPAddress('192.0.2.6')
);
$allowedCidrs = \array_map(
    static fn (IPv4\Subnet $subnet): string => $subnet->cidr(),
    $allowed->toSubnets()
);

echo \implode("\n", $allowedCidrs) . "\n";
// 192.0.2.1/32
// 192.0.2.2/31
// 192.0.2.4/31
// 192.0.2.6/32
```

Each address in the range is covered once. Using `Subnets::summarize()` on these blocks would produce `192.0.2.0/29`, which also includes `.0` and `.7`.

### Audit Firewall Coverage

Check that permitted blocks reach every required destination, list the gaps, and find permissions outside the approved plan:

```php
$toSubnets = static fn (array $cidrs): array => \array_map(
    static fn (string $cidr): IPv4\Subnet => IPv4\Subnet::fromCidr($cidr),
    $cidrs
);
$toCidrs = static fn (array $subnets): string => \implode(', ', \array_map(
    static fn (IPv4\Subnet $subnet): string => $subnet->cidr(),
    $subnets
));

$permitted = $toSubnets(['10.10.0.0/25', '10.10.0.128/25', '10.10.2.0/24', '203.0.113.0/24']);
$required = $toSubnets(['web' => '10.10.0.0/24', 'db' => '10.10.2.10/32', 'backup' => '10.10.3.0/30']);
$approved = $toSubnets(['10.10.0.0/23', '10.10.2.0/24', '10.10.3.0/30']);

\var_dump(IPv4\Subnets::covers($permitted, $required)); // false
echo $toCidrs(IPv4\Subnets::difference($required, $permitted)) . "\n"; // 10.10.3.0/30
echo $toCidrs(IPv4\Subnets::difference($permitted, $approved)) . "\n"; // 203.0.113.0/24
```

The two permitted `/25`s together cover the required `/24`. Results compare addresses, not rule text, so splitting or merging rules does not change them.

### Validate Upstream Servers Are Globally Reachable

Reject configured upstream resolvers and NTP servers that address policy keeps off the public internet. `isPublic()` would also reject the globally reachable AS112 block, so use `isGloballyReachable()`:

```php
$upstreams = ['dns1' => '1.1.1.1', 'dns2' => '192.168.1.53', 'as112' => '192.175.48.6', 'ntp' => '192.0.2.123'];

$unreachable = \array_keys(\array_filter(
    $upstreams,
    static fn (string $ip): bool => !(new IPv4\IPAddress($ip))->isGloballyReachable()
));

echo \implode(', ', $unreachable) . "\n"; // dns2, ntp
```

A pass means only that the address is eligible for global forwarding; it does not prove the server is reachable or allocated.

### Firewall Rule Validation

Find overlapping address ranges and flag differing actions for review. Whether an overlap
is a conflict depends on the firewall's rule order and matching behavior.

```php
<?php

class FirewallRuleValidator {
    private $rules = [];

    public function addRule($name, $cidr, $action = 'allow') {
        $subnet = IPv4\Subnet::fromCidr($cidr);
        $this->rules[] = [
            'name' => $name,
            'subnet' => $subnet,
            'cidr' => $cidr,
            'action' => $action,
        ];
    }

    public function validateRules() {
        $conflicts = [];

        for ($i = 0; $i < \count($this->rules); $i++) {
            for ($j = $i + 1; $j < \count($this->rules); $j++) {
                $rule1 = $this->rules[$i];
                $rule2 = $this->rules[$j];

                if ($rule1['subnet']->overlaps($rule2['subnet'])) {
                    $conflicts[] = [
                        'rule1' => $rule1,
                        'rule2' => $rule2,
                        'type' => $this->getConflictType($rule1, $rule2),
                    ];
                }
            }
        }

        return $conflicts;
    }

    private function getConflictType($rule1, $rule2) {
        // Called only for overlapping CIDR blocks: one must contain the other.
        return $rule1['subnet']->contains($rule2['subnet'])
            ? 'rule1_contains_rule2'
            : 'rule2_contains_rule1';
    }

    public function printValidationReport() {
        echo "Firewall Rule Validation Report\n";
        echo \str_repeat('=', 80) . "\n\n";

        $conflicts = $this->validateRules();

        if (empty($conflicts)) {
            echo "✓ No overlapping address ranges detected.\n";
            return;
        }

        echo "⚠ Overlapping rule pairs: " . \count($conflicts) . "\n\n";

        foreach ($conflicts as $i => $conflict) {
            echo "Overlap #" . ($i + 1) . ":\n";
            echo "  Rule 1: {$conflict['rule1']['name']} ({$conflict['rule1']['cidr']}) - {$conflict['rule1']['action']}\n";
            echo "  Rule 2: {$conflict['rule2']['name']} ({$conflict['rule2']['cidr']}) - {$conflict['rule2']['action']}\n";
            echo "  Type: {$conflict['type']}\n";

            if ($conflict['rule1']['action'] !== $conflict['rule2']['action']) {
                echo "  ⚠ WARNING: Differing actions ({$conflict['rule1']['action']} vs {$conflict['rule2']['action']})\n";
            }

            echo "\n";
        }
    }
}

// Usage
$validator = new FirewallRuleValidator();

// Add firewall rules
$validator->addRule('Allow Internal', '10.0.0.0/8', 'allow');
$validator->addRule('Allow Office A', '10.1.0.0/16', 'allow');  // Contained in Internal
$validator->addRule('Block Office A Guest', '10.1.100.0/24', 'deny');  // Overlaps with differing action
$validator->addRule('Allow External Partners', '203.0.113.0/24', 'allow');
$validator->addRule('Allow Public Web', '0.0.0.0/0', 'allow');  // Contains everything!

$validator->printValidationReport();
```

### Access Control by Trusted Network

Authorize requests against the specific networks each resource trusts. Address classification
is not authorization: `isPrivate()` matches all of RFC 1918, including guest Wi-Fi, VPN pools and
container networks.

```php
<?php

function canAccess($ipAddress, $resource) {
    $policies = [
        'admin-panel'  => ['192.168.50.0/24', '127.0.0.0/8'],  // Admin VPN, localhost
        'internal-api' => ['10.20.0.0/16', '192.168.50.0/24'], // Office LAN, Admin VPN
        'public-api'   => ['0.0.0.0/0'],                       // Everyone
    ];

    // Unknown resources trust no networks.
    foreach ($policies[$resource] ?? [] as $cidr) {
        if (IPv4\Subnet::fromCidr($cidr)->containsIP($ipAddress)) {
            return true;
        }
    }

    return false;
}

foreach (['10.20.5.17', '192.168.50.10', '172.17.0.2', '8.8.8.8', '127.0.0.1'] as $ip) {
    echo "{$ip}:\n";
    foreach (['admin-panel', 'internal-api', 'public-api'] as $resource) {
        $status = canAccess($ip, $resource) ? 'ALLOWED' : 'DENIED';
        echo "  {$resource}: {$status}\n";
    }
}
// 172.17.0.2 is private, but has access only to the public API.
```

### Restrict a Network Block to a Scope

Use `intersection()` to intersect each requested block with one scope. An overlapping pair of CIDRs is nested, so the result is the canonical narrower block. A disjoint or adjacent pair returns `null`:

```php
$scope = IPv4\Subnet::fromCidr('10.1.2.3/8');
$requests = ['10.20.30.40/16', '255.255.255.255/0', '192.0.2.1/24'];

foreach ($requests as $cidr) {
    $shared = $scope->intersection(IPv4\Subnet::fromCidr($cidr));
    echo ($shared?->cidr() ?? 'no shared addresses') . "\n";
}
// 10.20.0.0/16
// 10.0.0.0/8
// no shared addresses
```

The result has a canonical input IP even when equal operands contain host bits. The relation is symmetric by value.

---

## BGP Route Summarization

### Multi-Region Route Aggregation

Aggregate routes within each region, grouping only routes with the same routing policy.
`aggregate()` preserves the exact address coverage; `summarize()` can include gaps.

```php
<?php

$regions = [
    'North America' => ['10.1.0.0/24', '10.1.1.0/24', '10.1.2.0/24', '10.1.3.0/24'],
    'Europe'        => ['10.2.0.0/24', '10.2.1.0/24', '10.2.4.0/24', '10.2.5.0/24'],
    'Asia'          => ['10.3.0.0/24', '10.3.1.0/24'],
];

foreach ($regions as $region => $cidrs) {
    $subnets = \array_map(fn($cidr) => IPv4\Subnet::fromCidr($cidr), $cidrs);
    $aggregated = IPv4\Subnets::aggregate($subnets);

    echo "{$region}: " . \count($subnets) . " routes -> " . \count($aggregated) . "\n";
    foreach ($aggregated as $subnet) {
        echo "  {$subnet->networkCidr()}\n";
    }
}
// Result summary:
// North America: 10.1.0.0/22
// Europe: 10.2.0.0/23 and 10.2.4.0/23 (the gap stays unadvertised)
// Asia: 10.3.0.0/23
```

---

## DHCP Scope Planning

### Optimal DHCP Scope Calculation

Calculate optimal DHCP scopes for different departments. Each `hosts` value must be a positive PHP integer.

```php
<?php

class DHCPScopePlanner {
    // First 10 hosts reserved for static assignments, last host for the gateway
    private const STATIC_RESERVED = 10;
    private const GATEWAY_RESERVED = 1;

    // Throws rather than returning a partial plan, so a failed plan can never be exported
    public function planScopes($baseNetwork, array $departments) {
        $baseSubnet = IPv4\Subnet::fromCidr($baseNetwork);
        $scopes = [];

        // Allocate largest scopes first (VLSM) so each scope starts on a boundary
        // aligned to its size. Allocating a larger scope after a smaller one
        // would round its network address backward into space already in use.
        \uasort($departments, fn($a, $b) => $b['hosts'] <=> $a['hosts']);

        $currentNetwork = $baseSubnet->networkAddress();

        foreach ($departments as $dept => $requirements) {
            $requiredHosts = $requirements['hosts'];
            if (!\is_int($requiredHosts) || $requiredHosts < 1) {
                throw new \InvalidArgumentException("{$dept}: Host requirement must be a positive integer");
            }

            // Size the scope so the DHCP pool still holds every required host
            // after the static and gateway reservations are taken out
            $reserved = self::STATIC_RESERVED + self::GATEWAY_RESERVED;
            $scope = IPv4\SubnetParser::fromHostCount($currentNetwork, $requiredHosts + $reserved);

            // Verify scope fits within base network
            if (!$baseSubnet->contains($scope)) {
                throw new \RuntimeException("Insufficient space in {$baseNetwork} for {$dept}");
            }

            // Calculate DHCP pool (reserve first 10 IPs for static, last IP for gateway)
            $pool = new IPv4\IPRange(
                IPv4\IPAddress::fromInteger($scope->minHost()->asInteger() + self::STATIC_RESERVED),
                IPv4\IPAddress::fromInteger($scope->maxHost()->asInteger() - self::GATEWAY_RESERVED)
            );

            $dns = new IPv4\IPAddress($requirements['dns'] ?? $scope->minHost()->asQuads());

            $scopes[$dept] = [
                'subnet' => $scope,
                'required_hosts' => $requiredHosts,
                'pool' => $pool,
                'gateway' => $scope->maxHost(),
                'dns' => $dns,
            ];

            $currentNetwork = $scope->next()->networkAddress();
        }

        $unassignable = [(string) $baseSubnet->networkAddress(), (string) $baseSubnet->broadcastAddress()];
        foreach ($scopes as $scope) {
            $unassignable[] = (string) $scope['subnet']->networkAddress();
            $unassignable[] = (string) $scope['subnet']->broadcastAddress();
        }

        foreach ($scopes as $dept => $scope) {
            // DNS cannot use a network or broadcast address in this plan.
            if (\in_array((string) $scope['dns'], $unassignable, true)) {
                throw new \InvalidArgumentException(
                    "DNS server {$scope['dns']} for {$dept} is a network or broadcast address"
                );
            }
            // Advertising an address as DNS does not reserve it, so a DNS server inside
            // any pool (its own department's or another's) could be leased to a client.
            foreach ($scopes as $poolDept => $pool) {
                if ($pool['pool']->contains($scope['dns'])) {
                    throw new \InvalidArgumentException(
                        "DNS server {$scope['dns']} for {$dept} is inside the DHCP pool for {$poolDept}"
                    );
                }
            }
        }

        $this->printReport($baseSubnet, $scopes);

        return $scopes;
    }

    private function printReport(IPv4\Subnet $baseSubnet, array $scopes) {
        echo "DHCP Scope Planning Report\n";
        echo \str_repeat('=', 80) . "\n";
        echo "Base Network: {$baseSubnet->networkCidr()}\n";
        echo "Available IPs: {$baseSubnet->addressCount()}\n\n";

        foreach ($scopes as $dept => $scope) {
            $subnet = $scope['subnet'];
            echo "{$dept}:\n";
            echo "  Required hosts: {$scope['required_hosts']}\n";
            echo "  Subnet: {$subnet->networkCidr()}\n";
            echo "  Subnet mask: {$subnet->mask()}\n";
            echo "  Usable hosts: {$subnet->hostCount()}\n";
            echo "  Utilization: {$subnet->utilizationFor($scope['required_hosts'])}%\n";
            echo "  DHCP Pool: {$scope['pool']}\n";
            echo "  Gateway: {$scope['gateway']}\n";
            echo "  DNS: {$scope['dns']}\n\n";
        }
    }

    public function exportDHCPConfig(array $scopes) {
        $config = "# ISC DHCP Server Configuration\n";
        $config .= "# Generated: " . \date('Y-m-d H:i:s') . "\n\n";

        foreach ($scopes as $name => $scope) {
            $subnet = $scope['subnet'];
            $config .= "# {$name}\n";
            $config .= "subnet {$subnet->networkAddress()} netmask {$subnet->mask()} {\n";
            $config .= "    range {$scope['pool']->start()} {$scope['pool']->end()};\n";
            $config .= "    option routers {$scope['gateway']};\n";
            $config .= "    option domain-name-servers {$scope['dns']};\n";
            $config .= "    option subnet-mask {$subnet->mask()};\n";
            $config .= "}\n\n";
        }

        return $config;
    }
}

// Usage
$planner = new DHCPScopePlanner();

$departments = [
    'Engineering' => ['hosts' => 100, 'dns' => '10.0.0.1'],
    'Sales' => ['hosts' => 50, 'dns' => '10.0.0.1'],
    'Guest WiFi' => ['hosts' => 200, 'dns' => '8.8.8.8'],
];

$scopes = $planner->planScopes('10.0.0.0/22', $departments);

echo "\n" . \str_repeat('=', 80) . "\n";
echo "Generated DHCP Configuration:\n";
echo \str_repeat('=', 80) . "\n\n";
echo $planner->exportDHCPConfig($scopes);
```

---

## DNS Configuration

### Reverse DNS Zone Generator

Generate reverse DNS zone files, one per zone origin.

`reverseZones()` returns the octet-aligned zone origins that exactly cover a network, without trailing dots. A /8, /16 or /24 has one origin; a /20 has sixteen /24 origins; a /25–/32 has one /32 origin per address, never the enclosing /24. Classless delegation of a /25–/31 as one child zone (RFC 2317) needs its own naming and CNAME records in the parent zone, which this example does not cover. The generator appends the trailing dot only when it writes absolute names in zone-file syntax.

Secondary servers compare the SOA serial to decide whether to transfer the zone (RFC 1034 §4.3.5), so every revision must carry a newer serial than the last. Serials are compared with RFC 1982 serial arithmetic: a serial is newer only if it is ahead of the previous one by 1 to 2³¹−1, modulo 2³². A jump of 2³¹ or more makes the zone look older, so a larger number is not always a newer serial. The generator takes the serial as an argument rather than deriving it from the date: store the serial of each zone you publish and pass its successor, `($previousSerial + 1) & 0xFFFFFFFF`, when you regenerate.

```php
<?php

class ReverseDNSZoneGenerator {
    // Returns zone file text keyed by zone origin
    public function generateZones($cidr, $nameservers, $serial, $hostnames = []) {
        $subnet = IPv4\Subnet::fromCidr($cidr);

        // SOA serials are unsigned 32-bit values (RFC 1982)
        if (!\is_int($serial) || $serial < 0 || $serial > 0xFFFFFFFF) {
            throw new InvalidArgumentException("Serial must be an integer from 0 to 4294967295");
        }

        $zones = [];
        foreach ($subnet->reverseZones() as $origin) {
            $zones[$origin] = $this->generateZone($subnet, $origin, $nameservers, $serial, $hostnames);
        }

        return $zones;
    }

    private function generateZone($subnet, $origin, $nameservers, $serial, $hostnames) {
        $zone = "; Reverse DNS Zone\n";
        $zone .= "; Network: {$subnet->networkCidr()}\n";
        $zone .= "; Generated: " . \date('Y-m-d H:i:s') . "\n\n";

        $zone .= "\$ORIGIN {$this->absolute($origin)}\n";
        $zone .= "\$TTL 86400\n";
        $zone .= "@    IN    SOA    {$this->absolute($nameservers[0])} hostmaster.example.com. (\n";
        $zone .= "            " . \str_pad((string) $serial, 18) . "; Serial\n";
        $zone .= "            3600              ; Refresh\n";
        $zone .= "            1800              ; Retry\n";
        $zone .= "            604800            ; Expire\n";
        $zone .= "            86400 )           ; Minimum TTL\n\n";

        // Nameservers
        foreach ($nameservers as $ns) {
            $zone .= "    IN    NS    {$this->absolute($ns)}\n";
        }
        $zone .= "\n";

        // PTR records use absolute owner names (trailing dot); an address belongs
        // to this zone when its PTR name ends with the origin
        foreach ($hostnames as $ip => $hostname) {
            $ptrRecord = (new IPv4\IPAddress($ip))->arpaDomain();
            if (\str_ends_with(".{$ptrRecord}", ".{$origin}")) {
                $zone .= "{$this->absolute($ptrRecord)}    IN    PTR    {$this->absolute($hostname)}\n";
            }
        }

        return $zone;
    }

    // Accept names with or without the trailing dot (RFC 1035 §5.1) and emit them absolute
    private function absolute($name) {
        return \rtrim($name, '.') . '.';
    }
}

// Usage
$generator = new ReverseDNSZoneGenerator();

$hostnames = [
    '192.168.0.1' => 'gateway.example.com',
    '192.168.0.10' => 'dns1.example.com',
    '192.168.0.11' => 'dns2.example.com',
    '192.168.1.20' => 'mail.example.com',
    '192.168.1.100' => 'web1.example.com',
    '10.0.0.5' => 'elsewhere.example.com',  // Outside the network: no zone receives it
];

// Serial of the previously published zone, read from wherever you store it
$previousSerial = 2026102401;

// A /23 spans two /24 reverse zones
$zones = $generator->generateZones(
    '192.168.0.0/23',
    ['ns1.example.com', 'ns2.example.com'],
    ($previousSerial + 1) & 0xFFFFFFFF,  // Successor wraps 4294967295 to 0 (RFC 1982)
    $hostnames
);

foreach ($zones as $origin => $zone) {
    echo "=== {$origin} ===\n{$zone}\n";
}
// === 0.168.192.in-addr.arpa ===  (PTRs for 192.168.0.1, .10 and .11)
// === 1.168.192.in-addr.arpa ===  (PTRs for 192.168.1.20 and .100)
```

---

## Network Automation

### Infrastructure-as-Code Network Validator

Validate network configurations in infrastructure-as-code deployments.

```php
<?php

class NetworkConfigValidator {
    private $errors = [];
    private $warnings = [];

    public function validateConfig(array $config) {
        $this->errors = [];
        $this->warnings = [];

        // Check the structure first; foreach over a non-array is skipped with only a warning
        foreach (['subnets', 'requirements'] as $section) {
            if (\array_key_exists($section, $config) && !\is_array($config[$section])) {
                $this->errors[] = "{$section}: Must be a map of network names";
            }
        }
        if (!empty($this->errors)) {
            return false;
        }

        // Parse each subnet and compare it with those already parsed.
        $subnets = [];
        foreach ($config['subnets'] ?? [] as $name => $cidr) {
            if (!\is_string($cidr)) {
                $this->errors[] = "{$name}: CIDR must be a string";
                continue;
            }
            try {
                $subnet = IPv4\Subnet::fromCidr($cidr);
            } catch (\InvalidArgumentException $e) {
                $this->errors[] = "{$name}: Invalid CIDR notation - {$e->getMessage()}";
                continue;
            }

            foreach ($subnets as $otherName => $other) {
                if ($subnet->overlaps($other)) {
                    $this->errors[] = "Overlap detected: {$otherName} and {$name}";
                }
            }
            $subnets[$name] = $subnet;
        }

        // Iterate requirements directly so missing networks and null requirements are checked too.
        foreach ($config['requirements'] ?? [] as $name => $required) {
            if (!\array_key_exists($name, $config['subnets'] ?? [])) {
                $this->errors[] = "{$name}: Required network is not defined";
                continue;
            }
            if (!\is_int($required) || $required < 1) {
                $this->errors[] = "{$name}: Host requirement must be a positive integer";
                continue;
            }
            if (!isset($subnets[$name])) {
                continue;  // Invalid CIDR already reported above.
            }

            $available = $subnets[$name]->hostCount();
            if ($available < $required) {
                $this->errors[] = "{$name}: Insufficient capacity. Required: {$required}, Available: {$available}";
            } elseif ($available > $required * 2) {
                $this->warnings[] = "{$name}: Subnet may be oversized. Required: {$required}, Available: {$available}";
            }
        }

        // Validate address types against the whole block. isPrivate() and isPublic() classify
        // only the input IP, so they would miss a block that straddles an RFC 1918 boundary.
        $rfc1918 = \array_map(
            fn($cidr) => IPv4\Subnet::fromCidr($cidr),
            ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']
        );
        foreach ($subnets as $name => $subnet) {
            $touchesPrivate = \array_filter($rfc1918, fn($block) => $subnet->overlaps($block)) !== [];
            $entirelyPrivate = \array_filter($rfc1918, fn($block) => $subnet->isContainedIn($block)) !== [];

            if (\str_contains($name, 'public') && $touchesPrivate) {
                $this->warnings[] = "{$name}: Named 'public' but includes private address space";
            }
            if (\str_contains($name, 'private') && !$entirelyPrivate) {
                $this->warnings[] = "{$name}: Named 'private' but is not entirely within private address space";
            }
        }

        return empty($this->errors);
    }

    public function getReport() {
        $report = "Network Configuration Validation Report\n";
        $report .= \str_repeat('=', 80) . "\n\n";

        if (!empty($this->errors)) {
            $report .= "ERRORS (" . \count($this->errors) . "):\n";
            $report .= \str_repeat('-', 80) . "\n";
            foreach ($this->errors as $error) {
                $report .= "  ✗ {$error}\n";
            }
            $report .= "\n";
        }

        if (!empty($this->warnings)) {
            $report .= "WARNINGS (" . \count($this->warnings) . "):\n";
            $report .= \str_repeat('-', 80) . "\n";
            foreach ($this->warnings as $warning) {
                $report .= "  ⚠ {$warning}\n";
            }
            $report .= "\n";
        }

        if (empty($this->errors) && empty($this->warnings)) {
            $report .= "✓ Configuration is valid. No errors or warnings.\n";
        }

        return $report;
    }
}

// Usage in CI/CD pipeline
$config = [
    'subnets' => [
        'web-tier' => '10.0.1.0/24',
        'app-tier' => '10.0.2.0/24',
        'data-tier' => '10.0.3.0/24',
        'management' => '10.0.0.0/28',
    ],
    'requirements' => [
        'web-tier' => 50,
        'app-tier' => 100,
        'data-tier' => 20,
        'management' => 10,
    ],
];

$validator = new NetworkConfigValidator();

if (!$validator->validateConfig($config)) {
    echo $validator->getReport();
    exit(1);  // Fail CI/CD pipeline
}

echo $validator->getReport();
echo "\n✓ Validation passed. Proceeding with deployment...\n";
```

### Network Change Impact Analysis

Analyze the impact of network changes before implementation.

```php
<?php

function analyzeNetworkChange($currentCidr, $proposedCidr) {
    $current = IPv4\Subnet::fromCidr($currentCidr);
    $proposed = IPv4\Subnet::fromCidr($proposedCidr);

    echo "Current: {$current->networkCidr()} ({$current->hostCount()} hosts)\n";
    echo "Proposed: {$proposed->networkCidr()} ({$proposed->hostCount()} hosts)\n";
    echo "Current host range: {$current->hostRange()}\n";
    echo "Proposed host range: {$proposed->hostRange()}\n";
    $capacityChange = $proposed->hostCount() - $current->hostCount();
    echo \sprintf("Capacity change: %+d hosts\n", $capacityChange);

    if ($current->equals($proposed)) {
        echo "No subnet change.\n";
    } elseif (!$current->overlaps($proposed)) {
        echo "No overlap: all hosts need new addresses. Update DHCP, static IPs, firewall rules and DNS.\n";
    } elseif ($proposed->contains($current)) {
        // Checking the endpoints covers the entire contiguous host range.
        // Expanding a /31 or /32 can turn a host into the new network or broadcast address.
        $proposedHosts = $proposed->hostRange();
        $hostsPreserved = $proposedHosts->contains($current->minHost())
            && $proposedHosts->contains($current->maxHost());

        echo "Expansion: review the DHCP scope.\n";
        echo $hostsPreserved
            ? "All current host addresses remain usable.\n"
            : "Move hosts off the new network or broadcast address.\n";
    } else {
        // Overlapping CIDR blocks are nested, so this must be a contraction.
        echo "Contraction: audit allocations and move hosts that fall outside the proposed host range.\n";
    }
}

// Usage
analyzeNetworkChange('192.168.1.0/24', '192.168.0.0/23');  // Expansion
echo "\n" . \str_repeat('=', 80) . "\n\n";
analyzeNetworkChange('192.168.1.0/24', '192.168.1.0/25');  // Contraction
echo "\n" . \str_repeat('=', 80) . "\n\n";
analyzeNetworkChange('192.168.1.0/24', '192.168.2.0/24');  // Complete change
echo "\n" . \str_repeat('=', 80) . "\n\n";
analyzeNetworkChange('192.168.1.0/31', '192.168.1.0/30');  // Expansion that consumes a host
```

---

## Related Documentation

- **[Getting Started](getting-started.md)** - Installation and basic usage
- **[Core Features](core-features.md)** - Detailed feature documentation
- **[Advanced Features](advanced-features.md)** - Advanced operations
- **[API Reference](api-reference.md)** - Complete method documentation
- **[Reports](reports.md)** - Report generation
