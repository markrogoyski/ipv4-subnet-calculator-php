# Real-World Examples

Practical examples demonstrating how to use the IPv4 Subnet Calculator library in real-world scenarios.

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
require_once(__DIR__ . '/vendor/autoload.php');

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
    $optimalPrefix = IPv4\SubnetParser::optimalPrefixForHosts($requiredHosts);
    $subnet = IPv4\Subnet::fromCidr("{$currentNetwork}/{$optimalPrefix}");

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
    echo "  Range: {$subnet->minHost()} - {$subnet->maxHost()}\n";
    echo "  Utilization: {$subnet->utilizationFor($requiredHosts)}%\n\n";

    // Move to next available network
    $currentNetwork = $subnet->next()->networkAddress()->asQuads();
}

// Calculate remaining space
$allocated = \array_values($allocations);
$remaining = $corporateBlock->excludeAll($allocated);

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

        // Check if requested subnet is available
        $found = false;
        foreach ($this->availableBlocks as $block) {
            if ($block->contains($allocation)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new \Exception("Requested subnet {$cidr} is not available in pool");
        }

        // Update available blocks by excluding allocation
        $newAvailable = [];
        foreach ($this->availableBlocks as $block) {
            if ($block->contains($allocation)) {
                $remaining = $block->exclude($allocation);
                $newAvailable = \array_merge($newAvailable, $remaining);
            } else {
                $newAvailable[] = $block;
            }
        }

        $this->availableBlocks = $newAvailable;
        $this->allocations[$name] = $allocation;

        return $allocation;
    }

    public function findAvailableSubnet($requiredHosts) {
        $optimalPrefix = IPv4\SubnetParser::optimalPrefixForHosts($requiredHosts);

        foreach ($this->availableBlocks as $block) {
            if ($block->networkSize() <= $optimalPrefix) {
                // This block is large enough
                $subnet = IPv4\Subnet::fromCidr(
                    $block->networkAddress()->asQuads() . '/' . $optimalPrefix
                );

                if ($block->contains($subnet)) {
                    return $subnet->cidr();
                }
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

Validate that firewall rules don't have conflicting or overlapping entries.

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
        if ($rule1['subnet']->contains($rule2['subnet'])) {
            return 'rule1_contains_rule2';
        } elseif ($rule2['subnet']->contains($rule1['subnet'])) {
            return 'rule2_contains_rule1';
        } else {
            return 'partial_overlap';
        }
    }

    public function printValidationReport() {
        echo "Firewall Rule Validation Report\n";
        echo \str_repeat('=', 80) . "\n\n";

        $conflicts = $this->validateRules();

        if (empty($conflicts)) {
            echo "✓ No conflicts detected. All rules are valid.\n";
            return;
        }

        echo "⚠ CONFLICTS DETECTED: " . \count($conflicts) . "\n\n";

        foreach ($conflicts as $i => $conflict) {
            echo "Conflict #" . ($i + 1) . ":\n";
            echo "  Rule 1: {$conflict['rule1']['name']} ({$conflict['rule1']['cidr']}) - {$conflict['rule1']['action']}\n";
            echo "  Rule 2: {$conflict['rule2']['name']} ({$conflict['rule2']['cidr']}) - {$conflict['rule2']['action']}\n";
            echo "  Type: {$conflict['type']}\n";

            if ($conflict['rule1']['action'] !== $conflict['rule2']['action']) {
                echo "  ⚠ WARNING: Conflicting actions ({$conflict['rule1']['action']} vs {$conflict['rule2']['action']})\n";
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
$validator->addRule('Block Office A Guest', '10.1.100.0/24', 'deny');  // Conflicts!
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

class AccessController {
    public function checkAccess($ipAddress, $resource) {
        // Unknown resources trust no networks
        foreach ($this->getAccessPolicy($resource) as $cidr) {
            if (IPv4\Subnet::fromCidr($cidr)->containsIP($ipAddress)) {
                return true;
            }
        }

        return false;
    }

    private function getAccessPolicy($resource) {
        $policies = [
            'admin-panel'  => ['192.168.50.0/24', '127.0.0.0/8'],  // Admin VPN, localhost
            'internal-api' => ['10.20.0.0/16', '192.168.50.0/24'], // Office LAN, Admin VPN
            'public-api'   => ['0.0.0.0/0'],                       // Everyone
        ];

        return $policies[$resource] ?? [];
    }

    public function generateAccessReport($ipAddress) {
        $subnet = IPv4\Subnet::fromCidr($ipAddress . '/32');

        echo "Access Report for {$ipAddress}\n";
        echo \str_repeat('=', 80) . "\n";
        echo "IP Type: {$subnet->addressType()->value}\n";  // Use ->value for string
        echo "Private: " . ($subnet->isPrivate() ? 'Yes' : 'No') . "\n";
        echo "Public: " . ($subnet->isPublic() ? 'Yes' : 'No') . "\n";
        echo "Loopback: " . ($subnet->isLoopback() ? 'Yes' : 'No') . "\n\n";

        echo "Access Permissions:\n";
        $resources = ['admin-panel', 'public-api', 'internal-api'];
        foreach ($resources as $resource) {
            $access = $this->checkAccess($ipAddress, $resource);
            $status = $access ? '✓ ALLOWED' : '✗ DENIED';
            echo "  {$resource}: {$status}\n";
        }
    }
}

// Usage
$ac = new AccessController();

echo "Office User:\n";
$ac->generateAccessReport('10.20.5.17');
echo "\n";

echo "Admin VPN User:\n";
$ac->generateAccessReport('192.168.50.10');
echo "\n";

echo "Container (private, but untrusted):\n";
$ac->generateAccessReport('172.17.0.2');
echo "\n";

echo "External User:\n";
$ac->generateAccessReport('8.8.8.8');
echo "\n";

echo "Localhost:\n";
$ac->generateAccessReport('127.0.0.1');
```

---

## BGP Route Summarization

### Multi-Region Route Aggregation

Aggregate multiple regional subnets for BGP advertisement.

```php
<?php

class BGPRouteOptimizer {
    private $regions = [];

    public function addRegionalRoutes($region, array $cidrs) {
        $subnets = \array_map(function($cidr) {
            return IPv4\Subnet::fromCidr($cidr);
        }, $cidrs);

        $this->regions[$region] = $subnets;
    }

    public function optimizeRoutes() {
        $optimized = [];

        foreach ($this->regions as $region => $subnets) {
            $aggregated = IPv4\Subnets::aggregate($subnets);
            $optimized[$region] = $aggregated;
        }

        return $optimized;
    }

    public function printOptimizationReport() {
        echo "BGP Route Optimization Report\n";
        echo \str_repeat('=', 80) . "\n\n";

        $totalOriginal = 0;
        $totalOptimized = 0;

        foreach ($this->regions as $region => $subnets) {
            echo "Region: {$region}\n";
            echo \str_repeat('-', 80) . "\n";

            echo "Original routes (" . \count($subnets) . "):\n";
            foreach ($subnets as $subnet) {
                echo "  {$subnet->cidr()}\n";
            }

            $aggregated = IPv4\Subnets::aggregate($subnets);
            echo "\nOptimized routes (" . \count($aggregated) . "):\n";
            foreach ($aggregated as $subnet) {
                echo "  {$subnet->cidr()}\n";
            }

            $reduction = \count($subnets) - \count($aggregated);
            echo "\nReduction: {$reduction} routes\n\n";

            $totalOriginal += \count($subnets);
            $totalOptimized += \count($aggregated);
        }

        $totalReduction = $totalOriginal - $totalOptimized;
        $percentReduction = $totalOriginal > 0
            ? \round(($totalReduction / $totalOriginal) * 100, 2)
            : 0.0;

        echo \str_repeat('=', 80) . "\n";
        echo "Total Summary:\n";
        echo "  Original routes: {$totalOriginal}\n";
        echo "  Optimized routes: {$totalOptimized}\n";
        echo "  Reduction: {$totalReduction} routes ({$percentReduction}%)\n";
    }
}

// Usage
$bgp = new BGPRouteOptimizer();

// North America routes
$bgp->addRegionalRoutes('North America', [
    '10.1.0.0/24',
    '10.1.1.0/24',
    '10.1.2.0/24',
    '10.1.3.0/24',
]);

// Europe routes
$bgp->addRegionalRoutes('Europe', [
    '10.2.0.0/24',
    '10.2.1.0/24',
    '10.2.4.0/24',  // Gap - cannot aggregate with above
    '10.2.5.0/24',
]);

// Asia routes
$bgp->addRegionalRoutes('Asia', [
    '10.3.0.0/24',
    '10.3.1.0/24',
]);

$bgp->printOptimizationReport();
```

---

## DHCP Scope Planning

### Optimal DHCP Scope Calculation

Calculate optimal DHCP scopes for different departments.

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

        $currentNetwork = $baseSubnet->networkAddress()->asQuads();

        foreach ($departments as $dept => $requirements) {
            $requiredHosts = $requirements['hosts'];

            // Size the scope so the DHCP pool still holds every required host
            // after the static and gateway reservations are taken out
            $reserved = self::STATIC_RESERVED + self::GATEWAY_RESERVED;
            $optimalPrefix = IPv4\SubnetParser::optimalPrefixForHosts($requiredHosts + $reserved);
            $scope = IPv4\Subnet::fromCidr("{$currentNetwork}/{$optimalPrefix}");

            // Verify scope fits within base network
            if (!$baseSubnet->contains($scope)) {
                throw new \RuntimeException("Insufficient space in {$baseNetwork} for {$dept}");
            }

            // Calculate DHCP pool (reserve first 10 IPs for static, last IP for gateway)
            $poolStart = $scope->minHost()->asInteger() + self::STATIC_RESERVED;
            $poolEnd = $scope->maxHost()->asInteger() - self::GATEWAY_RESERVED;

            if ($poolEnd - $poolStart + 1 < $requiredHosts) {
                throw new \RuntimeException("DHCP pool for {$dept} cannot hold {$requiredHosts} hosts");
            }

            $dns = new IPv4\IPAddress($requirements['dns'] ?? $scope->minHost()->asQuads());

            $scopes[$dept] = [
                'subnet' => $scope,
                'required_hosts' => $requiredHosts,
                'scope_start' => \long2ip($poolStart),
                'scope_end' => \long2ip($poolEnd),
                'gateway' => $scope->maxHost(),
                'dns' => $dns,
            ];

            $currentNetwork = $scope->next()->networkAddress()->asQuads();
        }

        // Advertising an address as DNS does not reserve it, so a DNS server inside
        // any pool (its own department's or another's) could be leased to a client
        foreach ($scopes as $dept => $scope) {
            foreach ($scopes as $poolDept => $pool) {
                $dns = $scope['dns']->asInteger();
                if ($dns >= \ip2long($pool['scope_start']) && $dns <= \ip2long($pool['scope_end'])) {
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
            echo "  DHCP Pool: {$scope['scope_start']} - {$scope['scope_end']}\n";
            echo "  Gateway: {$scope['gateway']}\n";
            echo "  DNS: {$scope['dns']}\n\n";
        }
    }

    public function exportDHCPConfig(array $scopes, $serverType = 'isc-dhcp') {
        if ($serverType === 'isc-dhcp') {
            return $this->exportISCDHCPConfig($scopes);
        }
        return '';
    }

    private function exportISCDHCPConfig(array $scopes) {
        $config = "# ISC DHCP Server Configuration\n";
        $config .= "# Generated: " . \date('Y-m-d H:i:s') . "\n\n";

        foreach ($scopes as $name => $scope) {
            $subnet = $scope['subnet'];
            $config .= "# {$name}\n";
            $config .= "subnet {$subnet->networkAddress()->asQuads()} netmask {$subnet->mask()} {\n";
            $config .= "    range {$scope['scope_start']} {$scope['scope_end']};\n";
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
echo $planner->exportDHCPConfig($scopes, 'isc-dhcp');
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

        echo "Reverse DNS Zone Configuration\n";
        echo \str_repeat('=', 80) . "\n";
        echo "Subnet: {$cidr}\n";
        echo "Network: {$subnet->networkAddress()->asQuads()}\n";
        echo "Broadcast: {$subnet->broadcastAddress()->asQuads()}\n\n";

        // Generate zone file
        $zone = $this->generateZoneFile($subnet, $nameservers, $serial, $hostnames);

        return $zone;
    }

    private function generateZoneFile($subnet, $nameservers, $serial, $hostnames) {
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

        // Validate subnets
        $subnets = [];
        foreach ($config['subnets'] ?? [] as $name => $cidr) {
            if (!\is_string($cidr)) {
                $this->errors[] = "{$name}: CIDR must be a string";
                continue;
            }
            try {
                $subnet = IPv4\Subnet::fromCidr($cidr);
                $subnets[$name] = $subnet;

                // Check if subnet is properly sized. \array_key_exists() rather than isset(),
                // so a null requirement is reported instead of silently skipped.
                if (\array_key_exists($name, $config['requirements'] ?? [])) {
                    $required = $config['requirements'][$name];
                    $available = $subnet->hostCount();

                    if (!\is_int($required) || $required < 1) {
                        $this->errors[] = "{$name}: Host requirement must be a positive integer";
                    } elseif ($available < $required) {
                        $this->errors[] = "{$name}: Insufficient capacity. Required: {$required}, Available: {$available}";
                    } elseif ($available > $required * 2) {
                        $this->warnings[] = "{$name}: Subnet may be oversized. Required: {$required}, Available: {$available}";
                    }
                }
            } catch (\Exception $e) {
                $this->errors[] = "{$name}: Invalid CIDR notation - {$e->getMessage()}";
            }
        }

        // Every required network must be defined; the loop above only sizes the ones that are
        foreach ($config['requirements'] ?? [] as $name => $required) {
            if (!\array_key_exists($name, $config['subnets'] ?? [])) {
                $this->errors[] = "{$name}: Required network is not defined";
            }
        }

        // Check for overlaps
        $subnetList = \array_values($subnets);
        for ($i = 0; $i < \count($subnetList); $i++) {
            for ($j = $i + 1; $j < \count($subnetList); $j++) {
                if ($subnetList[$i]->overlaps($subnetList[$j])) {
                    $names = \array_keys($subnets);
                    $this->errors[] = "Overlap detected: {$names[$i]} and {$names[$j]}";
                }
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

            if (\strpos($name, 'public') !== false && $touchesPrivate) {
                $this->warnings[] = "{$name}: Named 'public' but includes private address space";
            }
            if (\strpos($name, 'private') !== false && !$entirelyPrivate) {
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

    echo "Network Change Impact Analysis\n";
    echo \str_repeat('=', 80) . "\n\n";

    echo "Current Network: {$currentCidr}\n";
    echo "Proposed Network: {$proposedCidr}\n\n";

    echo "Current State:\n";
    echo "  Usable hosts: {$current->hostCount()}\n";
    echo "  Range: {$current->minHost()} - {$current->maxHost()}\n\n";

    echo "Proposed State:\n";
    echo "  Usable hosts: {$proposed->hostCount()}\n";
    echo "  Range: {$proposed->minHost()} - {$proposed->maxHost()}\n\n";

    echo "Impact Analysis:\n";
    echo \str_repeat('-', 80) . "\n";

    // Capacity change
    $capacityChange = $proposed->hostCount() - $current->hostCount();
    if ($capacityChange > 0) {
        echo "  ✓ Capacity increase: +{$capacityChange} hosts\n";
    } elseif ($capacityChange < 0) {
        echo "  ⚠ Capacity decrease: {$capacityChange} hosts\n";
    } else {
        echo "  - No capacity change\n";
    }

    // Hosts are preserved only if every current host is still a usable host in the proposed network.
    // Expanding a /31 or /32 can turn a usable host into the new network or broadcast address.
    $proposedHosts = $proposed->hostRange();
    $hostsPreserved = $proposedHosts->contains($current->minHost())
        && $proposedHosts->contains($current->maxHost());

    // Address compatibility
    if ($current->overlaps($proposed)) {
        if ($proposed->contains($current)) {
            echo "  ✓ Proposed network contains current network (expansion)\n";
            if ($hostsPreserved) {
                echo "  ✓ Existing IPs will remain valid\n";
            } else {
                echo "  ⚠ Some existing host IPs become the new network or broadcast address\n";
            }
        } elseif ($current->contains($proposed)) {
            echo "  ⚠ Proposed network is smaller than current (contraction)\n";
            echo "  ⚠ Some existing IPs may become invalid\n";
        } else {
            echo "  ⚠ Partial overlap with current network\n";
            echo "  ⚠ Some IPs will remain valid, others will not\n";
        }
    } else {
        echo "  ✗ No overlap with current network\n";
        echo "  ✗ ALL existing IPs will become invalid\n";
        echo "  ✗ Complete renumbering required\n";
    }

    echo "\nRecommendations:\n";
    echo \str_repeat('-', 80) . "\n";

    if (!$current->overlaps($proposed)) {
        echo "  • Complete network renumbering required\n";
        echo "  • Plan maintenance window\n";
        echo "  • Update all static IP configurations\n";
        echo "  • Update firewall rules\n";
        echo "  • Update DNS records\n";
    } elseif (!$proposed->contains($current)) {
        echo "  • Partial renumbering required\n";
        echo "  • Audit existing IP allocations\n";
        echo "  • Migrate hosts outside new range\n";
    } elseif (!$hostsPreserved) {
        echo "  • Expansion, but some hosts must be renumbered\n";
        echo "  • Move hosts off the new network and broadcast addresses\n";
        echo "  • Update DHCP scope if applicable\n";
    } else {
        echo "  • Low-risk change (expansion)\n";
        echo "  • Update DHCP scope if applicable\n";
        echo "  • No immediate host changes required\n";
    }
}

// Usage
analyzeNetworkChange('192.168.1.0/24', '192.168.1.0/23');  // Expansion
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
