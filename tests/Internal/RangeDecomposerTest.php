<?php

declare(strict_types=1);

namespace IPv4\Tests\Internal;

use IPv4\Internal\RangeDecomposer;
use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RangeDecomposerTest extends TestCase
{
    /**
     * @param list<string> $expectedCidrs
     */
    #[Test]
    #[DataProvider('dataProviderForToSubnets')]
    public function testToSubnetsReturnsMinimalExactCidrCover(int $start, int $end, array $expectedCidrs): void
    {
        // Given
        $expectedAddressCount = $end - $start + 1;

        // When
        $subnets = RangeDecomposer::toSubnets($start, $end);

        // Then
        $this->assertSame(
            $expectedCidrs,
            \array_map(static fn (Subnet $subnet): string => $subnet->cidr(), $subnets)
        );
        $this->assertSame(
            $expectedCidrs,
            \array_map(static fn (Subnet $subnet): string => $subnet->networkCidr(), $subnets)
        );
        $this->assertSame(
            $expectedAddressCount,
            \array_sum(\array_map(static fn (Subnet $subnet): int => $subnet->addressCount(), $subnets))
        );
    }

    /**
     * @return array<string, array{int, int, list<string>}>
     */
    public static function dataProviderForToSubnets(): array
    {
        return [
            'successively larger blocks'  => [
                1, 15,
                ['0.0.0.1/32', '0.0.0.2/31', '0.0.0.4/30', '0.0.0.8/29'],
            ],
            'successively smaller blocks' => [
                16, 30,
                ['0.0.0.16/29', '0.0.0.24/30', '0.0.0.28/31', '0.0.0.30/32'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForAlignedRanges')]
    public function testToSubnetsPreservesAlignedRangesAtEveryPrefix(int $start, int $end, int $prefix): void
    {
        // Given
        $expectedCidr = \long2ip($start) . "/{$prefix}";

        // When
        $subnets = RangeDecomposer::toSubnets($start, $end);

        // Then
        $this->assertSame(
            [$expectedCidr],
            \array_map(static fn (Subnet $subnet): string => $subnet->cidr(), $subnets)
        );
        $this->assertSame($start, $subnets[0]->networkAddress()->asInteger());
        $this->assertSame($end, $subnets[0]->broadcastAddress()->asInteger());
        $this->assertSame($end - $start + 1, $subnets[0]->addressCount());
    }

    /**
     * First and last aligned blocks for every prefix, with /0 included once.
     *
     * @return array<string, array{int, int, int}>
     */
    public static function dataProviderForAlignedRanges(): array
    {
        return [
            'first /0'  => [0x00000000, 0xFFFFFFFF, 0],
            'first /1'  => [0x00000000, 0x7FFFFFFF, 1],
            'last /1'   => [0x80000000, 0xFFFFFFFF, 1],
            'first /2'  => [0x00000000, 0x3FFFFFFF, 2],
            'last /2'   => [0xC0000000, 0xFFFFFFFF, 2],
            'first /3'  => [0x00000000, 0x1FFFFFFF, 3],
            'last /3'   => [0xE0000000, 0xFFFFFFFF, 3],
            'first /4'  => [0x00000000, 0x0FFFFFFF, 4],
            'last /4'   => [0xF0000000, 0xFFFFFFFF, 4],
            'first /5'  => [0x00000000, 0x07FFFFFF, 5],
            'last /5'   => [0xF8000000, 0xFFFFFFFF, 5],
            'first /6'  => [0x00000000, 0x03FFFFFF, 6],
            'last /6'   => [0xFC000000, 0xFFFFFFFF, 6],
            'first /7'  => [0x00000000, 0x01FFFFFF, 7],
            'last /7'   => [0xFE000000, 0xFFFFFFFF, 7],
            'first /8'  => [0x00000000, 0x00FFFFFF, 8],
            'last /8'   => [0xFF000000, 0xFFFFFFFF, 8],
            'first /9'  => [0x00000000, 0x007FFFFF, 9],
            'last /9'   => [0xFF800000, 0xFFFFFFFF, 9],
            'first /10' => [0x00000000, 0x003FFFFF, 10],
            'last /10'  => [0xFFC00000, 0xFFFFFFFF, 10],
            'first /11' => [0x00000000, 0x001FFFFF, 11],
            'last /11'  => [0xFFE00000, 0xFFFFFFFF, 11],
            'first /12' => [0x00000000, 0x000FFFFF, 12],
            'last /12'  => [0xFFF00000, 0xFFFFFFFF, 12],
            'first /13' => [0x00000000, 0x0007FFFF, 13],
            'last /13'  => [0xFFF80000, 0xFFFFFFFF, 13],
            'first /14' => [0x00000000, 0x0003FFFF, 14],
            'last /14'  => [0xFFFC0000, 0xFFFFFFFF, 14],
            'first /15' => [0x00000000, 0x0001FFFF, 15],
            'last /15'  => [0xFFFE0000, 0xFFFFFFFF, 15],
            'first /16' => [0x00000000, 0x0000FFFF, 16],
            'last /16'  => [0xFFFF0000, 0xFFFFFFFF, 16],
            'first /17' => [0x00000000, 0x00007FFF, 17],
            'last /17'  => [0xFFFF8000, 0xFFFFFFFF, 17],
            'first /18' => [0x00000000, 0x00003FFF, 18],
            'last /18'  => [0xFFFFC000, 0xFFFFFFFF, 18],
            'first /19' => [0x00000000, 0x00001FFF, 19],
            'last /19'  => [0xFFFFE000, 0xFFFFFFFF, 19],
            'first /20' => [0x00000000, 0x00000FFF, 20],
            'last /20'  => [0xFFFFF000, 0xFFFFFFFF, 20],
            'first /21' => [0x00000000, 0x000007FF, 21],
            'last /21'  => [0xFFFFF800, 0xFFFFFFFF, 21],
            'first /22' => [0x00000000, 0x000003FF, 22],
            'last /22'  => [0xFFFFFC00, 0xFFFFFFFF, 22],
            'first /23' => [0x00000000, 0x000001FF, 23],
            'last /23'  => [0xFFFFFE00, 0xFFFFFFFF, 23],
            'first /24' => [0x00000000, 0x000000FF, 24],
            'last /24'  => [0xFFFFFF00, 0xFFFFFFFF, 24],
            'first /25' => [0x00000000, 0x0000007F, 25],
            'last /25'  => [0xFFFFFF80, 0xFFFFFFFF, 25],
            'first /26' => [0x00000000, 0x0000003F, 26],
            'last /26'  => [0xFFFFFFC0, 0xFFFFFFFF, 26],
            'first /27' => [0x00000000, 0x0000001F, 27],
            'last /27'  => [0xFFFFFFE0, 0xFFFFFFFF, 27],
            'first /28' => [0x00000000, 0x0000000F, 28],
            'last /28'  => [0xFFFFFFF0, 0xFFFFFFFF, 28],
            'first /29' => [0x00000000, 0x00000007, 29],
            'last /29'  => [0xFFFFFFF8, 0xFFFFFFFF, 29],
            'first /30' => [0x00000000, 0x00000003, 30],
            'last /30'  => [0xFFFFFFFC, 0xFFFFFFFF, 30],
            'first /31' => [0x00000000, 0x00000001, 31],
            'last /31'  => [0xFFFFFFFE, 0xFFFFFFFF, 31],
            'first /32' => [0x00000000, 0x00000000, 32],
            'last /32'  => [0xFFFFFFFF, 0xFFFFFFFF, 32],
        ];
    }
}
