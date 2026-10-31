<?php

declare(strict_types=1);

namespace IPv4\Internal;

/**
 * Pinned snapshot of the IANA IPv4 Special-Purpose Address Registry "Globally Reachable" column.
 *
 * Kept separate from the AddressType taxonomy. Entries are listed in registry order;
 * lookups pick the most specific (longest) matching prefix, so order does not matter.
 * A null reachability records a blank registry field (the deprecated 6to4 relay entry).
 *
 * Refreshing: recheck the registry, review row and footnote changes, add regression
 * tests for any changed behavior first, then update ENTRIES and SNAPSHOT_DATE.
 *
 * @link https://www.iana.org/assignments/iana-ipv4-special-registry/
 *
 * @internal This class is not part of the public API
 */
final class SpecialPurposeRegistry
{
    /**
     * "Last Updated" date of the registry this table was taken from.
     */
    public const SNAPSHOT_DATE = '2025-10-09';

    /**
     * Registry entries: network, prefix, globally reachable (null if blank), name, source.
     *
     * @var list<array{int, int, bool|null, string, string}>
     */
    private const ENTRIES = [
        [0x00_00_00_00,  8, false, '"This network"',                             'RFC 791, Section 3.2'],
        [0x00_00_00_00, 32, false, '"This host on this network"',                'RFC 1122, Section 3.2.1.3'],
        [0x0A_00_00_00,  8, false, 'Private-Use',                                'RFC 1918'],
        [0x64_40_00_00, 10, false, 'Shared Address Space',                       'RFC 6598'],
        [0x7F_00_00_00,  8, false, 'Loopback',                                   'RFC 1122, Section 3.2.1.3'],
        [0xA9_FE_00_00, 16, false, 'Link Local',                                 'RFC 3927'],
        [0xAC_10_00_00, 12, false, 'Private-Use',                                'RFC 1918'],
        [0xC0_00_00_00, 24, false, 'IETF Protocol Assignments',                  'RFC 6890, Section 2.1'],
        [0xC0_00_00_00, 29, false, 'IPv4 Service Continuity Prefix',             'RFC 7335'],
        [0xC0_00_00_08, 32, false, 'IPv4 dummy address',                         'RFC 7600'],
        [0xC0_00_00_09, 32, true,  'Port Control Protocol Anycast',              'RFC 7723'],
        [0xC0_00_00_0A, 32, true,  'Traversal Using Relays around NAT Anycast',  'RFC 8155'],
        [0xC0_00_00_AA, 32, false, 'NAT64/DNS64 Discovery',                      'RFC 8880; RFC 7050, Section 2.2'],
        [0xC0_00_00_AB, 32, false, 'NAT64/DNS64 Discovery',                      'RFC 8880; RFC 7050, Section 2.2'],
        [0xC0_00_02_00, 24, false, 'Documentation (TEST-NET-1)',                 'RFC 5737'],
        [0xC0_1F_C4_00, 24, true,  'AS112-v4',                                   'RFC 7535'],
        [0xC0_34_C1_00, 24, true,  'AMT',                                        'RFC 7450'],
        [0xC0_58_63_00, 24, null,  'Deprecated (6to4 Relay Anycast)',            'RFC 7526'],
        [0xC0_58_63_02, 32, false, '6a44-relay anycast address',                 'RFC 6751'],
        [0xC0_A8_00_00, 16, false, 'Private-Use',                                'RFC 1918'],
        [0xC0_AF_30_00, 24, true,  'Direct Delegation AS112 Service',            'RFC 7534'],
        [0xC6_12_00_00, 15, false, 'Benchmarking',                               'RFC 2544'],
        [0xC6_33_64_00, 24, false, 'Documentation (TEST-NET-2)',                 'RFC 5737'],
        [0xCB_00_71_00, 24, false, 'Documentation (TEST-NET-3)',                 'RFC 5737'],
        [0xF0_00_00_00,  4, false, 'Reserved',                                   'RFC 1112, Section 4'],
        [0xFF_FF_FF_FF, 32, false, 'Limited Broadcast',                          'RFC 8190; RFC 919, Section 7'],
    ];

    /**
     * Prevent instantiation.
     * @psalm-suppress UnusedConstructor
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Whether the registry permits global reachability for the address.
     *
     * An address outside every entry is ordinary space and returns true. Otherwise the
     * most specific entry decides, and a blank field returns false conservatively.
     *
     * @param int $address IPv4 address as unsigned 32-bit integer
     *
     * @return bool
     */
    public static function isGloballyReachable(int $address): bool
    {
        $entry = self::mostSpecificEntry($address);

        return $entry === null || $entry[2] === true;
    }

    /**
     * @return array{int, int, bool|null, string, string}|null
     */
    private static function mostSpecificEntry(int $address): ?array
    {
        $match = null;
        foreach (self::ENTRIES as $entry) {
            [$network, $prefix] = $entry;
            $hostBits = 32 - $prefix;
            if (($address >> $hostBits) === ($network >> $hostBits) && ($match === null || $prefix > $match[1])) {
                $match = $entry;
            }
        }

        return $match;
    }
}
