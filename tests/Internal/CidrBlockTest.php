<?php

declare(strict_types=1);

namespace IPv4\Tests\Internal;

use IPv4\Internal\CidrBlock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CidrBlockTest extends TestCase
{
    /**
     * @param int $start
     * @param int $prefix
     */
    #[Test]
    #[DataProvider('dataProviderForBasicInfo')]
    public function testBasicInfo(int $start, int $prefix): void
    {
        // Given
        $block = new CidrBlock($start, $prefix);

        // Then
        $this->assertSame($start, $block->startInt());
        $this->assertSame($prefix, $block->prefix());
    }

    /**
     * @param int $start
     * @param int $prefix
     * @param int $expectedSize
     */
    #[Test]
    #[DataProvider('dataProviderForBlockSize')]
    public function testBlockSize(int $start, int $prefix, int $expectedSize): void
    {
        // Given
        $block = new CidrBlock($start, $prefix);

        // When
        $size = $block->blockSize();

        // Then
        $this->assertSame($expectedSize, $size);
    }

    /**
     * @param int $start
     * @param int $prefix
     * @param int $expectedEnd
     */
    #[Test]
    #[DataProvider('dataProviderForEndInt')]
    public function testEndInt(int $start, int $prefix, int $expectedEnd): void
    {
        // Given
        $block = new CidrBlock($start, $prefix);

        // When
        $end = $block->endInt();

        // Then
        $this->assertSame($expectedEnd, $end);
    }

    /**
     * @return array<string, array{start:int, prefix:int}>
     */
    public static function dataProviderForBasicInfo(): array
    {
        return [
            'slash zero covers full space' => [
                'start' => 0,
                'prefix' => 0,
            ],
            'slash 32 covers one address' => [
                'start' => 123,
                'prefix' => 32,
            ],
            'slash 24 on zero boundary' => [
                'start' => 0,
                'prefix' => 24,
            ],
            'slash 16 on mid range boundary' => [
                'start' => 65_536,
                'prefix' => 16,
            ],
            'slash 30 on higher range' => [
                'start' => 4_294_967_292,
                'prefix' => 30,
            ],
        ];
    }

    /**
     * @return array<string, array{start:int, prefix:int, expectedSize:int}>
     */
    public static function dataProviderForBlockSize(): array
    {
        return [
            'slash zero covers full space' => [
                'start' => 0,
                'prefix' => 0,
                'expectedSize' => 4_294_967_296,
            ],
            'slash 32 covers one address' => [
                'start' => 123,
                'prefix' => 32,
                'expectedSize' => 1,
            ],
            'slash 24 on zero boundary' => [
                'start' => 0,
                'prefix' => 24,
                'expectedSize' => 256,
            ],
            'slash 16 on mid range boundary' => [
                'start' => 65_536,
                'prefix' => 16,
                'expectedSize' => 65_536,
            ],
            'slash 30 on higher range' => [
                'start' => 4_294_967_292,
                'prefix' => 30,
                'expectedSize' => 4,
            ],
        ];
    }

    /**
     * @return array<string, array{start:int, prefix:int, expectedEnd:int}>
     */
    public static function dataProviderForEndInt(): array
    {
        return [
            'slash zero ends at max IP' => [
                'start' => 0,
                'prefix' => 0,
                'expectedEnd' => 4_294_967_295,
            ],
            'slash 32 ends at start' => [
                'start' => 123,
                'prefix' => 32,
                'expectedEnd' => 123,
            ],
            'slash 24 ends at 255' => [
                'start' => 0,
                'prefix' => 24,
                'expectedEnd' => 255,
            ],
            'slash 16 ends at boundary' => [
                'start' => 65_536,
                'prefix' => 16,
                'expectedEnd' => 131_071,
            ],
            'slash 30 ends at max IP' => [
                'start' => 4_294_967_292,
                'prefix' => 30,
                'expectedEnd' => 4_294_967_295,
            ],
        ];
    }
}
