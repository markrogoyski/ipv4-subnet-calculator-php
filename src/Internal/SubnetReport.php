<?php

declare(strict_types=1);

namespace IPv4\Internal;

use IPv4\IPAddress;
use IPv4\Mask;
use IPv4\Subnet;

/**
 * Internal report generator for Subnet.
 *
 * Generates formatted output for subnet data in multiple formats:
 * array, JSON, and printable string.
 *
 * @internal Used by Subnet::toArray(), toJson(), toPrintable()
 */
final class SubnetReport
{
    /**
     * Create an associative array of subnet data.
     *
     * @param Subnet $subnet
     *
     * @return array<string, mixed>
     */
    public static function createArray(Subnet $subnet): array
    {
        return [
            'cidr'         => $subnet->networkCidr(),
            'ip_address'   => self::formats($subnet->ipAddress()),
            'address_type' => $subnet->addressType()->value,
            'network_class' => [
                'class'          => $subnet->networkClass()->value,
                'default_mask'   => $subnet->defaultClassMask(),
                'default_prefix' => $subnet->defaultClassPrefix(),
                'is_classful'    => $subnet->isClassful(),
            ],
            'subnet_mask'       => self::formats($subnet->mask()),
            'wildcard_mask'     => self::formats($subnet->wildcardMask()),
            'network_portion'   => self::formats($subnet->networkPortion()),
            'host_portion'      => self::formats($subnet->hostPortion()),
            'network_size'      => $subnet->networkSize(),
            'address_count'     => $subnet->addressCount(),
            'host_count'        => $subnet->hostCount(),
            'network_address'   => $subnet->networkAddress()->asQuads(),
            'broadcast_address' => $subnet->broadcastAddress()->asQuads(),
            'min_host'          => $subnet->minHost()->asQuads(),
            'max_host'          => $subnet->maxHost()->asQuads(),
            'arpa_domain'       => $subnet->arpaDomain(),
        ];
    }

    /**
     * Create a JSON string of subnet data.
     *
     * @param Subnet $subnet
     *
     * @return string
     *
     * @throws \JsonException
     */
    public static function createJson(Subnet $subnet): string
    {
        return \json_encode(
            self::createArray($subnet),
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR
        );
    }

    /**
     * Create a printable report string.
     *
     * @param Subnet $subnet
     *
     * @return string
     */
    public static function createPrintable(Subnet $subnet): string
    {
        $output = \sprintf(
            "%-18s %15s %8s %32s %10s\n",
            $subnet->networkCidr(),
            'Quads',
            'Hex',
            'Binary',
            'Integer'
        );

        $output .= \sprintf(
            "%-18s %15s %8s %32s %10s\n",
            '------------------',
            '---------------',
            '--------',
            '--------------------------------',
            '----------'
        );

        $rows = [
            'IP Address:'      => $subnet->ipAddress(),
            'Subnet Mask:'     => $subnet->mask(),
            'Wildcard Mask:'   => $subnet->wildcardMask(),
            'Network Portion:' => $subnet->networkPortion(),
            'Host Portion:'    => $subnet->hostPortion(),
        ];

        foreach ($rows as $label => $value) {
            $output .= \sprintf(
                "%-18s %15s %8s %32s %10d\n",
                $label,
                $value->asQuads(),
                $value->asHex(),
                $value->asBinary(),
                $value->asInteger()
            );
        }

        $output .= "\n";

        $output .= \sprintf("%-28s %s\n", 'Address Type:', $subnet->addressType()->value);
        $output .= \sprintf("%-28s %s\n", 'Network Class:', $subnet->networkClass()->value);

        if ($subnet->defaultClassMask() !== null) {
            $classful = $subnet->isClassful() ? 'Yes' : 'No (subnetted/supernetted)';
            $output .= \sprintf("%-28s %s\n", 'Classful:', $classful);
        }

        $output .= \sprintf("%-28s %d\n", 'Number of IP Addresses:', $subnet->addressCount());
        $output .= \sprintf("%-28s %d\n", 'Number of Addressable Hosts:', $subnet->hostCount());

        $range = $subnet->networkAddress()->asQuads() . ' - ' . $subnet->broadcastAddress()->asQuads();
        $output .= \sprintf("%-28s %s\n", 'IP Address Range:', $range);

        $output .= \sprintf("%-28s %s\n", 'Broadcast Address:', $subnet->broadcastAddress()->asQuads());
        $output .= \sprintf("%-28s %s\n", 'Min Host:', $subnet->minHost()->asQuads());
        $output .= \sprintf("%-28s %s\n", 'Max Host:', $subnet->maxHost()->asQuads());
        $output .= \sprintf("%-28s %s\n", 'ARPA Domain:', $subnet->arpaDomain());

        return $output;
    }

    /**
     * Get the quads, hex, binary and integer formats of an address or mask.
     *
     * @param IPAddress|Mask $value
     *
     * @return array{quads: string, hex: string, binary: string, integer: int}
     */
    private static function formats(IPAddress|Mask $value): array
    {
        return [
            'quads'   => $value->asQuads(),
            'hex'     => $value->asHex(),
            'binary'  => $value->asBinary(),
            'integer' => $value->asInteger(),
        ];
    }
}
