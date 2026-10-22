<?php

namespace Tests\Unit\Support\Network;

use App\Support\Network\Ipv4Subnet;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** Verify CIDR arithmetic independently of the database and Filament. */
class Ipv4SubnetTest extends TestCase
{
    /**
     * Normalize host bits and calculate the bounds of a conventional network.
     *
     * Expect: A host within a /24 resolves to its canonical network, size and protected endpoints.
     */
    public function test_host_bits_are_normalized_to_the_network(): void
    {
        $subnet = Ipv4Subnet::parse('192.168.1.173/24');
        $this->assertSame('192.168.1.0/24', $subnet->cidr());
        $this->assertSame(256, $subnet->size());
        $this->assertSame('192.168.1.255', long2ip($subnet->last()));
        $this->assertTrue($subnet->isProtected($subnet->network));
        $this->assertTrue($subnet->isProtected($subnet->last()));
        $this->assertFalse($subnet->isProtected($subnet->network + 1));
        $this->assertFalse($subnet->contains(Ipv4Subnet::addressToInteger('192.168.2.1')));
    }

    /**
     * Interpret the subnet representations accepted by the existing inventory.
     *
     * Expect: CIDR, prefix-only and contiguous netmask values identify the same canonical network.
     */
    public function test_legacy_inventory_masks_are_supported(): void
    {
        foreach (['192.168.1.0/24', '/24', '24', '255.255.255.0'] as $mask) {
            $this->assertSame('192.168.1.0/24', Ipv4Subnet::fromInventory('192.168.1.10', $mask)->cidr());
        }
    }

    /**
     * Reject malformed CIDR, unsupported address families and noncontiguous netmasks.
     *
     * Expect: Invalid network input raises a domain error instead of producing an address range.
     */
    public function test_invalid_networks_and_netmasks_are_rejected(): void
    {
        foreach (['192.168.1.0', '192.168.1.0/33', '192.168.1.0/-1', '300.1.1.1/24', '::1/128', '10.0.0.0/24oops', '10.0.0.0/'] as $input) {
            try {
                Ipv4Subnet::parse($input);
                $this->fail('Invalid CIDR was accepted: '.$input);
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->expectException(InvalidArgumentException::class);
        Ipv4Subnet::fromInventory('192.168.1.10', '255.0.255.0');
    }

    /**
     * Calculate extreme IPv4 ranges and the point-to-point endpoint exceptions.
     *
     * Expect: /0 fits in integer arithmetic, /31 has two usable hosts and /32 has one usable host.
     */
    public function test_large_and_point_to_point_networks_have_correct_bounds(): void
    {
        $all = Ipv4Subnet::parse('255.255.255.255/0');
        $this->assertSame('0.0.0.0/0', $all->cidr());
        $this->assertSame(4294967296, $all->size());
        $this->assertSame('255.255.255.255', long2ip($all->last()));

        foreach (['10.0.0.10/31' => 2, '255.255.255.255/32' => 1] as $cidr => $size) {
            $subnet = Ipv4Subnet::parse($cidr);
            $this->assertSame($size, $subnet->size());
            $this->assertFalse($subnet->isProtected($subnet->network));
            $this->assertFalse($subnet->isProtected($subnet->last()));
        }
    }
}
