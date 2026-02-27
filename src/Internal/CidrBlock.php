<?php

declare(strict_types=1);

namespace IPv4\Internal;

/**
 * Minimal CIDR block representation used for aggregation internals.
 *
 * @internal
 */
final class CidrBlock
{
    public function __construct(
        private readonly int $startInt,
        private readonly int $prefix,
    ) {
    }

    public function startInt(): int
    {
        return $this->startInt;
    }

    public function prefix(): int
    {
        return $this->prefix;
    }

    public function blockSize(): int
    {
        return $this->prefix === 0
            ? IPv4::ADDRESS_SPACE
            : (1 << (32 - $this->prefix));
    }

    public function endInt(): int
    {
        return $this->startInt + $this->blockSize() - 1;
    }
}
