<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\NetworkClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class NetworkClassTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    public function testEnumHasAllFiveCases(): void
    {
        // Given - The NetworkClass enum

        // When
        $cases = NetworkClass::cases();

        // Then
        $this->assertCount(5, $cases);
        $this->assertSame(NetworkClass::A, $cases[0]);
        $this->assertSame(NetworkClass::B, $cases[1]);
        $this->assertSame(NetworkClass::C, $cases[2]);
        $this->assertSame(NetworkClass::D, $cases[3]);
        $this->assertSame(NetworkClass::E, $cases[4]);
    }

    #[Test]
    #[DataProvider('dataProviderForEnumValues')]
    public function testEnumCasesHaveCorrectStringValues(NetworkClass $class, string $expectedValue): void
    {
        // Given - A NetworkClass enum case

        // When
        $value = $class->value;

        // Then
        $this->assertSame($expectedValue, $value);
    }

    /**
     * @return array<string, array{NetworkClass, string}>
     */
    public static function dataProviderForEnumValues(): array
    {
        return [
            'Class A' => [NetworkClass::A, 'A'],
            'Class B' => [NetworkClass::B, 'B'],
            'Class C' => [NetworkClass::C, 'C'],
            'Class D' => [NetworkClass::D, 'D'],
            'Class E' => [NetworkClass::E, 'E'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForFromString')]
    public function testFromCreatesEnumFromString(string $value, NetworkClass $expectedClass): void
    {
        // Given - A string value representing a network class

        // When
        $class = NetworkClass::from($value);

        // Then
        $this->assertSame($expectedClass, $class);
    }

    /**
     * @return array<string, array{string, NetworkClass}>
     */
    public static function dataProviderForFromString(): array
    {
        return [
            'A' => ['A', NetworkClass::A],
            'B' => ['B', NetworkClass::B],
            'C' => ['C', NetworkClass::C],
            'D' => ['D', NetworkClass::D],
            'E' => ['E', NetworkClass::E],
        ];
    }

    #[Test]
    public function testFromThrowsExceptionForInvalidValue(): void
    {
        // Then
        $this->expectException(\ValueError::class);

        // When
        NetworkClass::from('F');
    }

    #[Test]
    #[DataProvider('dataProviderForInvalidFromValues')]
    public function testFromThrowsExceptionForVariousInvalidValues(string $invalidValue): void
    {
        // Then
        $this->expectException(\ValueError::class);

        // When
        NetworkClass::from($invalidValue);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dataProviderForInvalidFromValues(): array
    {
        return [
            'lowercase a' => ['a'],
            'lowercase b' => ['b'],
            'F' => ['F'],
            'Z' => ['Z'],
            'empty' => [''],
            'number' => ['1'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForTryFrom')]
    public function testTryFromReturnsEnumOrNull(string $value, ?NetworkClass $expectedClass): void
    {
        // Given - A string value

        // When
        $class = NetworkClass::tryFrom($value);

        // Then
        $this->assertSame($expectedClass, $class);
    }

    /**
     * @return array<string, array{string, ?NetworkClass}>
     */
    public static function dataProviderForTryFrom(): array
    {
        return [
            'valid A' => ['A', NetworkClass::A],
            'valid B' => ['B', NetworkClass::B],
            'valid C' => ['C', NetworkClass::C],
            'valid D' => ['D', NetworkClass::D],
            'valid E' => ['E', NetworkClass::E],
            'invalid lowercase' => ['a', null],
            'invalid F' => ['F', null],
            'invalid empty' => ['', null],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForGetDefaultMask')]
    public function testGetDefaultMaskReturnsCorrectMask(NetworkClass $class, ?string $expectedMask): void
    {
        // Given - A NetworkClass enum case

        // When
        $mask = $class->getDefaultMask();

        // Then
        $this->assertSame($expectedMask, $mask);
    }

    /**
     * @return array<string, array{NetworkClass, ?string}>
     */
    public static function dataProviderForGetDefaultMask(): array
    {
        return [
            'Class A /8' => [NetworkClass::A, '255.0.0.0'],
            'Class B /16' => [NetworkClass::B, '255.255.0.0'],
            'Class C /24' => [NetworkClass::C, '255.255.255.0'],
            'Class D multicast' => [NetworkClass::D, null],
            'Class E reserved' => [NetworkClass::E, null],
        ];
    }

    #[Test]
    public function testGetDefaultMaskReturnsNullForMulticast(): void
    {
        // Given
        $class = NetworkClass::D;

        // When
        $mask = $class->getDefaultMask();

        // Then
        $this->assertNull($mask);
    }

    #[Test]
    public function testGetDefaultMaskReturnsNullForReserved(): void
    {
        // Given
        $class = NetworkClass::E;

        // When
        $mask = $class->getDefaultMask();

        // Then
        $this->assertNull($mask);
    }

    #[Test]
    #[DataProvider('dataProviderForGetDefaultPrefix')]
    public function testGetDefaultPrefixReturnsCorrectPrefix(NetworkClass $class, ?int $expectedPrefix): void
    {
        // Given - A NetworkClass enum case

        // When
        $prefix = $class->getDefaultPrefix();

        // Then
        $this->assertSame($expectedPrefix, $prefix);
    }

    /**
     * @return array<string, array{NetworkClass, ?int}>
     */
    public static function dataProviderForGetDefaultPrefix(): array
    {
        return [
            'Class A /8' => [NetworkClass::A, 8],
            'Class B /16' => [NetworkClass::B, 16],
            'Class C /24' => [NetworkClass::C, 24],
            'Class D multicast' => [NetworkClass::D, null],
            'Class E reserved' => [NetworkClass::E, null],
        ];
    }

    #[Test]
    public function testGetDefaultPrefixReturnsNullForMulticast(): void
    {
        // Given
        $class = NetworkClass::D;

        // When
        $prefix = $class->getDefaultPrefix();

        // Then
        $this->assertNull($prefix);
    }

    #[Test]
    public function testGetDefaultPrefixReturnsNullForReserved(): void
    {
        // Given
        $class = NetworkClass::E;

        // When
        $prefix = $class->getDefaultPrefix();

        // Then
        $this->assertNull($prefix);
    }

    #[Test]
    #[DataProvider('dataProviderForUnicastMaskAndPrefixConsistency')]
    public function testUnicastClassesMaskAndPrefixAreBothSet(NetworkClass $class): void
    {
        // Given - A unicast NetworkClass (A, B, or C)

        // When
        $mask = $class->getDefaultMask();
        $prefix = $class->getDefaultPrefix();

        // Then - Both mask and prefix should be set
        $this->assertIsString($mask);
        $this->assertIsInt($prefix);
        $this->assertGreaterThan(0, $prefix);
        $this->assertLessThanOrEqual(32, $prefix);
    }

    /**
     * @return array<string, array{NetworkClass}>
     */
    public static function dataProviderForUnicastMaskAndPrefixConsistency(): array
    {
        return [
            'Class A' => [NetworkClass::A],
            'Class B' => [NetworkClass::B],
            'Class C' => [NetworkClass::C],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForNonUnicastMaskAndPrefixConsistency')]
    public function testNonUnicastClassesMaskAndPrefixAreBothNull(NetworkClass $class): void
    {
        // Given - A non-unicast NetworkClass (D or E)

        // When
        $mask = $class->getDefaultMask();
        $prefix = $class->getDefaultPrefix();

        // Then - Both mask and prefix should be null
        $this->assertNull($mask);
        $this->assertNull($prefix);
    }

    /**
     * @return array<string, array{NetworkClass}>
     */
    public static function dataProviderForNonUnicastMaskAndPrefixConsistency(): array
    {
        return [
            'Class D' => [NetworkClass::D],
            'Class E' => [NetworkClass::E],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForUnicastClasses')]
    public function testUnicastClassesHaveDefaultMasks(NetworkClass $class): void
    {
        // Given - A unicast network class (A, B, or C)

        // When
        $mask = $class->getDefaultMask();
        $prefix = $class->getDefaultPrefix();

        // Then
        $this->assertIsString($mask);
        $this->assertIsInt($prefix);
        $this->assertMatchesRegularExpression('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $mask);
    }

    /**
     * @return array<string, array{NetworkClass}>
     */
    public static function dataProviderForUnicastClasses(): array
    {
        return [
            'Class A' => [NetworkClass::A],
            'Class B' => [NetworkClass::B],
            'Class C' => [NetworkClass::C],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForNonUnicastClasses')]
    public function testNonUnicastClassesHaveNullDefaults(NetworkClass $class): void
    {
        // Given - A non-unicast network class (D or E)

        // When
        $mask = $class->getDefaultMask();
        $prefix = $class->getDefaultPrefix();

        // Then
        $this->assertNull($mask);
        $this->assertNull($prefix);
    }

    /**
     * @return array<string, array{NetworkClass}>
     */
    public static function dataProviderForNonUnicastClasses(): array
    {
        return [
            'Class D multicast' => [NetworkClass::D],
            'Class E reserved' => [NetworkClass::E],
        ];
    }

    #[Test]
    public function testEnumCasesAreComparable(): void
    {
        // Given
        $classA1 = NetworkClass::A;
        $classA2 = NetworkClass::A;
        $classB = NetworkClass::B;

        // When / Then
        $this->assertSame($classA1, $classA2);
        $this->assertNotSame($classA1, $classB);
        $this->assertTrue($classA1 === $classA2);
        $this->assertFalse($classA1 === $classB);
    }

    #[Test]
    #[DataProvider('dataProviderForUnicastClassfulNetworkSizes')]
    public function testUnicastClassfulNetworkSizesAreCorrect(
        NetworkClass $class,
        int $expectedPrefix,
        int $expectedAddressesPerNetwork
    ): void {
        // Given - A unicast network class (A, B, or C)

        // When
        $actualPrefix = $class->getDefaultPrefix();

        // Then
        $this->assertSame($expectedPrefix, $actualPrefix);

        // And - Verify the addresses per network calculation
        $hostBits = 32 - $expectedPrefix;
        $addressesPerNetwork = 1 << $hostBits;
        $this->assertSame($expectedAddressesPerNetwork, $addressesPerNetwork);
    }

    /**
     * @return array<string, array{NetworkClass, int, int}>
     */
    public static function dataProviderForUnicastClassfulNetworkSizes(): array
    {
        return [
            'Class A /8' => [NetworkClass::A, 8, 16_777_216], // 2^24 addresses per network
            'Class B /16' => [NetworkClass::B, 16, 65_536], // 2^16 addresses per network
            'Class C /24' => [NetworkClass::C, 24, 256], // 2^8 addresses per network
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForNonUnicastClassfulNetworkSizes')]
    public function testNonUnicastClassesHaveNoDefaultPrefix(NetworkClass $class): void
    {
        // Given - A non-unicast network class (D or E)

        // When
        $prefix = $class->getDefaultPrefix();

        // Then
        $this->assertNull($prefix);
    }

    /**
     * @return array<string, array{NetworkClass}>
     */
    public static function dataProviderForNonUnicastClassfulNetworkSizes(): array
    {
        return [
            'Class D multicast' => [NetworkClass::D],
            'Class E reserved' => [NetworkClass::E],
        ];
    }

    #[Test]
    public function testEnumIsBackedByString(): void
    {
        // Given - The NetworkClass enum

        // When / Then - Verify it's a backed enum with string values
        $this->assertInstanceOf(\BackedEnum::class, NetworkClass::A);
        $this->assertIsString(NetworkClass::A->value);
        $this->assertIsString(NetworkClass::B->value);
        $this->assertIsString(NetworkClass::C->value);
        $this->assertIsString(NetworkClass::D->value);
        $this->assertIsString(NetworkClass::E->value);
    }

    #[Test]
    #[DataProvider('dataProviderForNameProperty')]
    public function testEnumCasesHaveCorrectNames(NetworkClass $class, string $expectedName): void
    {
        // Given - A NetworkClass enum case

        // When
        $name = $class->name;

        // Then
        $this->assertSame($expectedName, $name);
    }

    /**
     * @return array<string, array{NetworkClass, string}>
     */
    public static function dataProviderForNameProperty(): array
    {
        return [
            'Class A' => [NetworkClass::A, 'A'],
            'Class B' => [NetworkClass::B, 'B'],
            'Class C' => [NetworkClass::C, 'C'],
            'Class D' => [NetworkClass::D, 'D'],
            'Class E' => [NetworkClass::E, 'E'],
        ];
    }
}
