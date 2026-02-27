<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\SubnetMask;
use IPv4\WildcardMask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class SubnetMaskTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    #[DataProvider('dataProviderForValidPrefix')]
    public function testConstructorAcceptsValidPrefix(int $prefix): void
    {
        // Given - A valid CIDR prefix (0-32)

        // When
        $mask = new SubnetMask($prefix);

        // Then
        $this->assertSame($prefix, $mask->prefix());
    }

    /**
     * @return array<string, array{int}>
     */
    public static function dataProviderForValidPrefix(): array
    {
        return [
            '/0' => [0],
            '/1' => [1],
            '/8' => [8],
            '/16' => [16],
            '/24' => [24],
            '/25' => [25],
            '/30' => [30],
            '/31' => [31],
            '/32' => [32],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForInvalidPrefix')]
    public function testConstructorThrowsExceptionForInvalidPrefix(int $invalidPrefix): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Prefix must be between 0 and 32, got {$invalidPrefix}");

        // When
        new SubnetMask($invalidPrefix);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function dataProviderForInvalidPrefix(): array
    {
        return [
            'negative -1' => [-1],
            'negative -10' => [-10],
            'too large 33' => [33],
            'too large 100' => [100],
            'too large 255' => [255],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForPrefix')]
    public function testPrefixReturnsCorrectValue(int $prefix): void
    {
        // Given
        $mask = new SubnetMask($prefix);

        // When
        $result = $mask->prefix();

        // Then
        $this->assertSame($prefix, $result);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function dataProviderForPrefix(): array
    {
        return [
            'prefix 0' => [0],
            'prefix 1' => [1],
            'prefix 2' => [2],
            'prefix 3' => [3],
            'prefix 4' => [4],
            'prefix 5' => [5],
            'prefix 6' => [6],
            'prefix 7' => [7],
            'prefix 8' => [8],
            'prefix 9' => [9],
            'prefix 10' => [10],
            'prefix 11' => [11],
            'prefix 12' => [12],
            'prefix 13' => [13],
            'prefix 14' => [14],
            'prefix 15' => [15],
            'prefix 16' => [16],
            'prefix 17' => [17],
            'prefix 18' => [18],
            'prefix 19' => [19],
            'prefix 20' => [20],
            'prefix 21' => [21],
            'prefix 22' => [22],
            'prefix 23' => [23],
            'prefix 24' => [24],
            'prefix 25' => [25],
            'prefix 26' => [26],
            'prefix 27' => [27],
            'prefix 28' => [28],
            'prefix 29' => [29],
            'prefix 30' => [30],
            'prefix 31' => [31],
            'prefix 32' => [32],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForAsQuads')]
    public function testAsQuadsReturnsCorrectFormat(int $prefix, string $expectedQuads): void
    {
        // Given
        $mask = new SubnetMask($prefix);

        // When
        $result = $mask->asQuads();

        // Then
        $this->assertSame($expectedQuads, $result);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function dataProviderForAsQuads(): array
    {
        return [
            '/0' => [0, '0.0.0.0'],
            '/1' => [1, '128.0.0.0'],
            '/2' => [2, '192.0.0.0'],
            '/3' => [3, '224.0.0.0'],
            '/4' => [4, '240.0.0.0'],
            '/5' => [5, '248.0.0.0'],
            '/6' => [6, '252.0.0.0'],
            '/7' => [7, '254.0.0.0'],
            '/8' => [8, '255.0.0.0'],
            '/9' => [9, '255.128.0.0'],
            '/10' => [10, '255.192.0.0'],
            '/11' => [11, '255.224.0.0'],
            '/12' => [12, '255.240.0.0'],
            '/13' => [13, '255.248.0.0'],
            '/14' => [14, '255.252.0.0'],
            '/15' => [15, '255.254.0.0'],
            '/16' => [16, '255.255.0.0'],
            '/17' => [17, '255.255.128.0'],
            '/18' => [18, '255.255.192.0'],
            '/19' => [19, '255.255.224.0'],
            '/20' => [20, '255.255.240.0'],
            '/21' => [21, '255.255.248.0'],
            '/22' => [22, '255.255.252.0'],
            '/23' => [23, '255.255.254.0'],
            '/24' => [24, '255.255.255.0'],
            '/25' => [25, '255.255.255.128'],
            '/26' => [26, '255.255.255.192'],
            '/27' => [27, '255.255.255.224'],
            '/28' => [28, '255.255.255.240'],
            '/29' => [29, '255.255.255.248'],
            '/30' => [30, '255.255.255.252'],
            '/31' => [31, '255.255.255.254'],
            '/32' => [32, '255.255.255.255'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForAsArray')]
    public function testAsArrayReturnsCorrectFormat(int $prefix, array $expectedArray): void
    {
        // Given
        $mask = new SubnetMask($prefix);

        // When
        $result = $mask->asArray();

        // Then
        $this->assertSame($expectedArray, $result);
    }

    /**
     * @return array<string, array{int, string[]}>
     */
    public static function dataProviderForAsArray(): array
    {
        return [
            '/0' => [0, ['0', '0', '0', '0']],
            '/8' => [8, ['255', '0', '0', '0']],
            '/16' => [16, ['255', '255', '0', '0']],
            '/24' => [24, ['255', '255', '255', '0']],
            '/25' => [25, ['255', '255', '255', '128']],
            '/26' => [26, ['255', '255', '255', '192']],
            '/30' => [30, ['255', '255', '255', '252']],
            '/32' => [32, ['255', '255', '255', '255']],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForAsHex')]
    public function testAsHexReturnsCorrectFormat(int $prefix, string $expectedHex): void
    {
        // Given
        $mask = new SubnetMask($prefix);

        // When
        $result = $mask->asHex();

        // Then
        $this->assertSame($expectedHex, $result);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function dataProviderForAsHex(): array
    {
        return [
            '/0' => [0, '00000000'],
            '/8' => [8, 'FF000000'],
            '/16' => [16, 'FFFF0000'],
            '/24' => [24, 'FFFFFF00'],
            '/25' => [25, 'FFFFFF80'],
            '/26' => [26, 'FFFFFFC0'],
            '/27' => [27, 'FFFFFFE0'],
            '/28' => [28, 'FFFFFFF0'],
            '/29' => [29, 'FFFFFFF8'],
            '/30' => [30, 'FFFFFFFC'],
            '/31' => [31, 'FFFFFFFE'],
            '/32' => [32, 'FFFFFFFF'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForAsBinary')]
    public function testAsBinaryReturnsCorrectFormat(int $prefix, string $expectedBinary): void
    {
        // Given
        $mask = new SubnetMask($prefix);

        // When
        $result = $mask->asBinary();

        // Then
        $this->assertSame($expectedBinary, $result);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function dataProviderForAsBinary(): array
    {
        return [
            '/0' => [0, '00000000000000000000000000000000'],
            '/1' => [1, '10000000000000000000000000000000'],
            '/8' => [8, '11111111000000000000000000000000'],
            '/16' => [16, '11111111111111110000000000000000'],
            '/24' => [24, '11111111111111111111111100000000'],
            '/25' => [25, '11111111111111111111111110000000'],
            '/30' => [30, '11111111111111111111111111111100'],
            '/31' => [31, '11111111111111111111111111111110'],
            '/32' => [32, '11111111111111111111111111111111'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForAsInteger')]
    public function testAsIntegerReturnsCorrectFormat(int $prefix, int $expectedInteger): void
    {
        // Given
        $mask = new SubnetMask($prefix);

        // When
        $result = $mask->asInteger();

        // Then
        $this->assertSame($expectedInteger, $result);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function dataProviderForAsInteger(): array
    {
        return [
            '/0' => [0, 0],
            '/8' => [8, 4_278_190_080], // 0xFF000000
            '/16' => [16, 4_294_901_760], // 0xFFFF0000
            '/24' => [24, 4_294_967_040], // 0xFFFFFF00
            '/25' => [25, 4_294_967_168], // 0xFFFFFF80
            '/26' => [26, 4_294_967_232], // 0xFFFFFFC0
            '/30' => [30, 4_294_967_292], // 0xFFFFFFFC
            '/32' => [32, 4_294_967_295], // 0xFFFFFFFF
        ];
    }

    #[Test]
    public function testToStringReturnsQuads(): void
    {
        // Given
        $mask = new SubnetMask(24);

        // When
        $result = (string) $mask;

        // Then
        $this->assertSame('255.255.255.0', $result);
    }

    #[Test]
    #[DataProvider('dataProviderForToString')]
    public function testToStringFormatsCorrectly(int $prefix, string $expectedString): void
    {
        // Given
        $mask = new SubnetMask($prefix);

        // When
        $result = (string) $mask;

        // Then
        $this->assertSame($expectedString, $result);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function dataProviderForToString(): array
    {
        return [
            '/0' => [0, '0.0.0.0'],
            '/8' => [8, '255.0.0.0'],
            '/16' => [16, '255.255.0.0'],
            '/24' => [24, '255.255.255.0'],
            '/32' => [32, '255.255.255.255'],
        ];
    }

    #[Test]
    public function testWildcardMaskReturnsCorrespondingWildcardMask(): void
    {
        // Given
        $mask = new SubnetMask(24);

        // When
        $wildcard = $mask->wildcardMask();

        // Then
        $this->assertInstanceOf(WildcardMask::class, $wildcard);
        $this->assertSame(24, $wildcard->prefix());
        $this->assertSame('0.0.0.255', $wildcard->asQuads());
    }

    #[Test]
    #[DataProvider('dataProviderForWildcardMask')]
    public function testWildcardMaskCreatesCorrectInverse(int $prefix, string $expectedWildcard): void
    {
        // Given
        $mask = new SubnetMask($prefix);

        // When
        $wildcard = $mask->wildcardMask();

        // Then
        $this->assertSame($prefix, $wildcard->prefix());
        $this->assertSame($expectedWildcard, $wildcard->asQuads());
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function dataProviderForWildcardMask(): array
    {
        return [
            '/0' => [0, '255.255.255.255'],
            '/8' => [8, '0.255.255.255'],
            '/16' => [16, '0.0.255.255'],
            '/24' => [24, '0.0.0.255'],
            '/25' => [25, '0.0.0.127'],
            '/30' => [30, '0.0.0.3'],
            '/32' => [32, '0.0.0.0'],
        ];
    }

    #[Test]
    public function testEqualsReturnsTrueForSamePrefix(): void
    {
        // Given
        $mask1 = new SubnetMask(24);
        $mask2 = new SubnetMask(24);

        // When
        $result = $mask1->equals($mask2);

        // Then
        $this->assertTrue($result);
    }

    #[Test]
    public function testEqualsReturnsFalseForDifferentPrefix(): void
    {
        // Given
        $mask1 = new SubnetMask(24);
        $mask2 = new SubnetMask(16);

        // When
        $result = $mask1->equals($mask2);

        // Then
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('dataProviderForEquals')]
    public function testEqualsComparesCorrectly(int $prefix1, int $prefix2, bool $expectedResult): void
    {
        // Given
        $mask1 = new SubnetMask($prefix1);
        $mask2 = new SubnetMask($prefix2);

        // When
        $result = $mask1->equals($mask2);

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{int, int, bool}>
     */
    public static function dataProviderForEquals(): array
    {
        return [
            'same /24' => [24, 24, true],
            'same /16' => [16, 16, true],
            'same /0' => [0, 0, true],
            'same /32' => [32, 32, true],
            'different /24 vs /16' => [24, 16, false],
            'different /8 vs /24' => [8, 24, false],
            'different /0 vs /32' => [0, 32, false],
            'different /30 vs /31' => [30, 31, false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForBitwiseRelationship')]
    public function testSubnetAndWildcardAreBitwiseInverses(int $prefix): void
    {
        // Given
        $subnetMask = new SubnetMask($prefix);
        $wildcardMask = $subnetMask->wildcardMask();

        // When
        $subnetInt = $subnetMask->asInteger();
        $wildcardInt = $wildcardMask->asInteger();

        // Then - Subnet XOR Wildcard should equal 0xFFFFFFFF (all bits set)
        $this->assertSame(0xFFFFFFFF, $subnetInt ^ $wildcardInt);
        // And Subnet OR Wildcard should equal 0xFFFFFFFF
        $this->assertSame(0xFFFFFFFF, $subnetInt | $wildcardInt);
        // And Subnet AND Wildcard should equal 0 (no overlapping bits)
        $this->assertSame(0, $subnetInt & $wildcardInt);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function dataProviderForBitwiseRelationship(): array
    {
        return [
            'prefix 0' => [0],
            'prefix 1' => [1],
            'prefix 2' => [2],
            'prefix 3' => [3],
            'prefix 4' => [4],
            'prefix 5' => [5],
            'prefix 6' => [6],
            'prefix 7' => [7],
            'prefix 8' => [8],
            'prefix 9' => [9],
            'prefix 10' => [10],
            'prefix 11' => [11],
            'prefix 12' => [12],
            'prefix 13' => [13],
            'prefix 14' => [14],
            'prefix 15' => [15],
            'prefix 16' => [16],
            'prefix 17' => [17],
            'prefix 18' => [18],
            'prefix 19' => [19],
            'prefix 20' => [20],
            'prefix 21' => [21],
            'prefix 22' => [22],
            'prefix 23' => [23],
            'prefix 24' => [24],
            'prefix 25' => [25],
            'prefix 26' => [26],
            'prefix 27' => [27],
            'prefix 28' => [28],
            'prefix 29' => [29],
            'prefix 30' => [30],
            'prefix 31' => [31],
            'prefix 32' => [32],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForCommonMasks')]
    public function testCommonSubnetMasksAreCorrect(int $prefix, string $expectedMask, int $expectedHosts): void
    {
        // Given
        $mask = new SubnetMask($prefix);

        // When
        $result = $mask->asQuads();

        // Then
        $this->assertSame($expectedMask, $result);

        // And - Verify the number of host bits is correct
        $hostBits = 32 - $prefix;
        $expectedAddresses = $hostBits === 0 ? 1 : (1 << $hostBits);
        $this->assertSame($expectedAddresses, $expectedHosts);
    }

    /**
     * @return array<string, array{int, string, int}>
     */
    public static function dataProviderForCommonMasks(): array
    {
        return [
            'Class A default /8' => [8, '255.0.0.0', 16_777_216],
            'Class B default /16' => [16, '255.255.0.0', 65_536],
            'Class C default /24' => [24, '255.255.255.0', 256],
            'Point-to-point /30' => [30, '255.255.255.252', 4],
            'RFC 3021 /31' => [31, '255.255.255.254', 2],
            'Single host /32' => [32, '255.255.255.255', 1],
        ];
    }
}
