<?php

declare(strict_types=1);

namespace IPv4\Internal;

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
            'cidr' => $subnet->cidr(),
            'ip_address' => [
                'quads'   => $subnet->ipAddress()->asQuads(),
                'hex'     => $subnet->ipAddress()->asHex(),
                'binary'  => $subnet->ipAddress()->asBinary(),
                'integer' => $subnet->ipAddress()->asInteger(),
            ],
            'address_type' => $subnet->addressType()->value,
            'network_class' => [
                'class'          => $subnet->networkClass()->value,
                'default_mask'   => $subnet->defaultClassMask(),
                'default_prefix' => $subnet->defaultClassPrefix(),
                'is_classful'    => $subnet->isClassful(),
            ],
            'subnet_mask' => [
                'quads'   => $subnet->mask()->asQuads(),
                'hex'     => $subnet->mask()->asHex(),
                'binary'  => $subnet->mask()->asBinary(),
                'integer' => $subnet->mask()->asInteger(),
            ],
            'wildcard_mask' => [
                'quads'   => $subnet->wildcardMask()->asQuads(),
                'hex'     => $subnet->wildcardMask()->asHex(),
                'binary'  => $subnet->wildcardMask()->asBinary(),
                'integer' => $subnet->wildcardMask()->asInteger(),
            ],
            'network_portion' => [
                'quads'   => $subnet->networkPortion()->asQuads(),
                'hex'     => $subnet->networkPortion()->asHex(),
                'binary'  => $subnet->networkPortion()->asBinary(),
                'integer' => $subnet->networkPortion()->asInteger(),
            ],
            'host_portion' => [
                'quads'   => $subnet->hostPortion()->asQuads(),
                'hex'     => $subnet->hostPortion()->asHex(),
                'binary'  => $subnet->hostPortion()->asBinary(),
                'integer' => $subnet->hostPortion()->asInteger(),
            ],
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
            \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR
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
        $ip = $subnet->ipAddress();
        $mask = $subnet->mask();
        $wildcard = $subnet->wildcardMask();
        $networkPortion = $subnet->networkPortion();
        $hostPortion = $subnet->hostPortion();

        $output = \sprintf(
            "%-18s %15s %8s %32s %10s\n",
            $subnet->cidr(),
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

        $output .= \sprintf(
            "%-18s %15s %8s %32s %10d\n",
            'IP Address:',
            $ip->asQuads(),
            $ip->asHex(),
            $ip->asBinary(),
            $ip->asInteger()
        );

        $output .= \sprintf(
            "%-18s %15s %8s %32s %10d\n",
            'Subnet Mask:',
            $mask->asQuads(),
            $mask->asHex(),
            $mask->asBinary(),
            $mask->asInteger()
        );

        $output .= \sprintf(
            "%-18s %15s %8s %32s %10d\n",
            'Wildcard Mask:',
            $wildcard->asQuads(),
            $wildcard->asHex(),
            $wildcard->asBinary(),
            $wildcard->asInteger()
        );

        $output .= \sprintf(
            "%-18s %15s %8s %32s %10d\n",
            'Network Portion:',
            $networkPortion->asQuads(),
            $networkPortion->asHex(),
            $networkPortion->asBinary(),
            $networkPortion->asInteger()
        );

        $output .= \sprintf(
            "%-18s %15s %8s %32s %10d\n",
            'Host Portion:',
            $hostPortion->asQuads(),
            $hostPortion->asHex(),
            $hostPortion->asBinary(),
            $hostPortion->asInteger()
        );

        $output .= \PHP_EOL;

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
}
