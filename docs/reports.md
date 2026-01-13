# Reports

Generate comprehensive network information reports in multiple formats: printed output, associative arrays, JSON, and string representations.

## Table of Contents
- [Report Formats](#report-formats)
- [Printed Report](#printed-report)
- [Array Report](#array-report)
- [JSON Report](#json-report)
- [String Report](#string-report)
- [Standard Interfaces](#standard-interfaces)
- [Use Cases](#use-cases)

## Report Formats

The IPv4 Subnet Calculator provides four ways to generate comprehensive subnet reports:

1. **Printed Report** - Directly output formatted text to STDOUT
2. **Array Report** - Get structured data as a PHP associative array
3. **JSON Report** - Get structured data as a JSON string
4. **String Report** - Get formatted text as a string variable

All formats include complete network information: IP address, masks, network/host portions, address ranges, and metadata.

## Printed Report

Directly print a formatted report to STDOUT:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');
$subnet->printSubnetReport();
```

**Output:**
```
192.168.112.203/23           Quads      Hex                           Binary    Integer
------------------ --------------- -------- -------------------------------- ----------
IP Address:        192.168.112.203 C0A870CB 11000000101010000111000011001011 3232264395
Subnet Mask:         255.255.254.0 FFFFFE00 11111111111111111111111000000000 4294966784
Wildcard Mask:           0.0.1.255 000001FF 00000000000000000000000111111111        511
Network Portion:     192.168.112.0 C0A87000 11000000101010000111000000000000 3232264192
Host Portion:            0.0.0.203 000000CB 00000000000000000000000011001011        203

IP Address Type:             private
Network Class:               C
Classful:                    No (subnetted/supernetted)
Number of IP Addresses:      512
Number of Addressable Hosts: 510
IP Address Range:            192.168.112.0 - 192.168.113.255
Broadcast Address:           192.168.113.255
Min Host:                    192.168.112.1
Max Host:                    192.168.113.254
IPv4 ARPA Domain:            203.112.168.192.in-addr.arpa
```

**Use Cases:**
- CLI tools and scripts
- Quick diagnostic output
- Interactive terminal sessions
- Debug logging to console

## Array Report

Get subnet information as a structured PHP associative array:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');
$report = $subnet->getSubnetArrayReport();

// Access specific values
echo $report['ip_address']['quads'];              // '192.168.112.203'
echo $report['subnet_mask']['binary'];            // '11111111111111111111111000000000'
echo $report['number_of_addressable_hosts'];      // 510
echo $report['network_class']['class'];           // 'C'
echo $report['network_class']['is_classful'];     // false
```

**Complete Array Structure:**
```php
Array
(
    [ip_address_with_network_size] => 192.168.112.203/23
    [ip_address] => Array
        (
            [quads] => 192.168.112.203
            [hex] => C0A870CB
            [binary] => 11000000101010000111000011001011
            [integer] => 3232264395
        )

    [ip_address_type] => private
    [network_class] => Array
        (
            [class] => C
            [default_mask] => 255.255.255.0
            [default_prefix] => 24
            [is_classful] => false
        )

    [subnet_mask] => Array
        (
            [quads] => 255.255.254.0
            [hex] => FFFFFE00
            [binary] => 11111111111111111111111000000000
            [integer] => 4294966784
        )

    [wildcard_mask] => Array
        (
            [quads] => 0.0.1.255
            [hex] => 000001FF
            [binary] => 00000000000000000000000111111111
            [integer] => 511
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
```

**Use Cases:**
- Further programmatic processing
- Integrating with other PHP systems
- Custom report generation
- Storing in databases
- Template rendering

**Example - Extract Specific Information:**
```php
$report = $subnet->getSubnetArrayReport();

// Build custom output
$summary = [
    'network' => $report['network_portion']['quads'],
    'mask' => $report['subnet_mask']['quads'],
    'hosts' => $report['number_of_addressable_hosts'],
    'range' => implode(' - ', $report['ip_address_range']),
    'type' => $report['ip_address_type'],
];

print_r($summary);
```

## JSON Report

Get subnet information as a JSON string:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');
$json = $subnet->getSubnetJSONReport();

// Parse JSON
$data = json_decode($json, true);
echo $data['number_of_addressable_hosts'];  // 510
```

**Complete JSON Output:**
```json
{
    "ip_address_with_network_size": "192.168.112.203/23",
    "ip_address": {
        "quads": "192.168.112.203",
        "hex": "C0A870CB",
        "binary": "11000000101010000111000011001011",
        "integer": 3232264395
    },
    "ip_address_type": "private",
    "network_class": {
        "class": "C",
        "default_mask": "255.255.255.0",
        "default_prefix": 24,
        "is_classful": false
    },
    "subnet_mask": {
        "quads": "255.255.254.0",
        "hex": "FFFFFE00",
        "binary": "11111111111111111111111000000000",
        "integer": 4294966784
    },
    "wildcard_mask": {
        "quads": "0.0.1.255",
        "hex": "000001FF",
        "binary": "00000000000000000000000111111111",
        "integer": 511
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
```

**Use Cases:**
- REST API responses
- Web service integrations
- JavaScript/frontend consumption
- Data interchange between systems
- Logging to JSON-based systems
- Database storage (JSON columns)

**Example - REST API Endpoint:**
```php
// API endpoint that returns subnet information
function getSubnetInfo($cidr) {
    try {
        $subnet = IPv4\SubnetCalculatorFactory::fromCidr($cidr);

        header('Content-Type: application/json');
        echo $subnet->getSubnetJSONReport();
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Usage: GET /api/subnet/192.168.1.0/24
getSubnetInfo($_GET['cidr']);
```

## String Report

Get a formatted report as a string variable (same format as printed report):

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');
$stringReport = $subnet->getPrintableReport();

// Store in variable, log to file, etc.
file_put_contents('subnet-report.txt', $stringReport);
echo $stringReport;
```

**Output (same as printed report):**
```
192.168.112.203/23           Quads      Hex                           Binary    Integer
------------------ --------------- -------- -------------------------------- ----------
IP Address:        192.168.112.203 C0A870CB 11000000101010000111000011001011 3232264395
Subnet Mask:         255.255.254.0 FFFFFE00 11111111111111111111111000000000 4294966784
Wildcard Mask:           0.0.1.255 000001FF 00000000000000000000000111111111        511
Network Portion:     192.168.112.0 C0A87000 11000000101010000111000000000000 3232264192
Host Portion:            0.0.0.203 000000CB 00000000000000000000000011001011        203

IP Address Type:             private
Network Class:               C
Classful:                    No (subnetted/supernetted)
Number of IP Addresses:      512
Number of Addressable Hosts: 510
IP Address Range:            192.168.112.0 - 192.168.113.255
Broadcast Address:           192.168.113.255
Min Host:                    192.168.112.1
Max Host:                    192.168.113.254
IPv4 ARPA Domain:            203.112.168.192.in-addr.arpa
```

**Use Cases:**
- Logging to files
- Email notifications
- Text-based reports
- Documentation generation
- Error messages with context
- Audit trails

**Example - Log to File:**
```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('10.0.0.0/8');
$report = $subnet->getPrintableReport();

$logEntry = date('Y-m-d H:i:s') . " - Subnet Analysis\n";
$logEntry .= str_repeat('=', 80) . "\n";
$logEntry .= $report . "\n\n";

file_put_contents('/var/log/subnet-analysis.log', $logEntry, FILE_APPEND);
```

## Standard Interfaces

### String Representation (__toString)

The SubnetCalculator class implements `__toString()`, allowing you to print the object directly:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

// These are equivalent:
print($subnet);
echo $subnet;
echo $subnet->getPrintableReport();

// All output the formatted report
```

### JsonSerializable

The SubnetCalculator class implements `JsonSerializable`, enabling direct JSON encoding:

```php
$subnet = IPv4\SubnetCalculatorFactory::fromCidr('192.168.112.203/23');

// These are equivalent:
$json = json_encode($subnet);
$json = $subnet->getSubnetJSONReport();

// Pretty print JSON
$prettyJson = json_encode($subnet, JSON_PRETTY_PRINT);
echo $prettyJson;
```

## Use Cases

### CLI Network Analysis Tool

```php
#!/usr/bin/env php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

if ($argc < 2) {
    echo "Usage: {$argv[0]} <CIDR>\n";
    echo "Example: {$argv[0]} 192.168.1.0/24\n";
    exit(1);
}

try {
    $subnet = IPv4\SubnetCalculatorFactory::fromCidr($argv[1]);
    $subnet->printSubnetReport();
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    exit(1);
}
```

### Web API Service

```php
// REST API endpoint
header('Content-Type: application/json');

try {
    $cidr = $_GET['cidr'] ?? '';
    $subnet = IPv4\SubnetCalculatorFactory::fromCidr($cidr);

    echo $subnet->getSubnetJSONReport();
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'cidr' => $cidr
    ]);
}
```

### Batch Subnet Analysis

```php
$subnets = [
    '10.0.0.0/8',
    '172.16.0.0/12',
    '192.168.0.0/16',
];

$reports = [];

foreach ($subnets as $cidr) {
    $subnet = IPv4\SubnetCalculatorFactory::fromCidr($cidr);
    $reports[] = $subnet->getSubnetArrayReport();
}

// Export to JSON file
file_put_contents(
    'subnet-analysis.json',
    json_encode($reports, JSON_PRETTY_PRINT)
);
```

### Network Documentation Generator

```php
function generateNetworkDocumentation(array $subnets, $outputFile) {
    $doc = "# Network Documentation\n\n";
    $doc .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $doc .= str_repeat('=', 80) . "\n\n";

    foreach ($subnets as $name => $cidr) {
        $subnet = IPv4\SubnetCalculatorFactory::fromCidr($cidr);
        $report = $subnet->getSubnetArrayReport();

        $doc .= "## {$name}\n\n";
        $doc .= "```\n";
        $doc .= $subnet->getPrintableReport();
        $doc .= "```\n\n";
        $doc .= "**Type:** {$report['ip_address_type']}\n";
        $doc .= "**Usable Hosts:** {$report['number_of_addressable_hosts']}\n";
        $doc .= "**Range:** {$report['ip_address_range'][0]} - {$report['ip_address_range'][1]}\n\n";
        $doc .= str_repeat('-', 80) . "\n\n";
    }

    file_put_contents($outputFile, $doc);
}

// Usage
$networkPlan = [
    'Engineering Department' => '10.0.0.0/24',
    'Sales Department' => '10.0.1.0/24',
    'Guest WiFi' => '10.0.100.0/24',
    'Server Farm' => '10.0.200.0/22',
];

generateNetworkDocumentation($networkPlan, 'network-plan.md');
```

### Database Storage

```php
// Store subnet analysis in database
function storeSubnetAnalysis($pdo, $cidr, $description) {
    $subnet = IPv4\SubnetCalculatorFactory::fromCidr($cidr);
    $report = $subnet->getSubnetArrayReport();

    $stmt = $pdo->prepare("
        INSERT INTO subnet_analysis
        (cidr, description, network, broadcast, hosts, report_json, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $cidr,
        $description,
        $report['network_portion']['quads'],
        $report['broadcast_address'],
        $report['number_of_addressable_hosts'],
        json_encode($report)
    ]);
}

// Usage
$pdo = new PDO('mysql:host=localhost;dbname=network', 'user', 'pass');
storeSubnetAnalysis($pdo, '192.168.1.0/24', 'Office LAN');
```

### Email Notification

```php
function emailSubnetReport($email, $cidr) {
    $subnet = IPv4\SubnetCalculatorFactory::fromCidr($cidr);
    $report = $subnet->getPrintableReport();

    $subject = "Subnet Analysis Report: {$cidr}";
    $message = "Subnet analysis for {$cidr}\n\n";
    $message .= $report;
    $message .= "\n\nGenerated at: " . date('Y-m-d H:i:s');

    $headers = "From: network-admin@example.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($email, $subject, $message, $headers);
}

// Usage
emailSubnetReport('admin@example.com', '10.0.0.0/8');
```

### Comparison Report

```php
function compareSubnets($cidr1, $cidr2) {
    $subnet1 = IPv4\SubnetCalculatorFactory::fromCidr($cidr1);
    $subnet2 = IPv4\SubnetCalculatorFactory::fromCidr($cidr2);

    echo "Comparison Report\n";
    echo str_repeat('=', 80) . "\n\n";

    echo "Subnet 1: {$cidr1}\n";
    echo str_repeat('-', 80) . "\n";
    echo $subnet1->getPrintableReport();
    echo "\n\n";

    echo "Subnet 2: {$cidr2}\n";
    echo str_repeat('-', 80) . "\n";
    echo $subnet2->getPrintableReport();
    echo "\n\n";

    echo "Relationship:\n";
    echo str_repeat('-', 80) . "\n";

    if ($subnet1->overlaps($subnet2)) {
        echo "⚠ Subnets OVERLAP - potential conflict\n";

        if ($subnet1->contains($subnet2)) {
            echo "  → {$cidr1} contains {$cidr2}\n";
        } elseif ($subnet2->contains($subnet1)) {
            echo "  → {$cidr2} contains {$cidr1}\n";
        } else {
            echo "  → Partial overlap detected\n";
        }
    } else {
        echo "✓ Subnets do not overlap - no conflict\n";
    }
}

// Usage
compareSubnets('192.168.1.0/24', '192.168.1.128/25');
```

## Next Steps

- **[Getting Started](getting-started.md)** - Installation and basic usage
- **[Core Features](core-features.md)** - Network calculations, IP operations, overlap detection
- **[Advanced Features](advanced-features.md)** - CIDR aggregation, subnet exclusion, utilization analysis
- **[API Reference](api-reference.md)** - Complete method documentation
- **[Real-World Examples](examples.md)** - Practical patterns and use cases
