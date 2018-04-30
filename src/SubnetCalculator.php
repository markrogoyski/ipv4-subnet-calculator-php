<?php
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
 * Provides each data in dotted quads, hexadecimal, and binary formats, as well as array of quads.
 *
 * Aggregated network calculation reports:
 *  - Associative array
 *  - JSON
 *  - String
 *  - Printed to STDOUT
 *
 * @author Mark Rogoyski
 */
class SubnetCalculator
{
    /**
     * IP address as dotted quads: xxx.xxx.xxx.xxx
     * @var string
     */
    private $ip;

    /**
     * CIDR network size.
     * @var int
     */
    private $network_size;

    /**
     * Array of four elements containing the four quads of the IP address.
     * @var array
     */
    private $quads = [];

    /**
     * Subnet mask in format used for subnet calculations.
     * @var int
     */
    private $subnet_mask;

    const FORMAT_QUADS  = '%d';
    const FORMAT_HEX    = '%02X';
    const FORMAT_BINARY = '%08b';

    /**
     * Constructor - Takes IP address and network size, validates inputs, and assigns class attributes.
     * For example: 192.168.1.120/24 would be $ip = 192.168.1.120 $network_size = 24
     *
     * @param string $ip           IP address in dotted quad notation.
     * @param int    $network_size CIDR network size.
     */
    public function __construct($ip, $network_size)
    {
        $this->validateInputs($ip, $network_size);

        $this->ip           = $ip;
        $this->network_size = $network_size;
        $this->quads        = explode('.', $ip);
        $this->subnet_mask  = 0xFFFFFFFF << (32 - $this->network_size);
    }

    // PUBLIC INTERFACE

    /**
     * Get IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string IP address as dotted quads.
     */
    public function getIPAddress()
    {
        return $this->ip;
    }

    /**
     * Get IP address as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return array
     */
    public function getIPAddressQuads()
    {
        return $this->quads;
    }

    /**
     * Get IP address as hexadecimal
     *
     * @return string IP address in hex
     */
    public function getIPAddressHex()
    {
        return $this->ipAddressCalculation(self::FORMAT_HEX);
    }

    /**
     * Get IP address as binary
     *
     * @return string IP address in binary
     */
    public function getIPAddressBinary()
    {
        return $this->ipAddressCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get network size
     *
     * @return int network size
     */
    public function getNetworkSize()
    {
        return $this->network_size;
    }

    /**
     * Get the number of IP addresses in the network
     *
     * @return int Number of IP addresses
     */
    public function getNumberIPAddresses()
    {
        return pow(2, (32 - $this->network_size));
    }

    /**
     * Get the number of addressable hosts in the network
     *
     * @return int Number of IP addresses that are addressable
     */
    public function getNumberAddressableHosts()
    {
        if ($this->network_size == 32) {
            return 1;
        }
        if ($this->network_size == 31) {
            return 2;
        }

        return ($this->getNumberIPAddresses() - 2);
    }

    /**
     * Get range of IP addresses in the network
     *
     * @return array containing start and end of IP address range. IP addresses in dotted quad notation.
     */
    public function getIPAddressRange()
    {
        return [$this->getNetworkPortion(), $this->getBroadcastAddress()];
    }

    /**
     * Get range of IP addresses in the network
     *
     * @return array containing start and end of IP address range. IP addresses in dotted quad notation.
     */
    public function getAddressableHostRange()
    {
        return [$this->getMinHost(), $this->getMaxHost()];
    }

    /**
     * Get the broadcast IP address
     *
     * @return string IP address as dotted quads
     */
    public function getBroadcastAddress()
    {
        $network_quads         = $this->getNetworkPortionQuads();
        $number_ip_addresses   = $this->getNumberIPAddresses();

        $network_range_quads   = [];
        $network_range_quads[] = sprintf(self::FORMAT_QUADS, ( $network_quads[0] & ( $this->subnet_mask >> 24 ) ) + ( ( ( $number_ip_addresses - 1 ) >> 24 ) & 0xFF ));
        $network_range_quads[] = sprintf(self::FORMAT_QUADS, ( $network_quads[1] & ( $this->subnet_mask >> 16 ) ) + ( ( ( $number_ip_addresses - 1 ) >> 16 ) & 0xFF ));
        $network_range_quads[] = sprintf(self::FORMAT_QUADS, ( $network_quads[2] & ( $this->subnet_mask >>  8 ) ) + ( ( ( $number_ip_addresses - 1 ) >>  8 ) & 0xFF ));
        $network_range_quads[] = sprintf(self::FORMAT_QUADS, ( $network_quads[3] & ( $this->subnet_mask >>  0 ) ) + ( ( ( $number_ip_addresses - 1 ) >>  0 ) & 0xFF ));

        return implode('.', $network_range_quads);
    }

    /**
     * Get minimum host IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string min host as dotted quads
     */
    public function getMinHost()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return $this->ip;
        }
        return $this->minHostCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get minimum host IP address as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return array min host portion as dotted quads.
     */
    public function getMinHostQuads()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return $this->quads;
        }
        return explode('.', $this->minHostCalculation('%d', '.'));
    }

    /**
     * Get minimum host IP address as hex
     *
     * @return string min host portion as hex
     */
    public function getMinHostHex()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return implode('', array_map(
                function ($quad) {
                    return sprintf(self::FORMAT_HEX, $quad);
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
    public function getMinHostBinary()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return implode('', array_map(
                function ($quad) {
                    return sprintf(self::FORMAT_BINARY, $quad);
                },
                $this->quads
            ));
        }
        return $this->minHostCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get maximum host IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string max host as dotted quads.
     */
    public function getMaxHost()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return $this->ip;
        }
        return $this->maxHostCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get maximum host IP address as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return array min host portion as dotted quads
     */
    public function getMaxHostQuads()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return $this->quads;
        }
        return explode('.', $this->maxHostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get maximum host IP address as hex
     *
     * @return string max host portion as hex
     */
    public function getMaxHostHex()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return implode('', array_map(
                function ($quad) {
                    return sprintf(self::FORMAT_HEX, $quad);
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
    public function getMaxHostBinary()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return implode('', array_map(
                function ($quad) {
                    return sprintf(self::FORMAT_BINARY, $quad);
                },
                $this->quads
            ));
        }
        return $this->maxHostCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get subnet mask as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string subnet mask as dotted quads
     */
    public function getSubnetMask()
    {
        return $this->subnetCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get subnet mask as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return array of four elements containing the four quads of the subnet mask.
     */
    public function getSubnetMaskQuads()
    {
        return explode('.', $this->subnetCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get subnet mask as hexadecimal
     *
     * @return string subnet mask in hex
     */
    public function getSubnetMaskHex()
    {
        return $this->subnetCalculation(self::FORMAT_HEX);
    }

    /**
     * Get subnet mask as binary
     *
     * @return string subnet mask in binary
     */
    public function getSubnetMaskBinary()
    {
        return $this->subnetCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get network portion of IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string network portion as dotted quads
     */
    public function getNetworkPortion()
    {
        return $this->networkCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get network portion as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return array of four elements containing the four quads of the network portion
     */
    public function getNetworkPortionQuads()
    {
        return explode('.', $this->networkCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get network portion of IP address as hexadecimal
     *
     * @return string network portion in hex
     */
    public function getNetworkPortionHex()
    {
        return $this->networkCalculation(self::FORMAT_HEX);
    }

    /**
     * Get network portion of IP address as binary
     *
     * @return string network portion in binary
     */
    public function getNetworkPortionBinary()
    {
        return $this->networkCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get host portion of IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string host portion as dotted quads
     */
    public function getHostPortion()
    {
        return $this->hostCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get host portion as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return array of four elements containing the four quads of the host portion
     */
    public function getHostPortionQuads()
    {
        return explode('.', $this->hostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get host portion of IP address as hexadecimal
     *
     * @return string host portion in hex
     */
    public function getHostPortionHex()
    {
        return $this->hostCalculation(self::FORMAT_HEX);
    }

    /**
     * Get host portion of IP address as binary
     *
     * @return string host portion in binary
     */
    public function getHostPortionBinary()
    {
        return $this->hostCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get subnet calculations as an associated array
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexedecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @return array of subnet calculations.
     */
    public function getSubnetArrayReport()
    {
        return [
            'ip_address_with_network_size' => $this->getIPAddress() . '/' . $this->getNetworkSize(),
            'ip_address' => [
                'quads'  => $this->getIPAddress(),
                'hex'    => $this->getIPAddressHex(),
                'binary' => $this->getIPAddressBinary()
            ],
            'subnet_mask' => [
                'quads'  => $this->getSubnetMask(),
                'hex'    => $this->getSubnetMaskHex(),
                'binary' => $this->getSubnetMaskBinary()
            ],
            'network_portion' => [
                'quads'  => $this->getNetworkPortion(),
                'hex'    => $this->getNetworkPortionHex(),
                'binary' => $this->getNetworkPortionBinary()
            ],
            'host_portion' => [
                'quads'  => $this->getHostPortion(),
                'hex'    => $this->getHostPortionHex(),
                'binary' => $this->getHostPortionBinary()
            ],
            'network_size'                => $this->getNetworkSize(),
            'number_of_ip_addresses'      => $this->getNumberIPAddresses(),
            'number_of_addressable_hosts' => $this->getNumberAddressableHosts(),
            'ip_address_range'            => $this->getIPAddressRange(),
            'broadcast_address'           => $this->getBroadcastAddress(),
            'min_host'                    => $this->getMinHost(),
            'max_host'                    => $this->getMaxHost(),
        ];
    }

    /**
     * Get subnet calculations as JSON string
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexedecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @return string JSON string of subnet calculations
     */
    public function getSubnetJSONReport()
    {
        return json_encode($this->getSubnetArrayReport());
    }

    /**
     * Print a report of subnet calculations.
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexedecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     */
    public function printSubnetReport()
    {
        print($this->__tostring());
    }

    /**
     * Print a report of subnet calculations
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexedecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @return string Subnet Calculator report
     */
    public function getPrintableReport()
    {
        return $this->__tostring();
    }

    /**
     * String representation of a report of subnet calculations
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexedecimal, and binary notation.
     *
     * @return string
     */
    public function __toString()
    {
        $string  = sprintf("%-18s %15s %8s %32s\n", "{$this->ip}/{$this->network_size}", 'Quads', 'Hex', 'Binary');
        $string .= sprintf("%-18s %15s %8s %32s\n", '------------------', '---------------', '--------', '--------------------------------');
        $string .= sprintf("%-18s %15s %8s %32s\n", 'IP Address:', $this->getIPAddress(), $this->getIPAddressHex(), $this->getIPAddressBinary());
        $string .= sprintf("%-18s %15s %8s %32s\n", 'Subnet Mask:', $this->getSubnetMask(), $this->getSubnetMaskHex(), $this->getSubnetMaskBinary());
        $string .= sprintf("%-18s %15s %8s %32s\n", 'Network Portion:', $this->getNetworkPortion(), $this->getNetworkPortionHex(), $this->getNetworkPortionBinary());
        $string .= sprintf("%-18s %15s %8s %32s\n", 'Host Portion:', $this->getHostPortion(), $this->getHostPortionHex(), $this->getHostPortionBinary());
        $string .= "\n";
        $string .= sprintf("%-28s %d\n", 'Number of IP Addresses:', $this->getNumberIPAddresses());
        $string .= sprintf("%-28s %d\n", 'Number of Addressable Hosts:', $this->getNumberAddressableHosts());
        $string .= sprintf("%-28s %s\n", 'IP Address Range:', implode(' - ', $this->getIPAddressRange()));
        $string .= sprintf("%-28s %s\n", 'Broadcast Address:', $this->getBroadcastAddress());
        $string .= sprintf("%-28s %s\n", 'Min Host:', $this->getMinHost());
        $string .= sprintf("%-28s %s\n", 'Max Host:', $this->getMaxHost());

        return $string;
    }

    // PRIVATE METHODS

    /**
     * Calculate IP address for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted IP address
     */
    private function ipAddressCalculation($format, $separator = '')
    {
        return implode($separator, array_map(
            function ($x) use ($format) {
                return sprintf($format, $x);
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
    private function subnetCalculation($format, $separator = '')
    {
        $mask_quads   = [];
        $mask_quads[] = sprintf($format, ( $this->subnet_mask >> 24 ) & 0xFF);
        $mask_quads[] = sprintf($format, ( $this->subnet_mask >> 16 ) & 0xFF);
        $mask_quads[] = sprintf($format, ( $this->subnet_mask >>  8 ) & 0xFF);
        $mask_quads[] = sprintf($format, ( $this->subnet_mask >>  0 ) & 0xFF);

        return implode($separator, $mask_quads);
    }

    /**
     * Calculate network portion for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted subnet mask
     */
    private function networkCalculation($format, $separator = '')
    {
        $network_quads   = [];
        $network_quads[] = sprintf("$format", $this->quads[0] & ( $this->subnet_mask >> 24 ));
        $network_quads[] = sprintf("$format", $this->quads[1] & ( $this->subnet_mask >> 16 ));
        $network_quads[] = sprintf("$format", $this->quads[2] & ( $this->subnet_mask >>  8 ));
        $network_quads[] = sprintf("$format", $this->quads[3] & ( $this->subnet_mask >>  0 ));

        return implode($separator, $network_quads);
    }

    /**
     * Calculate host portion for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted subnet mask
     */
    private function hostCalculation($format, $separator = '')
    {
        $network_quads   = [];
        $network_quads[] = sprintf("$format", $this->quads[0] & ~( $this->subnet_mask >> 24 ));
        $network_quads[] = sprintf("$format", $this->quads[1] & ~( $this->subnet_mask >> 16 ));
        $network_quads[] = sprintf("$format", $this->quads[2] & ~( $this->subnet_mask >>  8 ));
        $network_quads[] = sprintf("$format", $this->quads[3] & ~( $this->subnet_mask >>  0 ));

        return implode($separator, $network_quads);
    }

    /**
     * Calculate min host for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted min host
     */
    private function minHostCalculation($format, $separator = '')
    {
        $network_quads   = [];
        $network_quads[] = sprintf("$format", $this->quads[0] & ($this->subnet_mask >> 24));
        $network_quads[] = sprintf("$format", $this->quads[1] & ($this->subnet_mask >> 16));
        $network_quads[] = sprintf("$format", $this->quads[2] & ($this->subnet_mask >>  8));
        $network_quads[] = sprintf("$format", ($this->quads[3] & ($this->subnet_mask >>  0)) + 1);

        return implode($separator, $network_quads);
    }

    /**
     * Calculate max host for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted max host
     */
    private function maxHostCalculation($format, $separator = '')
    {
        $network_quads         = $this->getNetworkPortionQuads();
        $number_ip_addresses   = $this->getNumberIPAddresses();

        $network_range_quads   = [];
        $network_range_quads[] = sprintf($format, ( $network_quads[0] & ( $this->subnet_mask >> 24 ) ) + ( ( ( $number_ip_addresses - 1 ) >> 24 ) & 0xFF ));
        $network_range_quads[] = sprintf($format, ( $network_quads[1] & ( $this->subnet_mask >> 16 ) ) + ( ( ( $number_ip_addresses - 1 ) >> 16 ) & 0xFF ));
        $network_range_quads[] = sprintf($format, ( $network_quads[2] & ( $this->subnet_mask >>  8 ) ) + ( ( ( $number_ip_addresses - 1 ) >>  8 ) & 0xFF ));
        $network_range_quads[] = sprintf($format, ( $network_quads[3] & ( $this->subnet_mask >>  0 ) ) + ( ( ( $number_ip_addresses - 1 ) >>  0 ) & 0xFE ));

        return implode($separator, $network_range_quads);
    }

    /**
     * Validate IP address and network
     *
     * @param string $ip           IP address in dotted quads format
     * @param int    $network_size Network size
     *
     * @throws \UnexpectedValueException IP or network size not valid
     */
    private function validateInputs($ip, $network_size)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \UnexpectedValueException("IP address $ip not valid.");
        }
        if (( $network_size < 1 ) || ( $network_size > 32 )) {
            throw new \UnexpectedValueException("Network size $network_size not valid.");
        }
    }
}
