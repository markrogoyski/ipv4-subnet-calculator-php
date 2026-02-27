<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\SubnetMask;
use IPv4\WildcardMask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class WildcardMaskTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    #[DataProvider('dataProviderForValidPrefix')]
    public function testConstructorAcceptsValidPrefix(int $prefix): void
    {
        // Given - A valid CIDR prefix (0-32)

        // When
        $mask = new WildcardMask($prefix);

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
        new WildcardMask($invalidPrefix);
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
        $mask = new WildcardMask($prefix);

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
        $mask = new WildcardMask($prefix);

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
            '/0' => [0, '255.255.255.255'],
            '/1' => [1, '127.255.255.255'],
            '/2' => [2, '63.255.255.255'],
            '/3' => [3, '31.255.255.255'],
            '/4' => [4, '15.255.255.255'],
            '/5' => [5, '7.255.255.255'],
            '/6' => [6, '3.255.255.255'],
            '/7' => [7, '1.255.255.255'],
            '/8' => [8, '0.255.255.255'],
            '/9' => [9, '0.127.255.255'],
            '/10' => [10, '0.63.255.255'],
            '/11' => [11, '0.31.255.255'],
            '/12' => [12, '0.15.255.255'],
            '/13' => [13, '0.7.255.255'],
            '/14' => [14, '0.3.255.255'],
            '/15' => [15, '0.1.255.255'],
            '/16' => [16, '0.0.255.255'],
            '/17' => [17, '0.0.127.255'],
            '/18' => [18, '0.0.63.255'],
            '/19' => [19, '0.0.31.255'],
            '/20' => [20, '0.0.15.255'],
            '/21' => [21, '0.0.7.255'],
            '/22' => [22, '0.0.3.255'],
            '/23' => [23, '0.0.1.255'],
            '/24' => [24, '0.0.0.255'],
            '/25' => [25, '0.0.0.127'],
            '/26' => [26, '0.0.0.63'],
            '/27' => [27, '0.0.0.31'],
            '/28' => [28, '0.0.0.15'],
            '/29' => [29, '0.0.0.7'],
            '/30' => [30, '0.0.0.3'],
            '/31' => [31, '0.0.0.1'],
            '/32' => [32, '0.0.0.0'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForAsArray')]
    public function testAsArrayReturnsCorrectFormat(int $prefix, array $expectedArray): void
    {
        // Given
        $mask = new WildcardMask($prefix);

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
            '/0' => [0, ['255', '255', '255', '255']],
            '/8' => [8, ['0', '255', '255', '255']],
            '/16' => [16, ['0', '0', '255', '255']],
            '/24' => [24, ['0', '0', '0', '255']],
            '/25' => [25, ['0', '0', '0', '127']],
            '/26' => [26, ['0', '0', '0', '63']],
            '/30' => [30, ['0', '0', '0', '3']],
            '/32' => [32, ['0', '0', '0', '0']],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForAsHex')]
    public function testAsHexReturnsCorrectFormat(int $prefix, string $expectedHex): void
    {
        // Given
        $mask = new WildcardMask($prefix);

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
            '/0' => [0, 'FFFFFFFF'],
            '/8' => [8, '00FFFFFF'],
            '/16' => [16, '0000FFFF'],
            '/24' => [24, '000000FF'],
            '/25' => [25, '0000007F'],
            '/26' => [26, '0000003F'],
            '/27' => [27, '0000001F'],
            '/28' => [28, '0000000F'],
            '/29' => [29, '00000007'],
            '/30' => [30, '00000003'],
            '/31' => [31, '00000001'],
            '/32' => [32, '00000000'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForAsBinary')]
    public function testAsBinaryReturnsCorrectFormat(int $prefix, string $expectedBinary): void
    {
        // Given
        $mask = new WildcardMask($prefix);

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
            '/0' => [0, '11111111111111111111111111111111'],
            '/1' => [1, '01111111111111111111111111111111'],
            '/8' => [8, '00000000111111111111111111111111'],
            '/16' => [16, '00000000000000001111111111111111'],
            '/24' => [24, '00000000000000000000000011111111'],
            '/25' => [25, '00000000000000000000000001111111'],
            '/30' => [30, '00000000000000000000000000000011'],
            '/31' => [31, '00000000000000000000000000000001'],
            '/32' => [32, '00000000000000000000000000000000'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForAsInteger')]
    public function testAsIntegerReturnsCorrectFormat(int $prefix, int $expectedInteger): void
    {
        // Given
        $mask = new WildcardMask($prefix);

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
            '/0' => [0, 4_294_967_295], // 0xFFFFFFFF
            '/8' => [8, 16_777_215], // 0x00FFFFFF
            '/16' => [16, 65_535], // 0x0000FFFF
            '/24' => [24, 255], // 0x000000FF
            '/25' => [25, 127], // 0x0000007F
            '/26' => [26, 63], // 0x0000003F
            '/30' => [30, 3], // 0x00000003
            '/32' => [32, 0], // 0x00000000
        ];
    }

    #[Test]
    public function testToStringReturnsQuads(): void
    {
        // Given
        $mask = new WildcardMask(24);

        // When
        $result = (string) $mask;

        // Then
        $this->assertSame('0.0.0.255', $result);
    }

    #[Test]
    #[DataProvider('dataProviderForToString')]
    public function testToStringFormatsCorrectly(int $prefix, string $expectedString): void
    {
        // Given
        $mask = new WildcardMask($prefix);

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
            '/0' => [0, '255.255.255.255'],
            '/8' => [8, '0.255.255.255'],
            '/16' => [16, '0.0.255.255'],
            '/24' => [24, '0.0.0.255'],
            '/32' => [32, '0.0.0.0'],
        ];
    }

    #[Test]
    public function testSubnetMaskReturnsCorrespondingSubnetMask(): void
    {
        // Given
        $mask = new WildcardMask(24);

        // When
        $subnet = $mask->subnetMask();

        // Then
        $this->assertInstanceOf(SubnetMask::class, $subnet);
        $this->assertSame(24, $subnet->prefix());
        $this->assertSame('255.255.255.0', $subnet->asQuads());
    }

    #[Test]
    #[DataProvider('dataProviderForSubnetMask')]
    public function testSubnetMaskCreatesCorrectInverse(int $prefix, string $expectedSubnet): void
    {
        // Given
        $mask = new WildcardMask($prefix);

        // When
        $subnet = $mask->subnetMask();

        // Then
        $this->assertSame($prefix, $subnet->prefix());
        $this->assertSame($expectedSubnet, $subnet->asQuads());
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function dataProviderForSubnetMask(): array
    {
        return [
            '/0' => [0, '0.0.0.0'],
            '/8' => [8, '255.0.0.0'],
            '/16' => [16, '255.255.0.0'],
            '/24' => [24, '255.255.255.0'],
            '/25' => [25, '255.255.255.128'],
            '/30' => [30, '255.255.255.252'],
            '/32' => [32, '255.255.255.255'],
        ];
    }

    #[Test]
    public function testEqualsReturnsTrueForSamePrefix(): void
    {
        // Given
        $mask1 = new WildcardMask(24);
        $mask2 = new WildcardMask(24);

        // When
        $result = $mask1->equals($mask2);

        // Then
        $this->assertTrue($result);
    }

    #[Test]
    public function testEqualsReturnsFalseForDifferentPrefix(): void
    {
        // Given
        $mask1 = new WildcardMask(24);
        $mask2 = new WildcardMask(16);

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
        $mask1 = new WildcardMask($prefix1);
        $mask2 = new WildcardMask($prefix2);

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
    public function testWildcardAndSubnetAreBitwiseInverses(int $prefix): void
    {
        // Given
        $wildcardMask = new WildcardMask($prefix);
        $subnetMask = $wildcardMask->subnetMask();

        // When
        $wildcardInt = $wildcardMask->asInteger();
        $subnetInt = $subnetMask->asInteger();

        // Then - Wildcard XOR Subnet should equal 0xFFFFFFFF (all bits set)
        $this->assertSame(0xFFFFFFFF, $wildcardInt ^ $subnetInt);
        // And Wildcard OR Subnet should equal 0xFFFFFFFF
        $this->assertSame(0xFFFFFFFF, $wildcardInt | $subnetInt);
        // And Wildcard AND Subnet should equal 0 (no overlapping bits)
        $this->assertSame(0, $wildcardInt & $subnetInt);
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
    #[DataProvider('dataProviderForCommonWildcards')]
    public function testCommonWildcardMasksAreCorrect(
        int $prefix,
        string $expectedMask,
        string $ciscoUsage
    ): void {
        // Given
        $mask = new WildcardMask($prefix);

        // When
        $result = $mask->asQuads();

        // Then
        $this->assertSame($expectedMask, $result);
        // The Cisco usage parameter is for documentation only
        $this->assertIsString($ciscoUsage);
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function dataProviderForCommonWildcards(): array
    {
        return [
            'Class A /8' => [8, '0.255.255.255', 'Match any host in 10.0.0.0/8'],
            'Class B /16' => [16, '0.0.255.255', 'Match any host in 172.16.0.0/16'],
            'Class C /24' => [24, '0.0.0.255', 'Match any host in 192.168.1.0/24'],
            'Point-to-point /30' => [30, '0.0.0.3', 'Match 4 IPs'],
            'RFC 3021 /31' => [31, '0.0.0.1', 'Match 2 IPs'],
            'Single host /32' => [32, '0.0.0.0', 'Match exact host'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForRoundTrip')]
    public function testRoundTripConversionMaintainsPrefix(int $prefix): void
    {
        // Given
        $wildcard = new WildcardMask($prefix);

        // When - Convert to subnet and back to wildcard
        $subnet = $wildcard->subnetMask();
        $wildcardAgain = $subnet->wildcardMask();

        // Then
        $this->assertSame($prefix, $wildcardAgain->prefix());
        $this->assertSame($wildcard->asQuads(), $wildcardAgain->asQuads());
        $this->assertTrue($wildcard->equals($wildcardAgain));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function dataProviderForRoundTrip(): array
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
    public function testWildcardMaskInterfaceImplementation(): void
    {
        // Given
        $mask = new WildcardMask(24);

        // When / Then - Verify it implements the Mask interface properly
        $this->assertInstanceOf(\Stringable::class, $mask);
        $this->assertIsInt($mask->prefix());
        $this->assertIsString($mask->asQuads());
        $this->assertIsArray($mask->asArray());
        $this->assertIsString($mask->asHex());
        $this->assertIsString($mask->asBinary());
        $this->assertIsInt($mask->asInteger());
        $this->assertIsString((string) $mask);
    }
}
