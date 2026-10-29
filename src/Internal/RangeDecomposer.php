<?php

declare(strict_types=1);

namespace IPv4\Internal;

use IPv4\IPAddress;
use IPv4\Subnet;

/**
 * Exact decomposition of IPv4 ranges into aligned CIDR blocks.
 *
 * @internal This class is not part of the public API
 */
final class RangeDecomposer
{
    /**
     * Cover an inclusive address range with the fewest aligned CIDR blocks.
     *
     * Each block is the largest one aligned at the current address that stays within the range.
     * Callers supply validated IPv4 endpoints. No individual addresses are enumerated;
     * each output block requires at most 32 alignment checks.
     *
     * @param int $start First address of the range
     * @param int $end   Last address of the range
     *
     * @return list<Subnet> Canonical blocks in ascending address order
     */
    public static function toSubnets(int $start, int $end): array
    {
        $subnets = [];
        while ($start <= $end) {
            $prefix = 32;
            while ($prefix > 0) {
                $parentSize = 1 << (33 - $prefix);
                if ($start % $parentSize !== 0 || $start + $parentSize - 1 > $end) {
                    break;
                }
                $prefix--;
            }

            $subnets[] = new Subnet(IPAddress::fromInteger($start), $prefix);
            $start += 1 << (32 - $prefix);
        }

        return $subnets;
    }

    /**
     * Prevent instantiation.
     * @psalm-suppress UnusedConstructor
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
