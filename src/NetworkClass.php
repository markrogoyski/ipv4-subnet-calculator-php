<?php

declare(strict_types=1);

namespace IPv4;

/**
 * Legacy network class classification (RFC 791).
 *
 * Represents the historical classful network classes (A, B, C, D, E) that were
 * used before CIDR. While classful networking is obsolete (superseded by CIDR
 * in RFC 4632), these classes are still referenced in education, certifications,
 * and some legacy systems.
 *
 * Each class is determined by the first octet of the IP address:
 *   - Class A: 0-127   (leading bit 0)     - Large networks
 *   - Class B: 128-191 (leading bits 10)   - Medium networks
 *   - Class C: 192-223 (leading bits 110)  - Small networks
 *   - Class D: 224-239 (leading bits 1110) - Multicast
 *   - Class E: 240-255 (leading bits 1111) - Reserved/Experimental
 *
 * This enum includes methods for retrieving the default classful subnet mask
 * and prefix, demonstrating PHP 8.1's ability to add behavior to enum cases.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc791  RFC 791 - Internet Protocol
 * @link https://datatracker.ietf.org/doc/html/rfc4632 RFC 4632 - CIDR (obsoletes classful routing)
 */
enum NetworkClass: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';

    /**
     * Get the default classful subnet mask for this network class.
     *
     * Classes A, B, and C have default masks. Classes D (multicast) and E (reserved)
     * do not have default masks as they were never used for unicast addressing.
     *
     * @return string|null Dotted-quad subnet mask, or null for Class D/E
     */
    public function getDefaultMask(): ?string
    {
        return match ($this) {
            self::A => '255.0.0.0',
            self::B => '255.255.0.0',
            self::C => '255.255.255.0',
            self::D, self::E => null,
        };
    }

    /**
     * Get the default classful CIDR prefix for this network class.
     *
     * Classes A, B, and C have default prefixes. Classes D (multicast) and E (reserved)
     * do not have default prefixes as they were never used for unicast addressing.
     *
     * @return int|null CIDR prefix (8, 16, or 24), or null for Class D/E
     */
    public function getDefaultPrefix(): ?int
    {
        return match ($this) {
            self::A => 8,
            self::B => 16,
            self::C => 24,
            self::D, self::E => null,
        };
    }
}
