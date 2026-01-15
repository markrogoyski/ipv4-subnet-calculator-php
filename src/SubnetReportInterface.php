<?php

declare(strict_types=1);

namespace IPv4;

/**
 * Contract for subnet report generation implementations.
 *
 * Defines the interface for creating formatted reports from SubnetCalculator instances.
 * Implementations must support four output formats: array, JSON, printable string,
 * and direct STDOUT output.
 *
 * Reports include comprehensive network data: IP addresses, subnet masks, wildcard masks,
 * network/host portions, address ranges, and metadata in multiple numeric formats
 * (dotted quads, hex, binary, integer).
 *
 * Implement this interface to create custom report formats or integrate with external
 * reporting systems. Inject via SubnetCalculator constructor for customized output.
 */
interface SubnetReportInterface
{
    /**
     * Get subnet calculations as an associated array
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexadecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @param SubnetCalculator $sub
     *
     * @return mixed[] of subnet calculations
     */
    public function createArrayReport(SubnetCalculator $sub): array;

    /**
     * Get subnet calculations as JSON string
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexadecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @param SubnetCalculator $sub
     *
     * @return string|false JSON string of subnet calculations
     */
    public function createJsonReport(SubnetCalculator $sub);

    /**
     * Print a report of subnet calculations.
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexadecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @param SubnetCalculator $sub
     */
    public function printReport(SubnetCalculator $sub): void;

    /**
     * Print a report of subnet calculations
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexadecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @param SubnetCalculator $sub
     *
     * @return string Subnet Calculator report
     */
    public function createPrintableReport(SubnetCalculator $sub): string;
}
