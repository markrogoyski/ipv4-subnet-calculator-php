<?php

declare(strict_types=1);

namespace IPv4;

use IPv4\Internal\IPv4;
use IPv4\Internal\Platform;

/**
 * Immutable value object representing a single IPv4 address.
 *
 * Provides formatting in multiple representations (dotted quads, hex, binary, integer)
 * and classification methods to identify address types (private, public, loopback, etc.).
 */
final class IPAddress implements \JsonSerializable, \Stringable
{
    use Formattable;

    /** @var int IP address as 32-bit integer */
    private readonly int $intValue;

    /**
     * Create an IP address from dotted quad notation or a 32-bit integer.
     *
     * A string is always parsed as dotted quad notation, so a numeric string such as
     * "3232235777" is rejected; pass an int to construct from an integer.
     *
     * @param string|int $ipAddress IP address (e.g., "192.168.1.100" or 3232235777)
     *
     * @throws \InvalidArgumentException If IP address format is invalid or the integer is outside the IPv4 range
     * @throws \RuntimeException If running on 32-bit PHP
     */
    public function __construct(string|int $ipAddress)
    {
        Platform::ensure64Bit();

        if (\is_int($ipAddress)) {
            if ($ipAddress < 0 || $ipAddress > IPv4::MAX_ADDRESS) {
                throw new \InvalidArgumentException(
                    "IP integer out of range: {$ipAddress}"
                );
            }

            $this->intValue = $ipAddress;

            return;
        }

        if (\filter_var($ipAddress, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) === false) {
            throw new \InvalidArgumentException(
                "Invalid IPv4 address: '{$ipAddress}'"
            );
        }

        $this->intValue = (int) \ip2long($ipAddress);
    }

    /**
     * Create an IP address from a 32-bit integer.
     *
     * Equivalent to `new IPAddress($integer)`.
     *
     * @param int $integer IP address as unsigned integer (0 to 4294967295)
     *
     * @return self
     *
     * @throws \InvalidArgumentException If the integer is outside the IPv4 range
     * @throws \RuntimeException If running on 32-bit PHP
     */
    public static function fromInteger(int $integer): self
    {
        return new self($integer);
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
        return $this->isInBlock(0x0A_00_00_00, 8)      // 10.0.0.0/8
            || $this->isInBlock(0xAC_10_00_00, 12)     // 172.16.0.0/12
            || $this->isInBlock(0xC0_A8_00_00, 16);    // 192.168.0.0/16
    }

    /**
     * Check if the IP address is public: not in any of the special-purpose ranges recognized by this library.
     *
     * Stricter than global reachability: a few special-purpose blocks (e.g., 192.0.0.9/32,
     * 192.31.196.0/24) are globally reachable but are not counted as public.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->addressType() === AddressType::Public;
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
        return $this->isInBlock(0x7F_00_00_00, 8);
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
        return $this->isInBlock(0xA9_FE_00_00, 16);
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
        return $this->isInBlock(0xE0_00_00_00, 4);
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
        return $this->isInBlock(0x64_40_00_00, 10);
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
        return $this->isInBlock(0xC0_00_02_00, 24)     // TEST-NET-1: 192.0.2.0/24
            || $this->isInBlock(0xC6_33_64_00, 24)     // TEST-NET-2: 198.51.100.0/24
            || $this->isInBlock(0xCB_00_71_00, 24);    // TEST-NET-3: 203.0.113.0/24
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
        return $this->isInBlock(0xC6_12_00_00, 15);
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
        return $this->isInBlock(0xF0_00_00_00, 4);
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
        return $this->intValue === IPv4::MAX_ADDRESS;
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
        return $this->isInBlock(0x00_00_00_00, 8);
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
        return $this->isInBlock(0xC0_00_00_00, 24);
    }

    /**
     * Check if the IP address is in the deprecated 6to4 relay range (192.88.99.0/24).
     *
     * This range was used for 6to4 anycast relay but has been deprecated.
     * Excludes 192.88.99.2, the 6a44 relay anycast address, which IANA lists
     * separately with no termination date (see isIanaReserved()).
     *
     * @link https://datatracker.ietf.org/doc/html/rfc3068
     * @link https://datatracker.ietf.org/doc/html/rfc7526
     *
     * @return bool
     */
    public function is6to4Relay(): bool
    {
        return $this->isInBlock(0xC0_58_63_00, 24)     // 6to4 Relay Anycast: 192.88.99.0/24
            && !$this->isInBlock(0xC0_58_63_02, 32);   // except 6a44 relay anycast: 192.88.99.2/32
    }

    /**
     * Check if the IP address is in an IANA Special-Purpose Address Registry range.
     *
     * IANA reserved ranges:
     *   - 192.31.196.0/24  (AS112-v4, RFC 7535)
     *   - 192.52.193.0/24  (AMT, RFC 7450)
     *   - 192.175.48.0/24  (Direct Delegation AS112, RFC 7534)
     *   - 192.88.99.2/32   (6a44 relay anycast, RFC 6751)
     *
     * @link https://datatracker.ietf.org/doc/html/rfc7535
     * @link https://datatracker.ietf.org/doc/html/rfc7450
     * @link https://datatracker.ietf.org/doc/html/rfc7534
     * @link https://datatracker.ietf.org/doc/html/rfc6751
     *
     * @return bool
     */
    public function isIanaReserved(): bool
    {
        return $this->isInBlock(0xC0_1F_C4_00, 24)     // AS112-v4: 192.31.196.0/24
            || $this->isInBlock(0xC0_34_C1_00, 24)     // AMT: 192.52.193.0/24
            || $this->isInBlock(0xC0_AF_30_00, 24)     // Direct Delegation AS112: 192.175.48.0/24
            || $this->isInBlock(0xC0_58_63_02, 32);    // 6a44 relay anycast: 192.88.99.2/32
    }

    /**
     * Get the address type classification.
     *
     * @return AddressType
     */
    public function addressType(): AddressType
    {
        // Order matters: limited broadcast (255.255.255.255) must be checked before reserved (240.0.0.0/4)
        return match (true) {
            $this->isThisNetwork()      => AddressType::ThisNetwork,
            $this->isPrivate()          => AddressType::Private,
            $this->isLoopback()         => AddressType::Loopback,
            $this->isLinkLocal()        => AddressType::LinkLocal,
            $this->isCarrierGradeNat()  => AddressType::CarrierGradeNat,
            $this->isDocumentation()    => AddressType::Documentation,
            $this->isBenchmarking()     => AddressType::Benchmarking,
            $this->isMulticast()        => AddressType::Multicast,
            $this->isLimitedBroadcast() => AddressType::LimitedBroadcast,
            $this->isIetfProtocol()     => AddressType::IetfProtocol,
            $this->is6to4Relay()        => AddressType::Deprecated6to4,
            $this->isReserved()         => AddressType::Reserved,
            $this->isIanaReserved()     => AddressType::IanaReserved,
            default                     => AddressType::Public,
        };
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
        $firstOctet = $this->intValue >> 24;

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
        return \implode('.', \array_reverse($this->octets())) . '.in-addr.arpa';
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
        return $this->intValue === $other->intValue;
    }

    /**
     * Get the IP address as a 32-bit unsigned integer.
     *
     * @return int
     */
    public function asInteger(): int
    {
        return $this->intValue;
    }

    /**
     * Check if the IP is within a given CIDR block.
     *
     * @param int $network Network address of the block as integer
     * @param int $prefix  CIDR prefix of the block (1-32)
     *
     * @return bool
     */
    private function isInBlock(int $network, int $prefix): bool
    {
        $hostBits = 32 - $prefix;

        return ($this->intValue >> $hostBits) === ($network >> $hostBits);
    }
}
