<?php

declare(strict_types=1);

namespace IPv4;

use IPv4\Internal\Platform;

/**
 * Immutable value object representing a single IPv4 address.
 *
 * Provides formatting in multiple representations (dotted quads, hex, binary, integer)
 * and classification methods to identify address types (private, public, loopback, etc.).
 */
final class IPAddress implements \Stringable
{
    use Formattable;

    /** @var int[] Four octets as integers */
    private readonly array $octetValues;

    /** @var int IP address as 32-bit integer */
    private readonly int $intValue;

    /**
     * Create an IP address from dotted quad notation.
     *
     * @param string $ipAddress IP address (e.g., "192.168.1.100")
     *
     * @throws \InvalidArgumentException If IP address format is invalid
     * @throws \RuntimeException If running on 32-bit PHP
     */
    public function __construct(private readonly string $ipAddress)
    {
        Platform::ensure64Bit();

        if (!\filter_var($ipAddress, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
            throw new \InvalidArgumentException(
                "Invalid IPv4 address: '{$ipAddress}'"
            );
        }

        $this->octetValues = \array_map(\intval(...), \explode('.', $ipAddress));

        $longValue = \ip2long($ipAddress);
        $this->intValue = $longValue !== false ? $longValue : 0;
    }

    /**
     * Create an IP address from a 32-bit integer.
     *
     * Note: Out-of-range integers are wrapped using modulo 2^32, consistent
     * with PHP's long2ip() behavior.
     *
     * @param int $integer IP address as integer
     *
     * @return self
     *
     * @throws \RuntimeException If running on 32-bit PHP
     */
    public static function fromInteger(int $integer): self
    {
        return new self(\long2ip($integer));
    }

    /**
     * Check if the IP address is in a private range (RFC 1918).
     *
     * Private ranges:
     *   - 10.0.0.0/8     (10.0.0.0 - 10.255.255.255)
     *   - 172.16.0.0/12  (172.16.0.0 - 172.31.255.255)
     *   - 192.168.0.0/16 (192.168.0.0 - 192.168.255.255)
     *
     * @link https://datatracker.ietf.org/doc/html/rfc1918
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        // 10.0.0.0/8
        if ($this->isInRange(0x0A_00_00_00, 0x0A_FF_FF_FF)) {
            return true;
        }

        // 172.16.0.0/12
        if ($this->isInRange(0xAC_10_00_00, 0xAC_1F_FF_FF)) {
            return true;
        }

        // 192.168.0.0/16
        if ($this->isInRange(0xC0_A8_00_00, 0xC0_A8_FF_FF)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the IP address is publicly routable.
     *
     * An IP is public if it's not in any special-purpose range.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return !$this->isPrivate()
            && !$this->isLoopback()
            && !$this->isLinkLocal()
            && !$this->isMulticast()
            && !$this->isCarrierGradeNat()
            && !$this->isDocumentation()
            && !$this->isBenchmarking()
            && !$this->isReserved()
            && !$this->isThisNetwork()
            && !$this->isIetfProtocol()
            && !$this->is6to4Relay();
    }

    /**
     * Check if the IP address is in the loopback range (127.0.0.0/8).
     *
     * @link https://datatracker.ietf.org/doc/html/rfc1122
     *
     * @return bool
     */
    public function isLoopback(): bool
    {
        return $this->isInRange(0x7F_00_00_00, 0x7F_FF_FF_FF);
    }

    /**
     * Check if the IP address is link-local (169.254.0.0/16).
     *
     * Link-local addresses are used for APIPA when DHCP is unavailable.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc3927
     *
     * @return bool
     */
    public function isLinkLocal(): bool
    {
        return $this->isInRange(0xA9_FE_00_00, 0xA9_FE_FF_FF);
    }

    /**
     * Check if the IP address is multicast (224.0.0.0/4).
     *
     * @link https://datatracker.ietf.org/doc/html/rfc5771
     *
     * @return bool
     */
    public function isMulticast(): bool
    {
        return $this->isInRange(0xE0_00_00_00, 0xEF_FF_FF_FF);
    }

    /**
     * Check if the IP address is in Carrier-Grade NAT range (100.64.0.0/10).
     *
     * @link https://datatracker.ietf.org/doc/html/rfc6598
     *
     * @return bool
     */
    public function isCarrierGradeNat(): bool
    {
        return $this->isInRange(0x64_40_00_00, 0x64_7F_FF_FF);
    }

    /**
     * Check if the IP address is reserved for documentation (RFC 5737).
     *
     * Documentation ranges:
     *   - 192.0.2.0/24   (TEST-NET-1)
     *   - 198.51.100.0/24 (TEST-NET-2)
     *   - 203.0.113.0/24  (TEST-NET-3)
     *
     * @link https://datatracker.ietf.org/doc/html/rfc5737
     *
     * @return bool
     */
    public function isDocumentation(): bool
    {
        // TEST-NET-1
        if ($this->isInRange(0xC0_00_02_00, 0xC0_00_02_FF)) {
            return true;
        }

        // TEST-NET-2
        if ($this->isInRange(0xC6_33_64_00, 0xC6_33_64_FF)) {
            return true;
        }

        // TEST-NET-3
        if ($this->isInRange(0xCB_00_71_00, 0xCB_00_71_FF)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the IP address is reserved for benchmarking (198.18.0.0/15).
     *
     * @link https://datatracker.ietf.org/doc/html/rfc2544
     *
     * @return bool
     */
    public function isBenchmarking(): bool
    {
        return $this->isInRange(0xC6_12_00_00, 0xC6_13_FF_FF);
    }

    /**
     * Check if the IP address is reserved for future use (240.0.0.0/4).
     *
     * Note: Includes 255.255.255.255 (limited broadcast).
     *
     * @link https://datatracker.ietf.org/doc/html/rfc1112
     *
     * @return bool
     */
    public function isReserved(): bool
    {
        return $this->isInRange(0xF0_00_00_00, 0xFF_FF_FF_FF);
    }

    /**
     * Check if the IP address is the limited broadcast (255.255.255.255).
     *
     * @link https://datatracker.ietf.org/doc/html/rfc919
     *
     * @return bool
     */
    public function isLimitedBroadcast(): bool
    {
        return $this->ipAddress === '255.255.255.255';
    }

    /**
     * Check if the IP address is in the "this" network range (0.0.0.0/8).
     *
     * @link https://datatracker.ietf.org/doc/html/rfc1122
     *
     * @return bool
     */
    public function isThisNetwork(): bool
    {
        return $this->isInRange(0x00_00_00_00, 0x00_FF_FF_FF);
    }

    /**
     * Check if the IP address is reserved for IETF protocol assignments (192.0.0.0/24).
     *
     * This range is used for special IETF protocols like DS-Lite, NAT64, etc.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc5735
     * @link https://datatracker.ietf.org/doc/html/rfc6890
     *
     * @return bool
     */
    public function isIetfProtocol(): bool
    {
        return $this->isInRange(0xC0_00_00_00, 0xC0_00_00_FF);
    }

    /**
     * Check if the IP address is in the deprecated 6to4 relay range (192.88.99.0/24).
     *
     * This range was used for 6to4 anycast relay but has been deprecated.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc3068
     * @link https://datatracker.ietf.org/doc/html/rfc7526
     *
     * @return bool
     */
    public function is6to4Relay(): bool
    {
        return $this->isInRange(0xC0_58_63_00, 0xC0_58_63_FF);
    }

    /**
     * Get the address type classification.
     *
     * @return AddressType
     */
    public function addressType(): AddressType
    {
        if ($this->isThisNetwork()) {
            return AddressType::ThisNetwork;
        }
        if ($this->isPrivate()) {
            return AddressType::Private;
        }
        if ($this->isLoopback()) {
            return AddressType::Loopback;
        }
        if ($this->isLinkLocal()) {
            return AddressType::LinkLocal;
        }
        if ($this->isCarrierGradeNat()) {
            return AddressType::CarrierGradeNat;
        }
        if ($this->isDocumentation()) {
            return AddressType::Documentation;
        }
        if ($this->isBenchmarking()) {
            return AddressType::Benchmarking;
        }
        if ($this->isMulticast()) {
            return AddressType::Multicast;
        }
        if ($this->isLimitedBroadcast()) {
            return AddressType::LimitedBroadcast;
        }
        if ($this->isIetfProtocol()) {
            return AddressType::IetfProtocol;
        }
        if ($this->is6to4Relay()) {
            return AddressType::Deprecated6to4;
        }
        if ($this->isReserved()) {
            return AddressType::Reserved;
        }

        return AddressType::Public;
    }

    /**
     * Get the legacy network class.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc791
     *
     * @return NetworkClass
     */
    public function networkClass(): NetworkClass
    {
        $firstOctet = $this->octetValues[0];

        return match (true) {
            $firstOctet <= 127 => NetworkClass::A,
            $firstOctet <= 191 => NetworkClass::B,
            $firstOctet <= 223 => NetworkClass::C,
            $firstOctet <= 239 => NetworkClass::D,
            default => NetworkClass::E,
        };
    }

    /**
     * Get the reverse DNS (ARPA) domain for this IP.
     *
     * @link https://en.wikipedia.org/wiki/Reverse_DNS_lookup
     *
     * @return string e.g., "100.1.168.192.in-addr.arpa"
     */
    public function arpaDomain(): string
    {
        return \implode('.', \array_reverse($this->octetValues)) . '.in-addr.arpa';
    }

    /**
     * Check equality with another IP address.
     *
     * @param self $other
     *
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->ipAddress === $other->ipAddress;
    }

    /**
     * Get the four octets as integers.
     *
     * @return int[]
     */
    protected function octets(): array
    {
        return $this->octetValues;
    }

    /**
     * Check if the IP is within a given range (inclusive).
     *
     * @param int $start Start of range as integer
     * @param int $end   End of range as integer
     *
     * @return bool
     */
    private function isInRange(int $start, int $end): bool
    {
        return $this->intValue >= $start && $this->intValue <= $end;
    }
}
