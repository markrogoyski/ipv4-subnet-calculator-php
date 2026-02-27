<?php

declare(strict_types=1);

namespace IPv4;

/**
 * Immutable value object representing a range of IPv4 addresses.
 *
 * Provides iteration over all IPs in the range, containment checking,
 * and count information. Used for both full address ranges (network to broadcast)
 * and host ranges (usable addresses only).
 *
 * Implements IteratorAggregate for foreach support and Countable for count().
 *
 * @implements \IteratorAggregate<int, IPAddress>
 */
final class IPRange implements \IteratorAggregate, \Countable, \Stringable
{
    private readonly int $startInt;
    private readonly int $endInt;

    /**
     * Create an IP range from start and end addresses.
     *
     * @param IPAddress $start First IP in range
     * @param IPAddress $end   Last IP in range
     *
     * @throws \InvalidArgumentException If start is greater than end
     */
    public function __construct(
        private readonly IPAddress $start,
        private readonly IPAddress $end,
    ) {
        $this->startInt = $start->asInteger();
        $this->endInt = $end->asInteger();

        if ($this->startInt > $this->endInt) {
            throw new \InvalidArgumentException(
                "Start IP '{$start}' is greater than end IP '{$end}'"
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
     * Check if an IP address is within this range.
     *
     * @param string|IPAddress $ip IP address to check
     *
     * @return bool
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
     * String representation of the range.
     *
     * @return string e.g., "192.168.1.0 - 192.168.1.255"
     */
    public function __toString(): string
    {
        return "{$this->start} - {$this->end}";
    }
}
