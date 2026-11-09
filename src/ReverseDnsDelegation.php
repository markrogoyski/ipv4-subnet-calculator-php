<?php

declare(strict_types=1);

namespace IPv4;

/**
 * Immutable RFC 2317 classless reverse-DNS delegation data for a /25–/32 block.
 *
 * Names the enclosing /24 parent zone, the child zone delegated for the block, and
 * the CNAME records the parent zone publishes so each address's PTR owner name
 * aliases the same label in the child zone. All names omit trailing dots.
 *
 * This is naming data only: NS, SOA and PTR records, parent-zone cooperation and
 * DNS publishing remain the caller's responsibility.
 *
 * @link https://www.rfc-editor.org/rfc/rfc2317
 */
final class ReverseDnsDelegation implements \JsonSerializable
{
    private readonly string $parentZone;
    private readonly string $childZone;

    /** @var array<string, string> */
    private readonly array $cnameRecords;

    /**
     * Create classless delegation data for a subnet.
     *
     * Usually obtained via Subnet::classlessDelegation(). Input host bits are ignored.
     *
     * @param Subnet      $subnet     Subnet with a prefix from 25 to 32
     * @param string|null $childLabel Child zone label; defaults to "startLastOctet-endLastOctet"
     *
     * @throws \InvalidArgumentException If the prefix is not 25–32 or the label is invalid
     */
    public function __construct(Subnet $subnet, ?string $childLabel = null)
    {
        if ($subnet->networkSize() < 25) {
            throw new \InvalidArgumentException(
                "Classless delegation requires a prefix between 25 and 32, got {$subnet->networkSize()}"
            );
        }

        $start = $subnet->networkAddress()->asInteger();
        $end = $subnet->broadcastAddress()->asInteger();

        if ($childLabel === null) {
            $childLabel = ($start & 0xFF) . '-' . ($end & 0xFF);
        } elseif (!self::isValidLabel($childLabel)) {
            throw new \InvalidArgumentException(
                "Invalid child zone label '{$childLabel}': expected 1-63 letters, digits or hyphens, "
                . 'not all digits, beginning and ending with a letter or digit'
            );
        }

        $this->parentZone = (new Subnet($subnet->networkAddress(), 24))->reverseZones()[0];
        $this->childZone = "{$childLabel}.{$this->parentZone}";

        $records = [];
        for ($address = $start; $address <= $end; $address++) {
            $lastOctet = $address & 0xFF;
            $records["{$lastOctet}.{$this->parentZone}"] = "{$lastOctet}.{$this->childZone}";
        }
        $this->cnameRecords = $records;
    }

    /**
     * Get the parent zone: the enclosing /24's reverse zone, which publishes the CNAMEs.
     *
     * @return string e.g., "2.0.192.in-addr.arpa" for 192.0.2.64/26
     */
    public function parentZone(): string
    {
        return $this->parentZone;
    }

    /**
     * Get the child zone origin delegated for the block.
     *
     * @return string e.g., "64-127.2.0.192.in-addr.arpa" for 192.0.2.64/26
     */
    public function childZone(): string
    {
        return $this->childZone;
    }

    /**
     * Get the parent-zone CNAME records, one per address including network and broadcast.
     *
     * Ordered by ascending address. Owners are each address's PTR name in the parent
     * zone; targets are the same last-octet label in the child zone.
     *
     * @return array<string, string> owner => target, e.g., "65.2.0.192.in-addr.arpa" => "65.64-127.2.0.192.in-addr.arpa"
     */
    public function cnameRecords(): array
    {
        return $this->cnameRecords;
    }

    /**
     * JSON serialization: the parent zone, child zone and parent-zone CNAME records.
     *
     * The CNAME records are owner => target in ascending address order, as returned by cnameRecords().
     *
     * @return array{parent_zone: string, child_zone: string, cname_records: array<string, string>} e.g., ['parent_zone' => '2.0.192.in-addr.arpa', 'child_zone' => '6-7.2.0.192.in-addr.arpa', 'cname_records' => ['6.2.0.192.in-addr.arpa' => '6.6-7.2.0.192.in-addr.arpa', ...]]
     */
    public function jsonSerialize(): array
    {
        return [
            'parent_zone'   => $this->parentZone,
            'child_zone'    => $this->childZone,
            'cname_records' => $this->cnameRecords,
        ];
    }

    /**
     * Check a custom child label: one nonnumeric LDH label of 1–63 ASCII characters.
     *
     * Numeric-only labels are rejected because they could collide with PTR owner names.
     *
     * @param string $label
     *
     * @return bool
     */
    private static function isValidLabel(string $label): bool
    {
        return \preg_match('/\A[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\z/', $label) === 1
            && !\ctype_digit($label);
    }
}
