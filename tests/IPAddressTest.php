<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\AddressType;
use IPv4\IPAddress;
use IPv4\NetworkClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class IPAddressTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    #[DataProvider('dataProviderForValidIpAddresses')]
    public function testConstructorAcceptsValidIpAddress(string $ipAddress): void
    {
        // Given - A valid IP address string

        // When
        $ip = new IPAddress($ipAddress);

        // Then
        $this->assertSame($ipAddress, $ip->asQuads());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dataProviderForValidIpAddresses(): array
    {
        return [
            'min IP' => ['0.0.0.0'],
            'max IP' => ['255.255.255.255'],
            'typical private' => ['192.168.1.1'],
            'loopback' => ['127.0.0.1'],
            'public IP' => ['8.8.8.8'],
            'class A' => ['10.20.30.40'],
            'class B' => ['172.16.50.100'],
            'class C' => ['192.168.100.50'],
            'multicast' => ['224.0.0.1'],
            'broadcast' => ['255.255.255.255'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForInvalidIpAddresses')]
    public function testConstructorThrowsExceptionForInvalidIpAddress(string $invalidIp): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid IPv4 address: '{$invalidIp}'");

        // When
        new IPAddress($invalidIp);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dataProviderForInvalidIpAddresses(): array
    {
        return [
            'octet too high' => ['256.1.1.1'],
            'negative octet' => ['-1.0.0.0'],
            'too many octets' => ['192.168.1.1.1'],
            'too few octets' => ['192.168.1'],
            'empty string' => [''],
            'non-numeric' => ['abc.def.ghi.jkl'],
            'partial invalid' => ['192.168.256.1'],
            'IPv6 address' => ['2001:db8::1'],
            'missing dots' => ['192168011'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForFromInteger')]
    public function testFromIntegerCreatesCorrectIpAddress(int $integer, string $expectedIp): void
    {
        // Given - An integer representation of an IP address

        // When
        $ip = IPAddress::fromInteger($integer);

        // Then
        $this->assertSame($expectedIp, $ip->asQuads());
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function dataProviderForFromInteger(): array
    {
        return [
            'zero' => [0, '0.0.0.0'],
            'max value' => [4_294_967_295, '255.255.255.255'],
            'localhost' => [2_130_706_433, '127.0.0.1'],
            'google DNS' => [134_744_072, '8.8.8.8'],
            'private 192.168.1.1' => [3_232_235_777, '192.168.1.1'],
            'private 10.0.0.1' => [167_772_161, '10.0.0.1'],
            'negative wraps' => [-1, '255.255.255.255'],
            'overflow wraps to zero' => [4_294_967_296, '0.0.0.0'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForFormatting')]
    public function testAsQuadsReturnsCorrectFormat(string $ipAddress): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->asQuads();

        // Then
        $this->assertSame($ipAddress, $result);
    }

    #[Test]
    #[DataProvider('dataProviderForFormatting')]
    public function testAsArrayReturnsCorrectFormat(string $ipAddress, array $expectedArray): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->asArray();

        // Then
        $this->assertSame($expectedArray, $result);
    }

    #[Test]
    #[DataProvider('dataProviderForFormatting')]
    public function testAsHexReturnsCorrectFormat(string $ipAddress, array $expectedArray, string $expectedHex): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->asHex();

        // Then
        $this->assertSame($expectedHex, $result);
    }

    #[Test]
    #[DataProvider('dataProviderForFormatting')]
    public function testAsBinaryReturnsCorrectFormat(
        string $ipAddress,
        array $expectedArray,
        string $expectedHex,
        string $expectedBinary
    ): void {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->asBinary();

        // Then
        $this->assertSame($expectedBinary, $result);
    }

    #[Test]
    #[DataProvider('dataProviderForFormatting')]
    public function testAsIntegerReturnsCorrectFormat(
        string $ipAddress,
        array $expectedArray,
        string $expectedHex,
        string $expectedBinary,
        int $expectedInteger
    ): void {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->asInteger();

        // Then
        $this->assertSame($expectedInteger, $result);
    }

    /**
     * @return array<string, array{string, string[], string, string, int}>
     */
    public static function dataProviderForFormatting(): array
    {
        return [
            '0.0.0.0' => [
                '0.0.0.0',
                ['0', '0', '0', '0'],
                '00000000',
                '00000000000000000000000000000000',
                0,
            ],
            '255.255.255.255' => [
                '255.255.255.255',
                ['255', '255', '255', '255'],
                'FFFFFFFF',
                '11111111111111111111111111111111',
                4_294_967_295,
            ],
            '192.168.1.1' => [
                '192.168.1.1',
                ['192', '168', '1', '1'],
                'C0A80101',
                '11000000101010000000000100000001',
                3_232_235_777,
            ],
            '10.20.30.40' => [
                '10.20.30.40',
                ['10', '20', '30', '40'],
                '0A141E28',
                '00001010000101000001111000101000',
                169_090_600,
            ],
            '127.0.0.1' => [
                '127.0.0.1',
                ['127', '0', '0', '1'],
                '7F000001',
                '01111111000000000000000000000001',
                2_130_706_433,
            ],
        ];
    }

    #[Test]
    public function testToStringReturnsQuads(): void
    {
        // Given
        $ip = new IPAddress('192.168.1.100');

        // When
        $result = (string) $ip;

        // Then
        $this->assertSame('192.168.1.100', $result);
    }

    #[Test]
    #[DataProvider('dataProviderForIsPrivate')]
    public function testIsPrivateIdentifiesPrivateAddresses(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->isPrivate();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIsPrivate(): array
    {
        return [
            // RFC 1918 Private ranges
            '10.0.0.0 start' => ['10.0.0.0', true],
            '10.128.50.100' => ['10.128.50.100', true],
            '10.255.255.255 end' => ['10.255.255.255', true],
            '172.16.0.0 start' => ['172.16.0.0', true],
            '172.20.10.5' => ['172.20.10.5', true],
            '172.31.255.255 end' => ['172.31.255.255', true],
            '192.168.0.0 start' => ['192.168.0.0', true],
            '192.168.1.1' => ['192.168.1.1', true],
            '192.168.255.255 end' => ['192.168.255.255', true],
            // Edge cases - just outside private ranges
            '9.255.255.255' => ['9.255.255.255', false],
            '11.0.0.0' => ['11.0.0.0', false],
            '172.15.255.255' => ['172.15.255.255', false],
            '172.32.0.0' => ['172.32.0.0', false],
            '192.167.255.255' => ['192.167.255.255', false],
            '192.169.0.0' => ['192.169.0.0', false],
            // Public addresses
            '8.8.8.8' => ['8.8.8.8', false],
            '1.1.1.1' => ['1.1.1.1', false],
            '208.67.222.222' => ['208.67.222.222', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIsPublic')]
    public function testIsPublicIdentifiesPublicAddresses(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->isPublic();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIsPublic(): array
    {
        return [
            'google DNS' => ['8.8.8.8', true],
            'cloudflare DNS' => ['1.1.1.1', true],
            'public IP' => ['208.67.222.222', true],
            'private 10.x' => ['10.0.0.1', false],
            'private 172.16.x' => ['172.16.0.1', false],
            'private 192.168.x' => ['192.168.1.1', false],
            'loopback' => ['127.0.0.1', false],
            'link-local' => ['169.254.1.1', false],
            'multicast' => ['224.0.0.1', false],
            'carrier-grade NAT' => ['100.64.0.1', false],
            'documentation' => ['192.0.2.1', false],
            'benchmarking' => ['198.18.0.1', false],
            'reserved' => ['240.0.0.1', false],
            'this network' => ['0.0.0.1', false],
            'IETF protocol' => ['192.0.0.1', false],
            '6to4 relay' => ['192.88.99.1', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIsLoopback')]
    public function testIsLoopbackIdentifiesLoopbackAddresses(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->isLoopback();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIsLoopback(): array
    {
        return [
            '127.0.0.0 start' => ['127.0.0.0', true],
            'localhost' => ['127.0.0.1', true],
            '127.128.0.1' => ['127.128.0.1', true],
            '127.255.255.255 end' => ['127.255.255.255', true],
            '126.255.255.255' => ['126.255.255.255', false],
            '128.0.0.0' => ['128.0.0.0', false],
            'public IP' => ['8.8.8.8', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIsLinkLocal')]
    public function testIsLinkLocalIdentifiesLinkLocalAddresses(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->isLinkLocal();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIsLinkLocal(): array
    {
        return [
            '169.254.0.0 start' => ['169.254.0.0', true],
            '169.254.1.1 APIPA' => ['169.254.1.1', true],
            '169.254.255.255 end' => ['169.254.255.255', true],
            '169.253.255.255' => ['169.253.255.255', false],
            '169.255.0.0' => ['169.255.0.0', false],
            'public IP' => ['8.8.8.8', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIsMulticast')]
    public function testIsMulticastIdentifiesMulticastAddresses(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->isMulticast();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIsMulticast(): array
    {
        return [
            '224.0.0.0 start' => ['224.0.0.0', true],
            '224.0.0.1 all hosts' => ['224.0.0.1', true],
            '239.255.255.255 end' => ['239.255.255.255', true],
            '230.1.2.3' => ['230.1.2.3', true],
            '223.255.255.255' => ['223.255.255.255', false],
            '240.0.0.0' => ['240.0.0.0', false],
            'public IP' => ['8.8.8.8', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIsCarrierGradeNat')]
    public function testIsCarrierGradeNatIdentifiesCgnatAddresses(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->isCarrierGradeNat();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIsCarrierGradeNat(): array
    {
        return [
            '100.64.0.0 start' => ['100.64.0.0', true],
            '100.64.1.1' => ['100.64.1.1', true],
            '100.127.255.255 end' => ['100.127.255.255', true],
            '100.100.100.100' => ['100.100.100.100', true],
            '100.63.255.255' => ['100.63.255.255', false],
            '100.128.0.0' => ['100.128.0.0', false],
            'public IP' => ['8.8.8.8', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIsDocumentation')]
    public function testIsDocumentationIdentifiesDocumentationAddresses(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->isDocumentation();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIsDocumentation(): array
    {
        return [
            // TEST-NET-1: 192.0.2.0/24
            '192.0.2.0 start TEST-NET-1' => ['192.0.2.0', true],
            '192.0.2.100' => ['192.0.2.100', true],
            '192.0.2.255 end TEST-NET-1' => ['192.0.2.255', true],
            // TEST-NET-2: 198.51.100.0/24
            '198.51.100.0 start TEST-NET-2' => ['198.51.100.0', true],
            '198.51.100.50' => ['198.51.100.50', true],
            '198.51.100.255 end TEST-NET-2' => ['198.51.100.255', true],
            // TEST-NET-3: 203.0.113.0/24
            '203.0.113.0 start TEST-NET-3' => ['203.0.113.0', true],
            '203.0.113.200' => ['203.0.113.200', true],
            '203.0.113.255 end TEST-NET-3' => ['203.0.113.255', true],
            // Outside ranges
            '192.0.1.255' => ['192.0.1.255', false],
            '192.0.3.0' => ['192.0.3.0', false],
            '198.51.99.255' => ['198.51.99.255', false],
            '198.51.101.0' => ['198.51.101.0', false],
            '203.0.112.255' => ['203.0.112.255', false],
            '203.0.114.0' => ['203.0.114.0', false],
            'public IP' => ['8.8.8.8', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIsBenchmarking')]
    public function testIsBenchmarkingIdentifiesBenchmarkingAddresses(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->isBenchmarking();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIsBenchmarking(): array
    {
        return [
            '198.18.0.0 start' => ['198.18.0.0', true],
            '198.18.100.100' => ['198.18.100.100', true],
            '198.19.255.255 end' => ['198.19.255.255', true],
            '198.19.128.50' => ['198.19.128.50', true],
            '198.17.255.255' => ['198.17.255.255', false],
            '198.20.0.0' => ['198.20.0.0', false],
            'public IP' => ['8.8.8.8', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIsReserved')]
    public function testIsReservedIdentifiesReservedAddresses(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->isReserved();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIsReserved(): array
    {
        return [
            '240.0.0.0 start' => ['240.0.0.0', true],
            '250.100.50.25' => ['250.100.50.25', true],
            '255.255.255.254' => ['255.255.255.254', true],
            '255.255.255.255 broadcast' => ['255.255.255.255', true],
            '239.255.255.255' => ['239.255.255.255', false],
            'public IP' => ['8.8.8.8', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIsLimitedBroadcast')]
    public function testIsLimitedBroadcastIdentifiesBroadcastAddress(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->isLimitedBroadcast();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIsLimitedBroadcast(): array
    {
        return [
            'broadcast address' => ['255.255.255.255', true],
            'almost broadcast' => ['255.255.255.254', false],
            'zero' => ['0.0.0.0', false],
            'typical IP' => ['192.168.1.1', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIsThisNetwork')]
    public function testIsThisNetworkIdentifiesThisNetworkAddresses(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->isThisNetwork();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIsThisNetwork(): array
    {
        return [
            '0.0.0.0 start' => ['0.0.0.0', true],
            '0.0.0.1' => ['0.0.0.1', true],
            '0.128.50.100' => ['0.128.50.100', true],
            '0.255.255.255 end' => ['0.255.255.255', true],
            '1.0.0.0' => ['1.0.0.0', false],
            'typical IP' => ['192.168.1.1', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIsIetfProtocol')]
    public function testIsIetfProtocolIdentifiesIetfProtocolAddresses(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->isIetfProtocol();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIsIetfProtocol(): array
    {
        return [
            '192.0.0.0 start' => ['192.0.0.0', true],
            '192.0.0.1 DS-Lite' => ['192.0.0.1', true],
            '192.0.0.100' => ['192.0.0.100', true],
            '192.0.0.255 end' => ['192.0.0.255', true],
            '191.255.255.255' => ['191.255.255.255', false],
            '192.0.1.0' => ['192.0.1.0', false],
            '192.0.2.0 TEST-NET-1' => ['192.0.2.0', false],
            'public IP' => ['8.8.8.8', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForIs6to4Relay')]
    public function testIs6to4RelayIdentifies6to4RelayAddresses(string $ipAddress, bool $expectedResult): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->is6to4Relay();

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function dataProviderForIs6to4Relay(): array
    {
        return [
            '192.88.99.0 start' => ['192.88.99.0', true],
            '192.88.99.1 anycast' => ['192.88.99.1', true],
            '192.88.99.100' => ['192.88.99.100', true],
            '192.88.99.255 end' => ['192.88.99.255', true],
            '192.88.98.255' => ['192.88.98.255', false],
            '192.88.100.0' => ['192.88.100.0', false],
            'public IP' => ['8.8.8.8', false],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForAddressType')]
    public function testAddressTypeReturnsCorrectClassification(string $ipAddress, AddressType $expectedType): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->addressType();

        // Then
        $this->assertSame($expectedType, $result);
    }

    /**
     * @return array<string, array{string, AddressType}>
     */
    public static function dataProviderForAddressType(): array
    {
        return [
            'this network' => ['0.0.0.1', AddressType::ThisNetwork],
            'private 10.x' => ['10.0.0.1', AddressType::Private],
            'private 172.16.x' => ['172.16.0.1', AddressType::Private],
            'private 192.168.x' => ['192.168.1.1', AddressType::Private],
            'loopback' => ['127.0.0.1', AddressType::Loopback],
            'link-local' => ['169.254.1.1', AddressType::LinkLocal],
            'carrier-grade NAT' => ['100.64.1.1', AddressType::CarrierGradeNat],
            'documentation' => ['192.0.2.1', AddressType::Documentation],
            'benchmarking' => ['198.18.0.1', AddressType::Benchmarking],
            'multicast' => ['224.0.0.1', AddressType::Multicast],
            'limited broadcast' => ['255.255.255.255', AddressType::LimitedBroadcast],
            'IETF protocol' => ['192.0.0.1', AddressType::IetfProtocol],
            'deprecated 6to4' => ['192.88.99.1', AddressType::Deprecated6to4],
            'reserved' => ['240.0.0.1', AddressType::Reserved],
            'public' => ['8.8.8.8', AddressType::Public],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForNetworkClass')]
    public function testNetworkClassReturnsCorrectClass(string $ipAddress, NetworkClass $expectedClass): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->networkClass();

        // Then
        $this->assertSame($expectedClass, $result);
    }

    /**
     * @return array<string, array{string, NetworkClass}>
     */
    public static function dataProviderForNetworkClass(): array
    {
        return [
            'Class A min' => ['0.0.0.0', NetworkClass::A],
            'Class A mid' => ['10.0.0.1', NetworkClass::A],
            'Class A max' => ['127.255.255.255', NetworkClass::A],
            'Class B min' => ['128.0.0.0', NetworkClass::B],
            'Class B mid' => ['172.16.0.1', NetworkClass::B],
            'Class B max' => ['191.255.255.255', NetworkClass::B],
            'Class C min' => ['192.0.0.0', NetworkClass::C],
            'Class C mid' => ['192.168.1.1', NetworkClass::C],
            'Class C max' => ['223.255.255.255', NetworkClass::C],
            'Class D min' => ['224.0.0.0', NetworkClass::D],
            'Class D mid' => ['230.1.2.3', NetworkClass::D],
            'Class D max' => ['239.255.255.255', NetworkClass::D],
            'Class E min' => ['240.0.0.0', NetworkClass::E],
            'Class E mid' => ['250.1.2.3', NetworkClass::E],
            'Class E max' => ['255.255.255.255', NetworkClass::E],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForArpaDomain')]
    public function testArpaDomainReturnsCorrectReverseDns(string $ipAddress, string $expectedArpa): void
    {
        // Given
        $ip = new IPAddress($ipAddress);

        // When
        $result = $ip->arpaDomain();

        // Then
        $this->assertSame($expectedArpa, $result);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function dataProviderForArpaDomain(): array
    {
        return [
            'localhost' => ['127.0.0.1', '1.0.0.127.in-addr.arpa'],
            'google DNS' => ['8.8.8.8', '8.8.8.8.in-addr.arpa'],
            'private IP' => ['192.168.1.100', '100.1.168.192.in-addr.arpa'],
            'zero' => ['0.0.0.0', '0.0.0.0.in-addr.arpa'],
            'max' => ['255.255.255.255', '255.255.255.255.in-addr.arpa'],
        ];
    }

    #[Test]
    public function testEqualsReturnsTrueForSameIpAddress(): void
    {
        // Given
        $ip1 = new IPAddress('192.168.1.1');
        $ip2 = new IPAddress('192.168.1.1');

        // When
        $result = $ip1->equals($ip2);

        // Then
        $this->assertTrue($result);
    }

    #[Test]
    public function testEqualsReturnsFalseForDifferentIpAddresses(): void
    {
        // Given
        $ip1 = new IPAddress('192.168.1.1');
        $ip2 = new IPAddress('192.168.1.2');

        // When
        $result = $ip1->equals($ip2);

        // Then
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('dataProviderForEquals')]
    public function testEqualsComparesCorrectly(string $ip1Address, string $ip2Address, bool $expectedResult): void
    {
        // Given
        $ip1 = new IPAddress($ip1Address);
        $ip2 = new IPAddress($ip2Address);

        // When
        $result = $ip1->equals($ip2);

        // Then
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function dataProviderForEquals(): array
    {
        return [
            'same IP' => ['192.168.1.1', '192.168.1.1', true],
            'different last octet' => ['192.168.1.1', '192.168.1.2', false],
            'completely different' => ['10.0.0.1', '172.16.0.1', false],
            'zero and max' => ['0.0.0.0', '255.255.255.255', false],
            'both zero' => ['0.0.0.0', '0.0.0.0', true],
            'both max' => ['255.255.255.255', '255.255.255.255', true],
        ];
    }
}
