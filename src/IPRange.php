<?php

declare(strict_types=1);

namespace IPv4;

use IPv4\Internal\RangeDecomposer;

/**
 * Immutable value object representing a range of IPv4 addresses.
 *
 * Provides iteration, constant-time indexing, containment checking, count information,
 * and exact CIDR decomposition. Used for full address ranges (network to broadcast)
 * and host ranges (usable addresses only).
 *
 * Implements IteratorAggregate for foreach support and Countable for count().
 * JSON encodes as its start and end addresses, not every address in the range.
 *
 * @implements \IteratorAggregate<int, IPAddress>
 */
final class IPRange implements \IteratorAggregate, \Countable, \JsonSerializable, \Stringable
{
    private readonly int $startInt;
    private readonly int $endInt;
    private readonly IPAddress $start;
    private readonly IPAddress $end;

    /**
     * Create an IP range from start and end addresses.
     *
     * Each address may be a dotted-quad string or an IPAddress, in any combination.
     * IPAddress instances are stored as given.
     *
     * @param string|IPAddress $start First IP in range
     * @param string|IPAddress $end   Last IP in range
     *
     * @throws \InvalidArgumentException If start or end is a string that is not a valid IPv4 address,
     *                                   or start is greater than end
     */
    public function __construct(string|IPAddress $start, string|IPAddress $end)
    {
        $this->start = \is_string($start) ? new IPAddress($start) : $start;
        $this->end = \is_string($end) ? new IPAddress($end) : $end;
        $this->startInt = $this->start->asInteger();
        $this->endInt = $this->end->asInteger();

        if ($this->startInt > $this->endInt) {
            throw new \InvalidArgumentException(
                "Start IP '{$this->start}' is greater than end IP '{$this->end}'"
            );
        }
    }

    /**
     * Get the first IP address in the range.
     *
     * @return IPAddress
     */
    public function start(): IPAddress
    {
        return $this->start;
    }

    /**
     * Get the last IP address in the range.
     *
     * @return IPAddress
     */
    public function end(): IPAddress
    {
        return $this->end;
    }

    /**
     * Get the number of IP addresses in the range.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->endInt - $this->startInt + 1;
    }

    /**
     * Get the IP address at a zero-based index in O(1) time and additional space.
     *
     * @param int $index Index from 0 to count() - 1
     *
     * @return IPAddress
     *
     * @throws \OutOfRangeException If index is negative or outside the range
     */
    public function at(int $index): IPAddress
    {
        if ($index < 0 || $index >= $this->count()) {
            throw new \OutOfRangeException("Address index {$index} is outside the range");
        }

        return IPAddress::fromInteger($this->startInt + $index);
    }

    /**
     * Find an IP address's zero-based index in O(1) time and additional space.
     *
     * @param string|IPAddress $ip IP address to find
     *
     * @return int|null Index, or null if the address is outside the range
     *
     * @throws \InvalidArgumentException If $ip is a string that is not a valid IPv4 address
     */
    public function indexOf(string|IPAddress $ip): ?int
    {
        if (\is_string($ip)) {
            $ip = new IPAddress($ip);
        }

        $ipInt = $ip->asInteger();
        if ($ipInt < $this->startInt || $ipInt > $this->endInt) {
            return null;
        }

        return $ipInt - $this->startInt;
    }

    /**
     * Check if an IP address is within this range.
     *
     * @param string|IPAddress $ip IP address to check
     *
     * @return bool
     *
     * @throws \InvalidArgumentException If $ip is a string that is not a valid IPv4 address
     */
    public function contains(string|IPAddress $ip): bool
    {
        if (\is_string($ip)) {
            $ip = new IPAddress($ip);
        }

        $ipInt = $ip->asInteger();

        return $ipInt >= $this->startInt && $ipInt <= $this->endInt;
    }

    /**
     * Check if another range lies entirely within this range.
     *
     * Containment is inclusive: a range contains itself, and shared endpoints count.
     *
     * @param self $other Range to check
     *
     * @return bool True if every address of $other is in this range
     */
    public function containsRange(self $other): bool
    {
        return $this->startInt <= $other->startInt && $other->endInt <= $this->endInt;
    }

    /**
     * Check if this range shares at least one address with another range.
     *
     * Symmetric: $a->overlaps($b) === $b->overlaps($a). A single shared endpoint counts.
     *
     * @param self $other Range to check
     *
     * @return bool True if the ranges have any address in common
     */
    public function overlaps(self $other): bool
    {
        return $this->startInt <= $other->endInt && $other->startInt <= $this->endInt;
    }

    /**
     * Get the addresses shared by this range and another range.
     *
     * The result runs from the larger of the two starts to the smaller of the two ends.
     *
     * @param self $other Range to intersect with
     *
     * @return self|null The shared range, or null if the ranges are disjoint
     */
    public function intersection(self $other): ?self
    {
        if (!$this->overlaps($other)) {
            return null;
        }

        return new self(
            $this->startInt >= $other->startInt ? $this->start : $other->start,
            $this->endInt <= $other->endInt ? $this->end : $other->end,
        );
    }

    /**
     * Check if this range and another range are disjoint and contiguous.
     *
     * True when one range ends immediately before the other starts, in either order.
     * Overlapping or identical ranges are not adjacent, and the address space does not
     * wrap: 255.255.255.255 is not adjacent to 0.0.0.0.
     *
     * @param self $other Range to check
     *
     * @return bool True if the ranges touch without overlapping
     */
    public function isAdjacent(self $other): bool
    {
        return $this->endInt + 1 === $other->startInt || $other->endInt + 1 === $this->startInt;
    }

    /**
     * Check equality with another IP range.
     *
     * @param self $other
     *
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->start->equals($other->start) && $this->end->equals($other->end);
    }

    /**
     * Iterate over all IP addresses in the range.
     *
     * @return \Generator<int, IPAddress>
     */
    public function getIterator(): \Generator
    {
        for ($ip = $this->startInt; $ip <= $this->endInt; $ip++) {
            yield IPAddress::fromInteger($ip);
        }
    }

    /**
     * Get all IP addresses as an array.
     *
     * Warning: For large ranges, this can consume significant memory.
     * Consider using iteration instead.
     *
     * @return IPAddress[]
     */
    public function toArray(): array
    {
        return \iterator_to_array($this->getIterator(), false);
    }

    /**
     * Return the smallest exact CIDR cover of this inclusive range.
     *
     * Blocks are canonical, disjoint, and sorted by ascending network address,
     * with no gaps or addresses outside the range. Does not enumerate addresses;
     * work is proportional to the output block count times the fixed 32-bit width.
     *
     * @return list<Subnet>
     */
    public function toSubnets(): array
    {
        return RangeDecomposer::toSubnets($this->startInt, $this->endInt);
    }

    /**
     * String representation of the range.
     *
     * @return string e.g., "192.168.1.0 - 192.168.1.255"
     */
    public function __toString(): string
    {
        return "{$this->start} - {$this->end}";
    }

    /**
     * JSON serialization (start and end addresses only).
     *
     * @return array{start: string, end: string} e.g., ['start' => '192.168.1.0', 'end' => '192.168.1.255']
     */
    public function jsonSerialize(): array
    {
        return [
            'start' => $this->start->asQuads(),
            'end'   => $this->end->asQuads(),
        ];
    }
}
