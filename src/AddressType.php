<?php

declare(strict_types=1);

namespace IPv4;

/**
 * IP address type classification.
 *
 * Categorizes IPv4 addresses according to their purpose and routability
 * as defined by various RFCs. Each case represents a distinct address
 * space with specific networking characteristics.
 *
 * This is a string-backed enum, allowing seamless JSON serialization
 * and easy string comparison when needed via the ->value property.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1918  RFC 1918 - Private Internets
 * @link https://datatracker.ietf.org/doc/html/rfc5735  RFC 5735 - Special Use IPv4 Addresses
 */
enum AddressType: string
{
    /**
     * Private address space (RFC 1918).
     * Ranges: 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
     */
    case Private = 'private';

    /**
     * Publicly routable address.
     * Any address not in a special-purpose range.
     */
    case Public = 'public';

    /**
     * Loopback address space (RFC 1122).
     * Range: 127.0.0.0/8
     */
    case Loopback = 'loopback';

    /**
     * Link-local / APIPA address space (RFC 3927).
     * Range: 169.254.0.0/16
     */
    case LinkLocal = 'link-local';

    /**
     * Multicast address space (RFC 5771).
     * Range: 224.0.0.0/4
     */
    case Multicast = 'multicast';

    /**
     * Carrier-Grade NAT / Shared Address Space (RFC 6598).
     * Range: 100.64.0.0/10
     */
    case CarrierGradeNat = 'carrier-grade-nat';

    /**
     * Documentation address space (RFC 5737).
     * Ranges: 192.0.2.0/24, 198.51.100.0/24, 203.0.113.0/24
     */
    case Documentation = 'documentation';

    /**
     * Benchmarking address space (RFC 2544).
     * Range: 198.18.0.0/15
     */
    case Benchmarking = 'benchmarking';

    /**
     * Reserved for future use (RFC 1112).
     * Range: 240.0.0.0/4
     */
    case Reserved = 'reserved';

    /**
     * Limited broadcast address (RFC 919).
     * Address: 255.255.255.255
     */
    case LimitedBroadcast = 'limited-broadcast';

    /**
     * "This" network address space (RFC 1122).
     * Range: 0.0.0.0/8
     */
    case ThisNetwork = 'this-network';

    /**
     * IETF Protocol Assignments (RFC 5735/6890).
     * Range: 192.0.0.0/24
     * Used for DS-Lite, NAT64, and other IETF protocol assignments.
     */
    case IetfProtocol = 'ietf-protocol';

    /**
     * 6to4 Relay Anycast (RFC 3068, deprecated by RFC 7526).
     * Range: 192.88.99.0/24
     */
    case Deprecated6to4 = 'deprecated-6to4';
}
