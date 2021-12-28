<?php

declare(strict_types=1);

namespace IPv4;

/**
 * Network calculator for subnet mask and other classless (CIDR) network information.
 *
 * Given an IP address and CIDR network size, it calculates the following information:
 *   - IP address network subnet masks, network and host portions, and provides aggregated reports.
 *   - Subnet mask
 *   - Network portion
 *   - Host portion
 *   - Number of IP addresses in the network
 *   - Number of addressable hosts in the network
 *   - IP address range
 *   - Broadcast address
 *   - Min and max host
 *   - All IP addresses
 *   - IPv4 ARPA Domain
 * Provides each data in dotted quads, hexadecimal, and binary formats, as well as array of quads.
 *
 * Aggregated network calculation reports:
 *  - Associative array
 *  - JSON
 *  - String
 *  - Printed to STDOUT
 */
class SubnetCalculator implements \JsonSerializable
{
    /** @var string IP address as dotted quads: xxx.xxx.xxx.xxx */
    private $ipAddress;

    /** @var int CIDR network size */
    private $networkSize;

    /** @var string[] of four elements containing the four quads of the IP address */
    private $quads = [];

    /** @var int Subnet mask in format used for subnet calculations */
    private $subnetMask;

    /** @var SubnetReportInterface */
    private $report;

    private const FORMAT_QUADS  = '%d';
    private const FORMAT_HEX    = '%02X';
    private const FORMAT_BINARY = '%08b';

    /**
     * Constructor - Takes IP address and network size, validates inputs, and assigns class attributes.
     * For example: 192.168.1.120/24 would be $ip = 192.168.1.120 $network_size = 24
     *
     * @param string                     $ipAddress   IP address in dotted quad notation.
     * @param int                        $networkSize CIDR network size.
     * @param SubnetReportInterface|null $report
     */
    public function __construct(string $ipAddress, int $networkSize, SubnetReportInterface $report = null)
    {
        $this->validateInputs($ipAddress, $networkSize);

        $this->ipAddress   = $ipAddress;
        $this->networkSize = $networkSize;
        $this->quads       = \explode('.', $ipAddress);
        $this->subnetMask  = $this->calculateSubnetMask($networkSize);
        $this->report      = $report ?? new SubnetReport();
    }

    /* **************** *
     * PUBLIC INTERFACE
     * **************** */

    /**
     * Get IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string IP address as dotted quads.
     */
    public function getIPAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * Get IP address as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[]
     */
    public function getIPAddressQuads(): array
    {
        return $this->quads;
    }

    /**
     * Get IP address as hexadecimal
     *
     * @return string IP address in hex
     */
    public function getIPAddressHex(): string
    {
        return $this->ipAddressCalculation(self::FORMAT_HEX);
    }

    /**
     * Get IP address as binary
     *
     * @return string IP address in binary
     */
    public function getIPAddressBinary(): string
    {
        return $this->ipAddressCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get the IP address as an integer
     *
     * @return int
     */
    public function getIPAddressInteger(): int
    {
        return \ip2long($this->ipAddress);
    }

    /**
     * Get network size
     *
     * @return int network size
     */
    public function getNetworkSize(): int
    {
        return $this->networkSize;
    }

    /**
     * Get the number of IP addresses in the network
     *
     * @return int Number of IP addresses
     */
    public function getNumberIPAddresses(): int
    {
        return \pow(2, (32 - $this->networkSize));
    }

    /**
     * Get the number of addressable hosts in the network
     *
     * @return int Number of IP addresses that are addressable
     */
    public function getNumberAddressableHosts(): int
    {
        if ($this->networkSize == 32) {
            return 1;
        }
        if ($this->networkSize == 31) {
            return 2;
        }

        return ($this->getNumberIPAddresses() - 2);
    }

    /**
     * Get range of IP addresses in the network
     *
     * @return string[] containing start and end of IP address range. IP addresses in dotted quad notation.
     */
    public function getIPAddressRange(): array
    {
        return [$this->getNetworkPortion(), $this->getBroadcastAddress()];
    }

    /**
     * Get range of IP addresses in the network
     *
     * @return string[] containing start and end of IP address range. IP addresses in dotted quad notation.
     */
    public function getAddressableHostRange(): array
    {
        return [$this->getMinHost(), $this->getMaxHost()];
    }

    /**
     * Get the broadcast IP address
     *
     * @return string IP address as dotted quads
     */
    public function getBroadcastAddress(): string
    {
        $network_quads       = $this->getNetworkPortionQuads();
        $number_ip_addresses = $this->getNumberIPAddresses();

        $network_range_quads = [
            \sprintf(self::FORMAT_QUADS, ((int) $network_quads[0] & ($this->subnetMask >> 24)) + ((($number_ip_addresses - 1) >> 24) & 0xFF)),
            \sprintf(self::FORMAT_QUADS, ((int) $network_quads[1] & ($this->subnetMask >> 16)) + ((($number_ip_addresses - 1) >> 16) & 0xFF)),
            \sprintf(self::FORMAT_QUADS, ((int) $network_quads[2] & ($this->subnetMask >>  8)) + ((($number_ip_addresses - 1) >>  8) & 0xFF)),
            \sprintf(self::FORMAT_QUADS, ((int) $network_quads[3] & ($this->subnetMask >>  0)) + ((($number_ip_addresses - 1) >>  0) & 0xFF)),
        ];

        return \implode('.', $network_range_quads);
    }

    /**
     * Get minimum host IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string min host as dotted quads
     */
    public function getMinHost(): string
    {
        if ($this->networkSize === 32 || $this->networkSize === 31) {
            return $this->ipAddress;
        }
        return $this->minHostCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get minimum host IP address as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[] min host portion as dotted quads.
     */
    public function getMinHostQuads(): array
    {
        if ($this->networkSize === 32 || $this->networkSize === 31) {
            return $this->quads;
        }
        return \explode('.', $this->minHostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get minimum host IP address as hex
     *
     * @return string min host portion as hex
     */
    public function getMinHostHex(): string
    {
        if ($this->networkSize === 32 || $this->networkSize === 31) {
            return \implode('', \array_map(
                function ($quad) {
                    return \sprintf(self::FORMAT_HEX, $quad);
                },
                $this->quads
            ));
        }
        return $this->minHostCalculation(self::FORMAT_HEX);
    }

    /**
     * Get minimum host IP address as binary
     *
     * @return string min host portion as binary
     */
    public function getMinHostBinary(): string
    {
        if ($this->networkSize === 32 || $this->networkSize === 31) {
            return \implode('', \array_map(
                function ($quad) {
                    return \sprintf(self::FORMAT_BINARY, $quad);
                },
                $this->quads
            ));
        }
        return $this->minHostCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get minimum host IP address as an Integer
     *
     * @return int min host portion as integer
     */
    public function getMinHostInteger(): int
    {
        return $this->networkSize === 32 || $this->networkSize === 31
            ? \ip2long(\implode('.', $this->quads))
            : \ip2long($this->minHostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get maximum host IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string max host as dotted quads.
     */
    public function getMaxHost(): string
    {
        if ($this->networkSize === 32 || $this->networkSize === 31) {
            return $this->ipAddress;
        }
        return $this->maxHostCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get maximum host IP address as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[] min host portion as dotted quads
     */
    public function getMaxHostQuads(): array
    {
        if ($this->networkSize === 32 || $this->networkSize === 31) {
            return $this->quads;
        }
        return \explode('.', $this->maxHostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get maximum host IP address as hex
     *
     * @return string max host portion as hex
     */
    public function getMaxHostHex(): string
    {
        if ($this->networkSize === 32 || $this->networkSize === 31) {
            return \implode('', \array_map(
                function ($quad) {
                    return \sprintf(self::FORMAT_HEX, $quad);
                },
                $this->quads
            ));
        }
        return $this->maxHostCalculation(self::FORMAT_HEX);
    }

    /**
     * Get maximum host IP address as binary
     *
     * @return string man host portion as binary
     */
    public function getMaxHostBinary(): string
    {
        if ($this->networkSize === 32 || $this->networkSize === 31) {
            return \implode('', \array_map(
                function ($quad) {
                    return \sprintf(self::FORMAT_BINARY, $quad);
                },
                $this->quads
            ));
        }
        return $this->maxHostCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get maximum host IP address as an Integer
     *
     * @return int max host portion as integer
     */
    public function getMaxHostInteger(): int
    {
        return $this->networkSize === 32 || $this->networkSize === 31
            ? \ip2long(\implode('.', $this->quads))
            : \ip2long($this->maxHostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get subnet mask as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string subnet mask as dotted quads
     */
    public function getSubnetMask(): string
    {
        return $this->subnetCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get subnet mask as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[] of four elements containing the four quads of the subnet mask.
     */
    public function getSubnetMaskQuads(): array
    {
        return \explode('.', $this->subnetCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get subnet mask as hexadecimal
     *
     * @return string subnet mask in hex
     */
    public function getSubnetMaskHex(): string
    {
        return $this->subnetCalculation(self::FORMAT_HEX);
    }

    /**
     * Get subnet mask as binary
     *
     * @return string subnet mask in binary
     */
    public function getSubnetMaskBinary(): string
    {
        return $this->subnetCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get subnet mask as an integer
     *
     * @return int
     */
    public function getSubnetMaskInteger(): int
    {
        return \ip2long($this->subnetCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get network portion of IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string network portion as dotted quads
     */
    public function getNetworkPortion(): string
    {
        return $this->networkCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get network portion as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[] of four elements containing the four quads of the network portion
     */
    public function getNetworkPortionQuads(): array
    {
        return \explode('.', $this->networkCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get network portion of IP address as hexadecimal
     *
     * @return string network portion in hex
     */
    public function getNetworkPortionHex(): string
    {
        return $this->networkCalculation(self::FORMAT_HEX);
    }

    /**
     * Get network portion of IP address as binary
     *
     * @return string network portion in binary
     */
    public function getNetworkPortionBinary(): string
    {
        return $this->networkCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get network portion of IP address as an integer
     *
     * @return int
     */
    public function getNetworkPortionInteger(): int
    {
        return \ip2long($this->networkCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get host portion of IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string host portion as dotted quads
     */
    public function getHostPortion(): string
    {
        return $this->hostCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get host portion as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return string[] of four elements containing the four quads of the host portion
     */
    public function getHostPortionQuads(): array
    {
        return \explode('.', $this->hostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get host portion of IP address as hexadecimal
     *
     * @return string host portion in hex
     */
    public function getHostPortionHex(): string
    {
        return $this->hostCalculation(self::FORMAT_HEX);
    }

    /**
     * Get host portion of IP address as binary
     *
     * @return string host portion in binary
     */
    public function getHostPortionBinary(): string
    {
        return $this->hostCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get host portion of IP address as an integer
     *
     * @return int
     */
    public function getHostPortionInteger(): int
    {
        return \ip2long($this->hostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get all IP addresses
     *
     * @return \Generator|string[]|false[]
     */
    public function getAllIPAddresses(): \Generator
    {
        [$startIp, $endIp] = $this->getIPAddressRangeAsInts();

        for ($ip = $startIp; $ip <= $endIp; $ip++) {
            yield \long2ip($ip);
        }
    }

    /**
     * Get all host IP addresses
     * Removes broadcast and network address if they exist.
     *
     * @return \Generator|string[]|false[]
     *
     * @throws \RuntimeException if there is an error in the IP address range calculation
     */
    public function getAllHostIPAddresses(): \Generator
    {
        [$startIp, $endIp] = $this->getIPAddressRangeAsInts();

        if ($this->getNetworkSize() < 31) {
            $startIp += 1;
            $endIp   -= 1;
        }

        for ($ip = $startIp; $ip <= $endIp; $ip++) {
            yield \long2ip($ip);
        }
    }

    /**
     * Is the IP address in the subnet?
     *
     * @param string $ipAddressString
     *
     * @return bool
     */
    public function isIPAddressInSubnet($ipAddressString): bool
    {
        $ipAddress = \ip2long($ipAddressString);
        [$startIp, $endIp] = $this->getIPAddressRangeAsInts();

        return $ipAddress >= $startIp && $ipAddress <= $endIp;
    }

    /**
     * Get the IPv4 Arpa Domain
     *
     * Reverse DNS lookups for IPv4 addresses use the special domain in-addr.arpa.
     * In this domain, an IPv4 address is represented as a concatenated sequence of four decimal numbers,
     * separated by dots, to which is appended the second level domain suffix .in-addr.arpa.
     *
     * The four decimal numbers are obtained by splitting the 32-bit IPv4 address into four octets and converting
     * each octet into a decimal number. These decimal numbers are then concatenated in the order:
     * least significant octet first (leftmost), to most significant octet last (rightmost).
     * It is important to note that this is the reverse order to the usual dotted-decimal convention for writing
     * IPv4 addresses in textual form.
     *
     * Ex: to do a reverse lookup of the IP address 8.8.4.4 the PTR record for the domain name 4.4.8.8.in-addr.arpa would be looked up.
     *
     * @link https://en.wikipedia.org/wiki/Reverse_DNS_lookup
     *
     * @return string
     */
    public function getIPv4ArpaDomain(): string
    {
        $reverseQuads = \implode('.', \array_reverse($this->quads));
        return $reverseQuads . '.in-addr.arpa';
    }

    /**
     * Get subnet calculations as an associated array
     *
     * @return mixed[] of subnet calculations
     */
    public function getSubnetArrayReport(): array
    {
        return $this->report->createArrayReport($this);
    }

    /**
     * Get subnet calculations as a JSON string
     *
     * @return string JSON string of subnet calculations
     *
     * @throws \RuntimeException if there is a JSON encode error
     */
    public function getSubnetJsonReport(): string
    {
        $json = $this->report->createJsonReport($this);

        if ($json === false) {
            throw new \RuntimeException('JSON report failure: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Print a report of subnet calculations
     */
    public function printSubnetReport(): void
    {
        $this->report->printReport($this);
    }

    /**
     * Print a report of subnet calculations
     *
     * @return string Subnet Calculator report
     */
    public function getPrintableReport(): string
    {
        return $this->report->createPrintableReport($this);
    }

    /**
     * String representation of a report of subnet calculations
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->report->createPrintableReport($this);
    }

    /* ************** *
     * PHP INTERFACES
     * ************** */

    /**
     * \JsonSerializable interface
     *
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return $this->report->createArrayReport($this);
    }

    /* ********************** *
     * PRIVATE IMPLEMENTATION
     * ********************** */

    /**
     * Calculate subnet mask
     *
     * @param  int $networkSize
     *
     * @return int
     */
    private function calculateSubnetMask(int $networkSize): int
    {
        return 0xFFFFFFFF << (32 - $networkSize);
    }

    /**
     * Calculate IP address for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted IP address
     */
    private function ipAddressCalculation(string $format, string $separator = ''): string
    {
        return \implode($separator, array_map(
            function ($quad) use ($format) {
                return \sprintf($format, $quad);
            },
            $this->quads
        ));
    }

    /**
     * Subnet calculation
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string subnet
     */
    private function subnetCalculation(string $format, string $separator = ''): string
    {
        $maskQuads = [
            \sprintf($format, ($this->subnetMask >> 24) & 0xFF),
            \sprintf($format, ($this->subnetMask >> 16) & 0xFF),
            \sprintf($format, ($this->subnetMask >>  8) & 0xFF),
            \sprintf($format, ($this->subnetMask >>  0) & 0xFF),
        ];

        return implode($separator, $maskQuads);
    }

    /**
     * Calculate network portion for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted subnet mask
     */
    private function networkCalculation(string $format, string $separator = ''): string
    {
        $networkQuads = [
            \sprintf($format, (int) $this->quads[0] & ($this->subnetMask >> 24)),
            \sprintf($format, (int) $this->quads[1] & ($this->subnetMask >> 16)),
            \sprintf($format, (int) $this->quads[2] & ($this->subnetMask >>  8)),
            \sprintf($format, (int) $this->quads[3] & ($this->subnetMask >>  0)),
        ];

        return implode($separator, $networkQuads);
    }

    /**
     * Calculate host portion for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted subnet mask
     */
    private function hostCalculation(string $format, string $separator = ''): string
    {
        $networkQuads = [
            \sprintf($format, (int) $this->quads[0] & ~($this->subnetMask >> 24)),
            \sprintf($format, (int) $this->quads[1] & ~($this->subnetMask >> 16)),
            \sprintf($format, (int) $this->quads[2] & ~($this->subnetMask >>  8)),
            \sprintf($format, (int) $this->quads[3] & ~($this->subnetMask >>  0)),
        ];

        return implode($separator, $networkQuads);
    }

    /**
     * Calculate min host for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted min host
     */
    private function minHostCalculation(string $format, string $separator = ''): string
    {
        $networkQuads = [
            \sprintf($format, (int) $this->quads[0] & ($this->subnetMask >> 24)),
            \sprintf($format, (int) $this->quads[1] & ($this->subnetMask >> 16)),
            \sprintf($format, (int) $this->quads[2] & ($this->subnetMask >>  8)),
            \sprintf($format, ((int) $this->quads[3] & ($this->subnetMask >> 0)) + 1),
        ];

        return implode($separator, $networkQuads);
    }

    /**
     * Calculate max host for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted max host
     */
    private function maxHostCalculation(string $format, string $separator = ''): string
    {
        $networkQuads      = $this->getNetworkPortionQuads();
        $numberIpAddresses = $this->getNumberIPAddresses();

        $network_range_quads = [
            \sprintf($format, ((int) $networkQuads[0] & ($this->subnetMask >> 24)) + ((($numberIpAddresses - 1) >> 24) & 0xFF)),
            \sprintf($format, ((int) $networkQuads[1] & ($this->subnetMask >> 16)) + ((($numberIpAddresses - 1) >> 16) & 0xFF)),
            \sprintf($format, ((int) $networkQuads[2] & ($this->subnetMask >>  8)) + ((($numberIpAddresses - 1) >>  8) & 0xFF)),
            \sprintf($format, ((int) $networkQuads[3] & ($this->subnetMask >>  0)) + ((($numberIpAddresses - 1) >>  0) & 0xFE)),
        ];

        return implode($separator, $network_range_quads);
    }

    /**
     * Validate IP address and network
     *
     * @param string $ipAddress   IP address in dotted quads format
     * @param int    $networkSize Network size
     *
     * @throws \UnexpectedValueException IP or network size not valid
     */
    private function validateInputs(string $ipAddress, int $networkSize): void
    {
        if (!\filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new \UnexpectedValueException("IP address $ipAddress not valid.");
        }
        if (($networkSize < 1) || ($networkSize > 32)) {
            throw new \UnexpectedValueException("Network size $networkSize not valid.");
        }
    }

    /**
     * Get the start and end of the IP address range as ints
     *
     * @return int[] [start IP, end IP]
     */
    private function getIPAddressRangeAsInts(): array
    {
        [$startIp, $endIp] = $this->getIPAddressRange();
        $startIp = \ip2long($startIp);
        $endIp   = \ip2long($endIp);

        if ($startIp === false || $endIp === false) {
            throw new \RuntimeException('IP address range calculation failed: ' . \print_r($this->getIPAddressRange(), true));
        }

        return [$startIp, $endIp];
    }
}
