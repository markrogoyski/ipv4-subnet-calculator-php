<?php

declare(strict_types=1);

namespace IPv4\Tests;

use IPv4\IPAddress;
use IPv4\Subnet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ReverseDnsDelegationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * classlessDelegation names the enclosing /24 parent zone and a startLastOctet-endLastOctet child zone
     *
     * @param string $cidr
     * @param string $expectedParent
     * @param string $expectedChild
     */
    #[Test]
    #[DataProvider('dataProviderForDefaultZones')]
    public function classlessDelegationNamesParentAndDefaultChildZones(string $cidr, string $expectedParent, string $expectedChild): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $delegation = $subnet->classlessDelegation();

        // Then
        $this->assertSame(
            [$expectedParent, $expectedChild],
            [$delegation->parentZone(), $delegation->childZone()]
        );
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function dataProviderForDefaultZones(): array
    {
        return [
            '/25 lower half'     => ['192.0.2.0/25', '2.0.192.in-addr.arpa', '0-127.2.0.192.in-addr.arpa'],
            '/25 upper half'     => ['192.0.2.128/25', '2.0.192.in-addr.arpa', '128-255.2.0.192.in-addr.arpa'],
            '/25 host bits'      => ['192.0.2.200/25', '2.0.192.in-addr.arpa', '128-255.2.0.192.in-addr.arpa'],
            '/26 nonzero start'  => ['192.0.2.64/26', '2.0.192.in-addr.arpa', '64-127.2.0.192.in-addr.arpa'],
            '/27'                => ['198.51.100.96/27', '100.51.198.in-addr.arpa', '96-127.100.51.198.in-addr.arpa'],
            '/28'                => ['203.0.113.240/28', '113.0.203.in-addr.arpa', '240-255.113.0.203.in-addr.arpa'],
            '/29 host bits'      => ['192.0.2.13/29', '2.0.192.in-addr.arpa', '8-15.2.0.192.in-addr.arpa'],
            '/30'                => ['10.1.2.4/30', '2.1.10.in-addr.arpa', '4-7.2.1.10.in-addr.arpa'],
            '/31'                => ['192.0.2.6/31', '2.0.192.in-addr.arpa', '6-7.2.0.192.in-addr.arpa'],
            '/31 host bits'      => ['192.0.2.7/31', '2.0.192.in-addr.arpa', '6-7.2.0.192.in-addr.arpa'],
            '/32'                => ['192.0.2.7/32', '2.0.192.in-addr.arpa', '7-7.2.0.192.in-addr.arpa'],
            '/25 lower endpoint' => ['0.0.0.0/25', '0.0.0.in-addr.arpa', '0-127.0.0.0.in-addr.arpa'],
            '/32 lower endpoint' => ['0.0.0.0/32', '0.0.0.in-addr.arpa', '0-0.0.0.0.in-addr.arpa'],
            '/32 upper endpoint' => ['255.255.255.255/32', '255.255.255.in-addr.arpa', '255-255.255.255.255.in-addr.arpa'],
            '/25 upper endpoint' => ['255.255.255.128/25', '255.255.255.in-addr.arpa', '128-255.255.255.255.in-addr.arpa'],
        ];
    }

    /**
     * cnameRecords maps every address's PTR owner in the parent zone to the same label in the child zone
     *
     * @param string                $cidr
     * @param array<string, string> $expectedRecords
     */
    #[Test]
    #[DataProvider('dataProviderForSmallBlockRecords')]
    public function cnameRecordsMapEveryAddressInAscendingOrder(string $cidr, array $expectedRecords): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $records = $subnet->classlessDelegation()->cnameRecords();

        // Then
        $this->assertSame($expectedRecords, $records);
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function dataProviderForSmallBlockRecords(): array
    {
        return [
            '/29 includes network and broadcast' => ['192.0.2.8/29', [
                '8.2.0.192.in-addr.arpa'  => '8.8-15.2.0.192.in-addr.arpa',
                '9.2.0.192.in-addr.arpa'  => '9.8-15.2.0.192.in-addr.arpa',
                '10.2.0.192.in-addr.arpa' => '10.8-15.2.0.192.in-addr.arpa',
                '11.2.0.192.in-addr.arpa' => '11.8-15.2.0.192.in-addr.arpa',
                '12.2.0.192.in-addr.arpa' => '12.8-15.2.0.192.in-addr.arpa',
                '13.2.0.192.in-addr.arpa' => '13.8-15.2.0.192.in-addr.arpa',
                '14.2.0.192.in-addr.arpa' => '14.8-15.2.0.192.in-addr.arpa',
                '15.2.0.192.in-addr.arpa' => '15.8-15.2.0.192.in-addr.arpa',
            ]],
            '/30 host bits'                      => ['10.1.2.6/30', [
                '4.2.1.10.in-addr.arpa' => '4.4-7.2.1.10.in-addr.arpa',
                '5.2.1.10.in-addr.arpa' => '5.4-7.2.1.10.in-addr.arpa',
                '6.2.1.10.in-addr.arpa' => '6.4-7.2.1.10.in-addr.arpa',
                '7.2.1.10.in-addr.arpa' => '7.4-7.2.1.10.in-addr.arpa',
            ]],
            '/31'                                => ['192.0.2.6/31', [
                '6.2.0.192.in-addr.arpa' => '6.6-7.2.0.192.in-addr.arpa',
                '7.2.0.192.in-addr.arpa' => '7.6-7.2.0.192.in-addr.arpa',
            ]],
            '/32'                                => ['192.0.2.7/32', [
                '7.2.0.192.in-addr.arpa' => '7.7-7.2.0.192.in-addr.arpa',
            ]],
            '/32 upper endpoint'                 => ['255.255.255.255/32', [
                '255.255.255.255.in-addr.arpa' => '255.255-255.255.255.255.in-addr.arpa',
            ]],
        ];
    }

    /**
     * cnameRecords for larger blocks: count and both endpoint aliases
     *
     * @param string $cidr
     * @param int    $expectedCount
     * @param string $expectedFirstOwner
     * @param string $expectedFirstTarget
     * @param string $expectedLastOwner
     * @param string $expectedLastTarget
     */
    #[Test]
    #[DataProvider('dataProviderForLargeBlockRecords')]
    public function cnameRecordsIncludeBothEndpointAliases(
        string $cidr,
        int $expectedCount,
        string $expectedFirstOwner,
        string $expectedFirstTarget,
        string $expectedLastOwner,
        string $expectedLastTarget,
    ): void {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $records = $subnet->classlessDelegation()->cnameRecords();

        // Then
        $this->assertSame(
            [$expectedCount, $expectedFirstOwner, $expectedFirstTarget, $expectedLastOwner, $expectedLastTarget],
            [\count($records), \array_key_first($records), \reset($records), \array_key_last($records), \end($records)]
        );
    }

    /**
     * @return array<string, array{string, int, string, string, string, string}>
     */
    public static function dataProviderForLargeBlockRecords(): array
    {
        return [
            '/25 lower half'     => ['192.0.2.0/25', 128, '0.2.0.192.in-addr.arpa', '0.0-127.2.0.192.in-addr.arpa', '127.2.0.192.in-addr.arpa', '127.0-127.2.0.192.in-addr.arpa'],
            '/25 upper endpoint' => ['255.255.255.128/25', 128, '128.255.255.255.in-addr.arpa', '128.128-255.255.255.255.in-addr.arpa', '255.255.255.255.in-addr.arpa', '255.128-255.255.255.255.in-addr.arpa'],
            '/26 nonzero start'  => ['192.0.2.64/26', 64, '64.2.0.192.in-addr.arpa', '64.64-127.2.0.192.in-addr.arpa', '127.2.0.192.in-addr.arpa', '127.64-127.2.0.192.in-addr.arpa'],
            '/26 host bits'      => ['192.0.2.100/26', 64, '64.2.0.192.in-addr.arpa', '64.64-127.2.0.192.in-addr.arpa', '127.2.0.192.in-addr.arpa', '127.64-127.2.0.192.in-addr.arpa'],
        ];
    }

    /**
     * A custom child label replaces the default range label in the child zone and every target
     *
     * @param string $cidr
     * @param string $label
     * @param string $expectedChild
     * @param string $expectedFirstTarget
     */
    #[Test]
    #[DataProvider('dataProviderForCustomLabels')]
    public function classlessDelegationAcceptsCustomChildLabel(string $cidr, string $label, string $expectedChild, string $expectedFirstTarget): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // When
        $delegation = $subnet->classlessDelegation($label);

        // Then
        $this->assertSame(
            [$expectedChild, $expectedFirstTarget],
            [$delegation->childZone(), \array_values($delegation->cnameRecords())[0]]
        );
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function dataProviderForCustomLabels(): array
    {
        $longest = \str_repeat('a', 63);

        return [
            'word'            => ['192.0.2.64/26', 'customer1', 'customer1.2.0.192.in-addr.arpa', '64.customer1.2.0.192.in-addr.arpa'],
            'single letter'   => ['192.0.2.0/25', 'a', 'a.2.0.192.in-addr.arpa', '0.a.2.0.192.in-addr.arpa'],
            'internal hyphen' => ['192.0.2.8/29', 'x-1', 'x-1.2.0.192.in-addr.arpa', '8.x-1.2.0.192.in-addr.arpa'],
            'case preserved'  => ['192.0.2.6/31', 'Net6B', 'Net6B.2.0.192.in-addr.arpa', '6.Net6B.2.0.192.in-addr.arpa'],
            'leading digit'   => ['192.0.2.7/32', '7host', '7host.2.0.192.in-addr.arpa', '7.7host.2.0.192.in-addr.arpa'],
            'range style'     => ['192.0.2.0/26', '0-63', '0-63.2.0.192.in-addr.arpa', '0.0-63.2.0.192.in-addr.arpa'],
            '63 characters'   => ['192.0.2.0/25', $longest, "{$longest}.2.0.192.in-addr.arpa", "0.{$longest}.2.0.192.in-addr.arpa"],
        ];
    }

    /**
     * classlessDelegation rejects labels outside the single nonnumeric LDH label grammar
     *
     * @param string $label
     */
    #[Test]
    #[DataProvider('dataProviderForInvalidLabels')]
    public function classlessDelegationRejectsInvalidChildLabel(string $label): void
    {
        // Given
        $subnet = Subnet::fromCidr('192.0.2.64/26');

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid child zone label');

        // When
        $subnet->classlessDelegation($label);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dataProviderForInvalidLabels(): array
    {
        return [
            'empty'            => [''],
            'numeric'          => ['64'],
            'zero'             => ['0'],
            'leading hyphen'   => ['-a'],
            'trailing hyphen'  => ['a-'],
            'only hyphen'      => ['-'],
            'dotted'           => ['a.b'],
            'domain'           => ['64-127.2.0.192.in-addr.arpa'],
            'trailing dot'     => ['a.'],
            'underscore'       => ['a_b'],
            'slash'            => ['64/26'],
            'space'            => ['a b'],
            'leading space'    => [' a'],
            'trailing newline' => ["a\n"],
            'non-ASCII'        => ['é'],
            '64 characters'    => [\str_repeat('a', 64)],
        ];
    }

    /**
     * classlessDelegation rejects prefixes /0 to /24, which are not classless within a /24 parent
     *
     * @param string $cidr
     */
    #[Test]
    #[DataProvider('dataProviderForNonClasslessPrefixes')]
    public function classlessDelegationRejectsPrefixesUpToTwentyFour(string $cidr): void
    {
        // Given
        $subnet = Subnet::fromCidr($cidr);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Classless delegation requires a prefix between 25 and 32');

        // When
        $subnet->classlessDelegation();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dataProviderForNonClasslessPrefixes(): array
    {
        return [
            '/0'  => ['0.0.0.0/0'],
            '/8'  => ['10.0.0.0/8'],
            '/16' => ['172.16.0.0/16'],
            '/23' => ['192.0.2.0/23'],
            '/24' => ['192.0.2.0/24'],
        ];
    }

    /**
     * The prefix is checked before the label, so a /24 with a bad label reports the prefix
     */
    #[Test]
    public function classlessDelegationValidatesPrefixBeforeLabel(): void
    {
        // Given
        $subnet = Subnet::fromCidr('192.0.2.0/24');

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Classless delegation requires a prefix between 25 and 32');

        // When
        $subnet->classlessDelegation('-');
    }

    /**
     * Every delegation has one alias per address of the block, in ascending order, each owner the
     * address's PTR name and each target the same label below the child zone, which sits directly
     * below the parent /24 zone; results are independent of input host bits and have no trailing dots
     */
    #[Test]
    public function delegationAliasesExactlyCoverBlockBelowChildZone(): void
    {
        // Given
        $cidrs = [
            '0.0.0.0/25', '0.0.0.128/25', '192.0.2.200/25', '255.255.255.128/25', '192.0.2.64/26',
            '192.0.2.191/26', '10.0.0.224/27', '198.51.100.96/27', '203.0.113.240/28', '10.0.255.16/28',
            '192.0.2.8/29', '192.0.2.13/29', '10.0.0.248/29', '255.255.255.255/29', '192.0.2.14/30',
            '0.0.0.3/30', '10.1.2.4/30', '192.0.2.6/31', '192.0.2.7/31', '0.0.0.0/31',
            '255.255.255.254/31', '10.0.1.255/31', '192.0.2.7/32', '0.0.0.0/32', '255.255.255.255/32',
            '10.0.0.255/32', '10.1.0.0/32', '172.16.255.128/25', '100.64.0.0/26', '192.168.1.50/30',
        ];

        foreach ($cidrs as $cidr) {
            $subnet = Subnet::fromCidr($cidr);
            $networkSubnet = Subnet::fromCidr($subnet->networkCidr());
            $parent = Subnet::fromCidr("{$subnet->networkAddress()}/24")->reverseZones()[0];

            // When
            $delegation = $subnet->classlessDelegation();
            $records = $delegation->cnameRecords();

            // Then
            $this->assertSame($parent, $delegation->parentZone(), "{$cidr} parent is not the enclosing /24 zone");
            $this->assertStringEndsWith(".{$parent}", $delegation->childZone(), "{$cidr} child is not below the parent");
            $this->assertSame(
                1,
                \substr_count(\substr($delegation->childZone(), 0, -\strlen(".{$parent}")) . '.', '.'),
                "{$cidr} child is not a single label below the parent"
            );
            $this->assertSame(
                \array_map(static fn (IPAddress $ip): string => $ip->arpaDomain(), \iterator_to_array($subnet->addressRange(), false)),
                \array_keys($records),
                "{$cidr} aliases are not one per address in ascending order"
            );
            $this->assertSame(
                [$delegation->parentZone(), $delegation->childZone(), $records],
                [$networkSubnet->classlessDelegation()->parentZone(), $networkSubnet->classlessDelegation()->childZone(), $networkSubnet->classlessDelegation()->cnameRecords()],
                "{$cidr} depends on host bits"
            );
            foreach ($records as $owner => $target) {
                $lastOctet = \explode('.', $owner)[0];
                $this->assertSame("{$lastOctet}.{$delegation->childZone()}", $target, "{$cidr} alias {$owner} has the wrong target");
                $this->assertStringEndsNotWith('.', $owner, "{$cidr} owner {$owner} has a trailing dot");
            }
            $this->assertStringEndsNotWith('.', $delegation->childZone(), "{$cidr} child zone has a trailing dot");
        }
    }

    /**
     * json_encode of a delegation emits the parent zone, child zone and owner => target CNAME records
     *
     * @param string      $cidr
     * @param string|null $label
     * @param string      $expectedJson
     */
    #[Test]
    #[DataProvider('dataProviderForDelegationJson')]
    public function jsonEncodeOfDelegationEmitsZonesAndCnameRecords(string $cidr, ?string $label, string $expectedJson): void
    {
        // Given
        $delegation = Subnet::fromCidr($cidr)->classlessDelegation($label);

        // When
        $json = \json_encode($delegation, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);

        // Then
        $this->assertSame($expectedJson, $json);
    }

    /**
     * @return array<string, array{string, string|null, string}>
     */
    public static function dataProviderForDelegationJson(): array
    {
        return [
            '/32'                            => ['192.0.2.5/32', null, '{"parent_zone":"2.0.192.in-addr.arpa","child_zone":"5-5.2.0.192.in-addr.arpa","cname_records":{"5.2.0.192.in-addr.arpa":"5.5-5.2.0.192.in-addr.arpa"}}'],
            '/32 custom label'               => ['192.0.2.5/32', 'host5', '{"parent_zone":"2.0.192.in-addr.arpa","child_zone":"host5.2.0.192.in-addr.arpa","cname_records":{"5.2.0.192.in-addr.arpa":"5.host5.2.0.192.in-addr.arpa"}}'],
            '/32 lowest address of the /24'  => ['192.0.2.0/32', null, '{"parent_zone":"2.0.192.in-addr.arpa","child_zone":"0-0.2.0.192.in-addr.arpa","cname_records":{"0.2.0.192.in-addr.arpa":"0.0-0.2.0.192.in-addr.arpa"}}'],
            '/32 highest address of the /24' => ['192.0.2.255/32', null, '{"parent_zone":"2.0.192.in-addr.arpa","child_zone":"255-255.2.0.192.in-addr.arpa","cname_records":{"255.2.0.192.in-addr.arpa":"255.255-255.2.0.192.in-addr.arpa"}}'],
            '/31'                            => ['192.0.2.6/31', null, '{"parent_zone":"2.0.192.in-addr.arpa","child_zone":"6-7.2.0.192.in-addr.arpa","cname_records":{"6.2.0.192.in-addr.arpa":"6.6-7.2.0.192.in-addr.arpa","7.2.0.192.in-addr.arpa":"7.6-7.2.0.192.in-addr.arpa"}}'],
            '/30'                            => ['192.0.2.8/30', null, '{"parent_zone":"2.0.192.in-addr.arpa","child_zone":"8-11.2.0.192.in-addr.arpa","cname_records":{"8.2.0.192.in-addr.arpa":"8.8-11.2.0.192.in-addr.arpa","9.2.0.192.in-addr.arpa":"9.8-11.2.0.192.in-addr.arpa","10.2.0.192.in-addr.arpa":"10.8-11.2.0.192.in-addr.arpa","11.2.0.192.in-addr.arpa":"11.8-11.2.0.192.in-addr.arpa"}}'],
        ];
    }

    /**
     * Decoded delegation JSON for larger blocks holds the zones and exactly the records cnameRecords() returns
     *
     * @param string      $cidr
     * @param string|null $label
     * @param string      $expectedChild
     * @param int         $expectedCount
     */
    #[Test]
    #[DataProvider('dataProviderForLargeDelegationJson')]
    public function jsonEncodeOfLargeDelegationMatchesAccessors(string $cidr, ?string $label, string $expectedChild, int $expectedCount): void
    {
        // Given
        $delegation = Subnet::fromCidr($cidr)->classlessDelegation($label);

        // When
        $decoded = \json_decode(\json_encode($delegation, \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR);

        // Then
        $this->assertSame(
            [['parent_zone' => '2.0.192.in-addr.arpa', 'child_zone' => $expectedChild, 'cname_records' => $delegation->cnameRecords()], $expectedCount],
            [$decoded, \count($delegation->cnameRecords())]
        );
    }

    /**
     * @return array<string, array{string, string|null, string, int}>
     */
    public static function dataProviderForLargeDelegationJson(): array
    {
        return [
            '/25 lower half'    => ['192.0.2.0/25', null, '0-127.2.0.192.in-addr.arpa', 128],
            '/25 upper half'    => ['192.0.2.128/25', null, '128-255.2.0.192.in-addr.arpa', 128],
            '/26 nonzero start' => ['192.0.2.64/26', null, '64-127.2.0.192.in-addr.arpa', 64],
            '/25 custom label'  => ['192.0.2.0/25', 'pool', 'pool.2.0.192.in-addr.arpa', 128],
        ];
    }
}
