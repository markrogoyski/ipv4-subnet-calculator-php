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

---

## Firewall and Security

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

Generate reverse DNS zone configurations.

Reverse zones are delegated on octet boundaries, so this generator accepts /8, /16 and /24 networks. Classless delegation of longer prefixes (RFC 2317) needs a child zone with its own naming and CNAME records in the parent zone, which this example does not cover.

Secondary servers compare the SOA serial to decide whether to transfer the zone (RFC 1034 §4.3.5), so every revision must carry a newer serial than the last. Serials are compared with RFC 1982 serial arithmetic: a serial is newer only if it is ahead of the previous one by 1 to 2³¹−1, modulo 2³². A jump of 2³¹ or more makes the zone look older, so a larger number is not always a newer serial. The generator takes the serial as an argument rather than deriving it from the date: store the serial of each zone you publish and pass its successor, `($previousSerial + 1) & 0xFFFFFFFF`, when you regenerate.

```php
<?php

class ReverseDNSZoneGenerator {
    public function generateZone($cidr, $nameservers, $serial, $hostnames = []) {
        $subnet = IPv4\Subnet::fromCidr($cidr);

        if (!\in_array($subnet->networkSize(), [8, 16, 24], true)) {
            throw new InvalidArgumentException(
                "Reverse zones must be /8, /16 or /24; {$cidr} needs RFC 2317 classless delegation"
            );
        }

        // SOA serials are unsigned 32-bit values (RFC 1982)
        if (!\is_int($serial) || $serial < 0 || $serial > 0xFFFFFFFF) {
            throw new InvalidArgumentException("Serial must be an integer from 0 to 4294967295");
        }

        $zone = "; Reverse DNS Zone\n";
        $zone .= "; Network: {$subnet->cidr()}\n";
        $zone .= "; Generated: " . \date('Y-m-d H:i:s') . "\n\n";

        // Zone origin: the network octets in reverse, e.g. 192.168.1.0/24 => 1.168.192.in-addr.arpa
        $networkOctets = \array_slice(\explode('.', $subnet->networkAddress()->asQuads()), 0, \intdiv($subnet->networkSize(), 8));
        $origin = \implode('.', \array_reverse($networkOctets)) . '.in-addr.arpa';

        $zone .= "\$ORIGIN {$origin}.\n";
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

        // PTR records use absolute owner names (trailing dot); every address in an
        // octet-aligned network falls within the zone origin
        foreach ($hostnames as $ip => $hostname) {
            if ($subnet->containsIP($ip)) {
                $ptrRecord = (new IPv4\IPAddress($ip))->arpaDomain();
                $zone .= "{$ptrRecord}.    IN    PTR    {$this->absolute($hostname)}\n";
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
    '192.168.1.1' => 'gateway.example.com',
    '192.168.1.10' => 'dns1.example.com',
    '192.168.1.11' => 'dns2.example.com',
    '192.168.1.20' => 'mail.example.com',
    '192.168.1.100' => 'web1.example.com',
];

// Serial of the previously published zone, read from wherever you store it
$previousSerial = 2026102401;

$zone = $generator->generateZone(
    '192.168.1.0/24',
    ['ns1.example.com', 'ns2.example.com'],
    ($previousSerial + 1) & 0xFFFFFFFF,  // Successor wraps 4294967295 to 0 (RFC 1982)
    $hostnames
);

echo $zone;
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
