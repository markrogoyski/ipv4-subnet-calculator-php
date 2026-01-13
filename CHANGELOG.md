# IPv4 SubnetCalculator Change log

## v4.4.0 - TBD

### New Features
- **Subnet exclusion and difference operations**: New methods for calculating remaining address space after removing (excluding) subnets:
  - `exclude()`: Remove a single subnet from this subnet, returning the remaining address space as an array of optimally-sized CIDR blocks
  - `excludeAll()`: Remove multiple subnets from this subnet, applying exclusions sequentially
  - Returns minimal set of CIDR blocks covering the remaining space
  - Useful for IP address management (IPAM), reserving address ranges, carving out specific blocks from larger allocations, and network planning

- **Adjacent subnet navigation**: New methods for navigating to neighboring subnets of the same size:
  - `getNextSubnet()`: Get the immediately following subnet in IP address space
  - `getPreviousSubnet()`: Get the immediately preceding subnet in IP address space
  - `getAdjacentSubnets()`: Get multiple adjacent subnets in either direction (positive count for forward, negative for backward)

- **Optimal prefix calculation**: New utility method for calculating the smallest CIDR prefix that accommodates a given host count:
  - `SubnetCalculatorFactory::optimalPrefixForHosts()`: Calculate optimal prefix without creating a SubnetCalculator instance
  - Returns the smallest prefix (largest network) needed for the specified number of hosts
  - Useful for network planning and subnet sizing calculations

## v4.3.0 - 2026-01-11

### New Features
- **SubnetCalculatorFactory**: New factory class for creating SubnetCalculator instances from various input formats:
  - `fromCidr()`: Create from CIDR notation (e.g., "192.168.1.0/24")
  - `fromMask()`: Create from IP address and subnet mask (e.g., "192.168.1.0", "255.255.255.0")
  - `fromRange()`: Create from IP address range (e.g., "192.168.1.0", "192.168.1.255")
  - `fromHostCount()`: Create from IP address and required host count
- **Subnet overlap and conflict detection**: New methods for network planning and conflict prevention:
  - `overlaps()`: Check if two subnets share any IP addresses
  - `contains()`: Check if this subnet fully contains another subnet
  - `isContainedIn()`: Check if this subnet is fully contained within another subnet
  - Useful for firewall rule validation, routing table conflict detection, and network planning

- **IP address range type detection**: New methods for identifying special-purpose IPv4 address ranges (IANA registry compliance):
  - `isPrivate()`: Check if IP is in RFC 1918 private ranges (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16)
  - `isPublic()`: Check if IP is publicly routable (not in any special-purpose range)
  - `isLoopback()`: Check if IP is in loopback range (127.0.0.0/8)
  - `isLinkLocal()`: Check if IP is link-local/APIPA (169.254.0.0/16)
  - `isMulticast()`: Check if IP is multicast (224.0.0.0/4)
  - `isCarrierGradeNat()`: Check if IP is in CGN/Shared Address Space (100.64.0.0/10)
  - `isDocumentation()`: Check if IP is reserved for documentation (TEST-NET-1/2/3)
  - `isBenchmarking()`: Check if IP is reserved for benchmarking (198.18.0.0/15)
  - `isReserved()`: Check if IP is reserved for future use (240.0.0.0/4)
  - `isLimitedBroadcast()`: Check if IP is limited broadcast address (255.255.255.255)
  - `isThisNetwork()`: Check if IP is in "this" network range (0.0.0.0/8)
  - `getAddressType()`: Get address type classification as string
  - Useful for security validation, routing decisions, and network classification

- **Wildcard mask calculations**: New methods for wildcard masks (inverse of subnet masks), commonly used in Cisco ACLs and OSPF configurations:
  - `getWildcardMask()`: Returns wildcard mask in dotted quad notation (e.g., "0.0.0.255")
  - `getWildcardMaskQuads()`: Returns wildcard mask as array of quads (e.g., ['0', '0', '0', '255'])
  - `getWildcardMaskHex()`: Returns wildcard mask in hexadecimal format (e.g., "000000FF")
  - `getWildcardMaskBinary()`: Returns wildcard mask in binary format (e.g., "00000000000000000000000011111111")
  - `getWildcardMaskInteger()`: Returns wildcard mask as integer value (e.g., 255)

### Improvements
- **Test organization**: Refactored test suite into smaller, focused test files grouped by functionality for better maintainability and clarity

## v4.2.0 - 2026-01-10

### New Features
- Added project logo

### Fixed
- **RFC 3021 compliance for /31 networks**: Fixed getMinHost(), getMaxHost(), and their variant methods (*Quads, *Hex, *Binary, *Integer) to correctly calculate host ranges for /31 point-to-point networks. Previously these methods returned the input IP for both min and max; they now correctly return the lower IP (network portion) as min host and higher IP (broadcast) as max host. This aligns with getNumberAddressableHosts() which already correctly reported 2 usable hosts for /31 networks.

## v4.1.0 - 2024-02-09

### New Features
- Network splitting capability via `split` method
- CIDR notation retrieval through `getCidrNotation`

### Improvements
- Enhanced compatibility extending through PHP 8.4

## v4.0.0 - 2023-12-29

### New Features
- IPv4 ARPA Domain functionality for reverse DNS lookups
- IP address integer representations:
  - `getIPAddressInteger`
  - `getMinHostInteger`
  - `getMaxHostInteger`
  - `getSubnetMaskInteger`
  - `getNetworkPortionInteger`
  - `getHostPortionInteger`

### Improvements
- ARPA domain and integer values integrated into reports
- PHP minimum version requirement raised to 7.2

## v3.1.0 - 2023-12-19

### New Features
- IP address subnet membership validation

## v3.0.0 - 2023-10-27

### New Features
- Complete IP address enumeration capability
- Host IP address listing
- JsonSerializable interface implementation
- SubnetReport interface for custom reporting options

## v2.1.0 - 2023-04-30

### New Features
- Minimum host calculation
- Maximum host calculation
- Addressable host range specification
