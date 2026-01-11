# IPv4 SubnetCalculator Change log

## v4.3.0 - TBD

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

### Improvements
- **Test organization**: Refactored test suite into smaller, focused test files grouped by functionality for better maintainability and clarity

## v4.2.0 - 2026-01-10

### New Features
- Added project logo

### Fixed
- **RFC 3021 compliance for /31 networks**: Fixed getMinHost(), getMaxHost(), and their variant methods (*Quads, *Hex, *Binary, *Integer) to correctly calculate host ranges for /31 point-to-point networks. Previously these methods returned the input IP for both min and max; they now correctly return the lower IP (network portion) as min host and higher IP (broadcast) as max host. This aligns with getNumberAddressableHosts() which already correctly reported 2 usable hosts for /31 networks.
