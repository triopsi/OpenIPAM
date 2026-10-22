<?php

namespace App\Support\Network;

use InvalidArgumentException;

/**
 * Describe an IPv4 network using integer arithmetic without expanding its hosts.
 *
 * Address counts fit in a 64-bit integer even for /0. The grid requests only one
 * bounded block at a time, so large networks do not allocate billions of cells.
 */
final readonly class Ipv4Subnet
{
    /** Store the canonical network address and validated CIDR prefix. */
    private function __construct(public int $network, public int $prefix) {}

    /**
     * Parse IPv4 CIDR notation and normalize host bits to the network address.
     *
     * @throws InvalidArgumentException When the address or prefix is invalid.
     */
    public static function parse(string $cidr): self
    {
        if (! preg_match('/^([^\/]+)\/(\d{1,2})$/D', trim($cidr), $matches)
            || ! filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            || (int) $matches[2] > 32) {
            throw new InvalidArgumentException('Expected an IPv4 network in CIDR notation.');
        }

        $prefix = (int) $matches[2];
        $size = 2 ** (32 - $prefix);

        return new self(intdiv(self::addressToInteger($matches[1]), $size) * $size, $prefix);
    }

    /**
     * Interpret existing inventory subnet values: CIDR, /prefix or netmask.
     *
     * A dotted netmask must contain contiguous leading one bits. Invalid legacy
     * values can be skipped by callers without inventing a network for them.
     *
     * @throws InvalidArgumentException When the stored subnet cannot be interpreted.
     */
    public static function fromInventory(string $address, string $subnet): self
    {
        $subnet = trim($subnet);
        if (str_contains($subnet, '/') && ! str_starts_with($subnet, '/')) {
            return self::parse($subnet);
        }

        if (preg_match('/^\/?\d{1,2}$/D', $subnet)) {
            return self::parse($address.'/'.ltrim($subnet, '/'));
        }

        $bits = str_pad(decbin(self::addressToInteger($subnet)), 32, '0', STR_PAD_LEFT);
        if (! preg_match('/^1*0*$/D', $bits)) {
            throw new InvalidArgumentException('Expected a contiguous IPv4 netmask.');
        }

        return self::parse($address.'/'.substr_count($bits, '1'));
    }

    /**
     * Convert a validated IPv4 address to its unsigned 32-bit numeric value.
     *
     * @throws InvalidArgumentException When the value is not an IPv4 address.
     */
    public static function addressToInteger(string $address): int
    {
        if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new InvalidArgumentException('Expected a valid IPv4 address.');
        }

        return (int) sprintf('%u', ip2long($address));
    }

    /** Return canonical CIDR notation, including for noncanonical user input. */
    public function cidr(): string
    {
        return long2ip($this->network).'/'.$this->prefix;
    }

    /** Count all addresses in the network, including any protected endpoints. */
    public function size(): int
    {
        return 2 ** (32 - $this->prefix);
    }

    /** Return the final address, without implying a broadcast exists on /31 or /32. */
    public function last(): int
    {
        return $this->network + $this->size() - 1;
    }

    /** Check numeric membership without enumerating the network. */
    public function contains(int $address): bool
    {
        return $address >= $this->network && $address <= $this->last();
    }

    /**
     * Identify network/broadcast endpoints on conventional subnets.
     *
     * Point-to-point /31 networks and single-host /32 networks have no protected
     * endpoints; both /31 addresses and the sole /32 address remain usable.
     */
    public function isProtected(int $address): bool
    {
        return $this->prefix <= 30 && in_array($address, [$this->network, $this->last()], true);
    }
}
