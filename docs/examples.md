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
$corporateBlock = IPv4\SubnetCalculatorFactory::fromCidr('10.0.0.0/16');

// Office requirements
$offices = [
    'New York'     => 500,
    'London'       => 300,
    'Tokyo'        => 250,
    'Sydney'       => 150,
    'Branch-Small' => 50,
];

echo "Corporate IP Allocation Plan\n";
echo str_repeat('=', 80) . "\n\n";
echo "Available: {$corporateBlock->getCidrNotation()}\n";
echo "Total IPs: {$corporateBlock->getNumberIPAddresses()}\n\n";

$currentNetwork = '10.0.0.0';
$allocations = [];

foreach ($offices as $office => $requiredHosts) {
    // Find optimal subnet size
    $optimalPrefix = IPv4\SubnetCalculatorFactory::optimalPrefixForHosts($requiredHosts);
    $subnet = IPv4\SubnetCalculatorFactory::fromCidr("{$currentNetwork}/{$optimalPrefix}");

    $allocations[$office] = $subnet;

    echo "{$office}:\n";
    echo "  Required hosts: {$requiredHosts}\n";
    echo "  Allocated: {$subnet->getCidrNotation()}\n";
    echo "  Usable hosts: {$subnet->getNumberAddressableHosts()}\n";
    echo "  Range: {$subnet->getMinHost()} - {$subnet->getMaxHost()}\n";
    echo "  Utilization: {$subnet->getUtilizationForHosts($requiredHosts)}%\n\n";

    // Move to next available network
    $currentNetwork = $subnet->getNextSubnet()->getNetworkPortion();
}

// Calculate remaining space
$allocated = array_values($allocations);
$remaining = $corporateBlock->excludeAll($allocated);

echo "Remaining unallocated space:\n";
$totalRemaining = 0;
foreach ($remaining as $block) {
    echo "  {$block->getCidrNotation()} ({$block->getNumberIPAddresses()} IPs)\n";
    $totalRemaining += $block->getNumberIPAddresses();
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
            IPv4\SubnetCalculatorFactory::fromCidr($initialBlock)
        ];
        $this->allocations = [];
    }

    public function allocate($name, $cidr) {
        $allocation = IPv4\SubnetCalculatorFactory::fromCidr($cidr);

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
                $newAvailable = array_merge($newAvailable, $remaining);
            } else {
                $newAvailable[] = $block;
            }
        }

        $this->availableBlocks = $newAvailable;
        $this->allocations[$name] = $allocation;

        return $allocation;
    }

    public function findAvailableSubnet($requiredHosts) {
        $optimalPrefix = IPv4\SubnetCalculatorFactory::optimalPrefixForHosts($requiredHosts);

        foreach ($this->availableBlocks as $block) {
            if ($block->getNetworkSize() <= $optimalPrefix) {
                // This block is large enough
                $subnet = IPv4\SubnetCalculatorFactory::fromCidr(
                    $block->getNetworkPortion() . '/' . $optimalPrefix
                );

                if ($block->contains($subnet)) {
                    return $subnet->getCidrNotation();
                }
            }
        }

        return null;
    }

    public function getUtilizationReport() {
        $report = "IPAM Pool: {$this->name}\n";
        $report .= str_repeat('=', 80) . "\n\n";

        $report .= "Allocations:\n";
        $report .= str_repeat('-', 80) . "\n";
        foreach ($this->allocations as $name => $subnet) {
            $report .= sprintf("  %-30s %s (%d IPs)\n",
                $name,
                $subnet->getCidrNotation(),
                $subnet->getNumberIPAddresses()
            );
        }

        $report .= "\nAvailable Blocks:\n";
        $report .= str_repeat('-', 80) . "\n";
        $totalAvailable = 0;
        foreach ($this->availableBlocks as $block) {
            $report .= sprintf("  %s (%d IPs)\n",
                $block->getCidrNotation(),
                $block->getNumberIPAddresses()
            );
            $totalAvailable += $block->getNumberIPAddresses();
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
        $subnet = IPv4\SubnetCalculatorFactory::fromCidr($cidr);
        $this->rules[] = [
            'name' => $name,
            'subnet' => $subnet,
            'cidr' => $cidr,
            'action' => $action,
        ];
    }

    public function validateRules() {
        $conflicts = [];

        for ($i = 0; $i < count($this->rules); $i++) {
            for ($j = $i + 1; $j < count($this->rules); $j++) {
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
        echo str_repeat('=', 80) . "\n\n";

        $conflicts = $this->validateRules();

        if (empty($conflicts)) {
            echo "✓ No conflicts detected. All rules are valid.\n";
            return;
        }

        echo "⚠ CONFLICTS DETECTED: " . count($conflicts) . "\n\n";

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

### Access Control by IP Type

Implement access control based on IP address classification.

```php
<?php

class AccessController {
    public function checkAccess($ipAddress, $resource) {
        $subnet = IPv4\SubnetCalculatorFactory::fromCidr($ipAddress . '/32');

        // Define access policies
        $policy = $this->getAccessPolicy($resource);

        // Check IP type
        if ($subnet->isPrivate()) {
            return in_array('private', $policy['allowed_types']);
        } elseif ($subnet->isPublic()) {
            return in_array('public', $policy['allowed_types']);
        } elseif ($subnet->isLoopback()) {
            return in_array('loopback', $policy['allowed_types']);
        }

        return false;
    }

    private function getAccessPolicy($resource) {
        $policies = [
            'admin-panel' => [
                'allowed_types' => ['private', 'loopback'],
            ],
            'public-api' => [
                'allowed_types' => ['public', 'private'],
            ],
            'internal-api' => [
                'allowed_types' => ['private'],
            ],
        ];

        return $policies[$resource] ?? ['allowed_types' => []];
    }

    public function generateAccessReport($ipAddress) {
        $subnet = IPv4\SubnetCalculatorFactory::fromCidr($ipAddress . '/32');

        echo "Access Report for {$ipAddress}\n";
        echo str_repeat('=', 80) . "\n";
        echo "IP Type: {$subnet->getAddressType()}\n";
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

echo "Internal User:\n";
$ac->generateAccessReport('192.168.1.100');
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
        $subnets = array_map(function($cidr) {
            return IPv4\SubnetCalculatorFactory::fromCidr($cidr);
        }, $cidrs);

        $this->regions[$region] = $subnets;
    }

    public function optimizeRoutes() {
        $optimized = [];

        foreach ($this->regions as $region => $subnets) {
            $aggregated = IPv4\SubnetCalculatorFactory::aggregate($subnets);
            $optimized[$region] = $aggregated;
        }

        return $optimized;
    }

    public function printOptimizationReport() {
        echo "BGP Route Optimization Report\n";
        echo str_repeat('=', 80) . "\n\n";

        $totalOriginal = 0;
        $totalOptimized = 0;

        foreach ($this->regions as $region => $subnets) {
            echo "Region: {$region}\n";
            echo str_repeat('-', 80) . "\n";

            echo "Original routes (" . \count($subnets) . "):\n";
            foreach ($subnets as $subnet) {
                echo "  {$subnet->getCidrNotation()}\n";
            }

            $aggregated = IPv4\SubnetCalculatorFactory::aggregate($subnets);
            echo "\nOptimized routes (" . count($aggregated) . "):\n";
            foreach ($aggregated as $subnet) {
                echo "  {$subnet->getCidrNotation()}\n";
            }

            $reduction = count($subnets) - count($aggregated);
            echo "\nReduction: {$reduction} routes\n\n";

            $totalOriginal += count($subnets);
            $totalOptimized += count($aggregated);
        }

        $totalReduction = $totalOriginal - $totalOptimized;
        $percentReduction = round(($totalReduction / $totalOriginal) * 100, 2);

        echo str_repeat('=', 80) . "\n";
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
    public function planScopes($baseNetwork, array $departments) {
        $baseSubnet = IPv4\SubnetCalculatorFactory::fromCidr($baseNetwork);
        $scopes = [];

        echo "DHCP Scope Planning Report\n";
        echo str_repeat('=', 80) . "\n";
        echo "Base Network: {$baseNetwork}\n";
        echo "Available IPs: {$baseSubnet->getNumberIPAddresses()}\n\n";

        $currentNetwork = $baseSubnet->getNetworkPortion();

        foreach ($departments as $dept => $requirements) {
            $requiredHosts = $requirements['hosts'];
            $optimalPrefix = IPv4\SubnetCalculatorFactory::optimalPrefixForHosts($requiredHosts);
            $scope = IPv4\SubnetCalculatorFactory::fromCidr("{$currentNetwork}/{$optimalPrefix}");

            // Verify scope fits within base network
            if (!$baseSubnet->contains($scope)) {
                echo "ERROR: Insufficient space for {$dept}\n";
                break;
            }

            $usableHosts = $scope->getNumberAddressableHosts();
            $utilization = $scope->getUtilizationForHosts($requiredHosts);

            // Calculate DHCP pool (reserve first 10 IPs for static, last IP for gateway)
            $poolStart = $scope->getMinHostInteger() + 10;
            $poolEnd = $scope->getMaxHostInteger() - 1;

            $scopes[$dept] = [
                'subnet' => $scope,
                'scope_start' => long2ip($poolStart),
                'scope_end' => long2ip($poolEnd),
                'gateway' => $scope->getMaxHost(),
                'dns' => $requirements['dns'] ?? $scope->getMinHost(),
            ];

            echo "{$dept}:\n";
            echo "  Required hosts: {$requiredHosts}\n";
            echo "  Subnet: {$scope->getCidrNotation()}\n";
            echo "  Subnet mask: {$scope->getSubnetMask()}\n";
            echo "  Usable hosts: {$usableHosts}\n";
            echo "  Utilization: {$utilization}%\n";
            echo "  DHCP Pool: {$scopes[$dept]['scope_start']} - {$scopes[$dept]['scope_end']}\n";
            echo "  Gateway: {$scopes[$dept]['gateway']}\n";
            echo "  DNS: {$scopes[$dept]['dns']}\n\n";

            $currentNetwork = $scope->getNextSubnet()->getNetworkPortion();
        }

        return $scopes;
    }

    public function exportDHCPConfig(array $scopes, $serverType = 'isc-dhcp') {
        if ($serverType === 'isc-dhcp') {
            return $this->exportISCDHCPConfig($scopes);
        }
        return '';
    }

    private function exportISCDHCPConfig(array $scopes) {
        $config = "# ISC DHCP Server Configuration\n";
        $config .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($scopes as $name => $scope) {
            $subnet = $scope['subnet'];
            $config .= "# {$name}\n";
            $config .= "subnet {$subnet->getNetworkPortion()} netmask {$subnet->getSubnetMask()} {\n";
            $config .= "    range {$scope['scope_start']} {$scope['scope_end']};\n";
            $config .= "    option routers {$scope['gateway']};\n";
            $config .= "    option domain-name-servers {$scope['dns']};\n";
            $config .= "    option subnet-mask {$subnet->getSubnetMask()};\n";
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

echo "\n" . str_repeat('=', 80) . "\n";
echo "Generated DHCP Configuration:\n";
echo str_repeat('=', 80) . "\n\n";
echo $planner->exportDHCPConfig($scopes, 'isc-dhcp');
```

---

## DNS Configuration

### Reverse DNS Zone Generator

Generate reverse DNS zone configurations.

```php
<?php

class ReverseDNSZoneGenerator {
    public function generateZone($cidr, $nameservers, $hostnames = []) {
        $subnet = IPv4\SubnetCalculatorFactory::fromCidr($cidr);

        echo "Reverse DNS Zone Configuration\n";
        echo str_repeat('=', 80) . "\n";
        echo "Subnet: {$cidr}\n";
        echo "Network: {$subnet->getNetworkPortion()}\n";
        echo "Broadcast: {$subnet->getBroadcastAddress()}\n\n";

        // Generate zone file
        $zone = $this->generateZoneFile($subnet, $nameservers, $hostnames);

        return $zone;
    }

    private function generateZoneFile($subnet, $nameservers, $hostnames) {
        $zone = "; Reverse DNS Zone\n";
        $zone .= "; Network: {$subnet->getCidrNotation()}\n";
        $zone .= "; Generated: " . date('Y-m-d H:i:s') . "\n\n";

        $zone .= "\$TTL 86400\n";
        $zone .= "@    IN    SOA    {$nameservers[0]}. hostmaster.example.com. (\n";
        $zone .= "            " . date('Ymd') . "01    ; Serial\n";
        $zone .= "            3600              ; Refresh\n";
        $zone .= "            1800              ; Retry\n";
        $zone .= "            604800            ; Expire\n";
        $zone .= "            86400 )           ; Minimum TTL\n\n";

        // Nameservers
        foreach ($nameservers as $ns) {
            $zone .= "    IN    NS    {$ns}.\n";
        }
        $zone .= "\n";

        // PTR records
        foreach ($hostnames as $ip => $hostname) {
            $ipSubnet = IPv4\SubnetCalculatorFactory::fromCidr($ip . '/32');
            if ($subnet->isIPAddressInSubnet($ip)) {
                $octets = $ipSubnet->getIPAddressQuads();
                $ptrRecord = $octets[3];
                $zone .= "{$ptrRecord}    IN    PTR    {$hostname}.\n";
            }
        }

        return $zone;
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

$zone = $generator->generateZone(
    '192.168.1.0/24',
    ['ns1.example.com', 'ns2.example.com'],
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

        // Validate subnets
        $subnets = [];
        foreach ($config['subnets'] as $name => $cidr) {
            try {
                $subnet = IPv4\SubnetCalculatorFactory::fromCidr($cidr);
                $subnets[$name] = $subnet;

                // Check if subnet is properly sized
                if (isset($config['requirements'][$name])) {
                    $required = $config['requirements'][$name];
                    $available = $subnet->getNumberAddressableHosts();

                    if ($available < $required) {
                        $this->errors[] = "{$name}: Insufficient capacity. Required: {$required}, Available: {$available}";
                    } elseif ($available > $required * 2) {
                        $this->warnings[] = "{$name}: Subnet may be oversized. Required: {$required}, Available: {$available}";
                    }
                }
            } catch (\Exception $e) {
                $this->errors[] = "{$name}: Invalid CIDR notation - {$e->getMessage()}";
            }
        }

        // Check for overlaps
        $subnetList = array_values($subnets);
        for ($i = 0; $i < count($subnetList); $i++) {
            for ($j = $i + 1; $j < count($subnetList); $j++) {
                if ($subnetList[$i]->overlaps($subnetList[$j])) {
                    $names = array_keys($subnets);
                    $this->errors[] = "Overlap detected: {$names[$i]} and {$names[$j]}";
                }
            }
        }

        // Validate address types
        foreach ($subnets as $name => $subnet) {
            if (strpos($name, 'public') !== false && $subnet->isPrivate()) {
                $this->warnings[] = "{$name}: Named 'public' but uses private address space";
            }
            if (strpos($name, 'private') !== false && $subnet->isPublic()) {
                $this->warnings[] = "{$name}: Named 'private' but uses public address space";
            }
        }

        return empty($this->errors);
    }

    public function getReport() {
        $report = "Network Configuration Validation Report\n";
        $report .= str_repeat('=', 80) . "\n\n";

        if (!empty($this->errors)) {
            $report .= "ERRORS (" . count($this->errors) . "):\n";
            $report .= str_repeat('-', 80) . "\n";
            foreach ($this->errors as $error) {
                $report .= "  ✗ {$error}\n";
            }
            $report .= "\n";
        }

        if (!empty($this->warnings)) {
            $report .= "WARNINGS (" . count($this->warnings) . "):\n";
            $report .= str_repeat('-', 80) . "\n";
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
    $current = IPv4\SubnetCalculatorFactory::fromCidr($currentCidr);
    $proposed = IPv4\SubnetCalculatorFactory::fromCidr($proposedCidr);

    echo "Network Change Impact Analysis\n";
    echo str_repeat('=', 80) . "\n\n";

    echo "Current Network: {$currentCidr}\n";
    echo "Proposed Network: {$proposedCidr}\n\n";

    echo "Current State:\n";
    echo "  Usable hosts: {$current->getNumberAddressableHosts()}\n";
    echo "  Range: {$current->getMinHost()} - {$current->getMaxHost()}\n\n";

    echo "Proposed State:\n";
    echo "  Usable hosts: {$proposed->getNumberAddressableHosts()}\n";
    echo "  Range: {$proposed->getMinHost()} - {$proposed->getMaxHost()}\n\n";

    echo "Impact Analysis:\n";
    echo str_repeat('-', 80) . "\n";

    // Capacity change
    $capacityChange = $proposed->getNumberAddressableHosts() - $current->getNumberAddressableHosts();
    if ($capacityChange > 0) {
        echo "  ✓ Capacity increase: +{$capacityChange} hosts\n";
    } elseif ($capacityChange < 0) {
        echo "  ⚠ Capacity decrease: {$capacityChange} hosts\n";
    } else {
        echo "  - No capacity change\n";
    }

    // Address compatibility
    if ($current->overlaps($proposed)) {
        if ($proposed->contains($current)) {
            echo "  ✓ Proposed network contains current network (expansion)\n";
            echo "  ✓ Existing IPs will remain valid\n";
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
    echo str_repeat('-', 80) . "\n";

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
    } else {
        echo "  • Low-risk change (expansion)\n";
        echo "  • Update DHCP scope if applicable\n";
        echo "  • No immediate host changes required\n";
    }
}

// Usage
analyzeNetworkChange('192.168.1.0/24', '192.168.1.0/23');  // Expansion
echo "\n" . str_repeat('=', 80) . "\n\n";
analyzeNetworkChange('192.168.1.0/24', '192.168.1.0/25');  // Contraction
echo "\n" . str_repeat('=', 80) . "\n\n";
analyzeNetworkChange('192.168.1.0/24', '192.168.2.0/24');  // Complete change
```

---

## Related Documentation

- **[Getting Started](getting-started.md)** - Installation and basic usage
- **[Core Features](core-features.md)** - Detailed feature documentation
- **[Advanced Features](advanced-features.md)** - Advanced operations
- **[API Reference](api-reference.md)** - Complete method documentation
- **[Reports](reports.md)** - Report generation
