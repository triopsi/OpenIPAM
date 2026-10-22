<?php

namespace Tests\Feature\IPAM;

use App\Models\Device;
use App\Models\IpAddress;
use App\Services\SubnetInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Exercise inventory snapshots and atomic assignments on every CI database engine. */
class SubnetInventoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Render free, assigned, manually reserved and protected addresses in one snapshot.
     *
     * Expect: Counts cover every address exactly once, linked devices override stale status and browsing creates no rows.
     */
    public function test_snapshot_counts_inventory_without_creating_missing_hosts(): void
    {
        $device = Device::factory()->create(['name' => 'Router']);
        $assigned = IpAddress::factory()->create(['ip_address' => '10.0.0.1', 'version' => 4, 'status' => 'available']);
        $assigned->devices()->attach($device);
        IpAddress::factory()->create(['ip_address' => '10.0.0.2', 'version' => 4, 'status' => 'reserved']);
        IpAddress::factory()->create(['ip_address' => '10.0.0.0', 'version' => 4, 'status' => 'available']);
        IpAddress::factory()->create(['ip_address' => '10.0.1.1', 'version' => 4, 'status' => 'assigned']);
        IpAddress::factory()->create(['ip_address' => '2001:db8::1', 'version' => 6, 'status' => 'assigned']);

        $snapshot = app(SubnetInventoryService::class)->snapshot('10.0.0.0/29');
        $this->assertSame(['available' => 4, 'assigned' => 1, 'reserved' => 3], $snapshot['counts']);
        $this->assertCount(8, $snapshot['cells']);
        $this->assertSame(['Router'], $snapshot['cells'][1]['devices']);
        $this->assertSame('assigned', $snapshot['cells'][1]['status']);
        $this->assertSame('network', $snapshot['cells'][0]['kind']);
        $this->assertSame('broadcast', $snapshot['cells'][7]['kind']);
        $this->assertSame(50.0, $snapshot['utilization']);
        $this->assertDatabaseCount('ip_addresses', 5);
    }

    /**
     * Request blocks at and beyond the ends of large subnets.
     *
     * Expect: At most 256 cells are allocated, offsets are clamped and /0 supports its final address block.
     */
    public function test_large_networks_are_bounded_and_paginated(): void
    {
        $service = app(SubnetInventoryService::class);
        $first = $service->snapshot('10.0.0.0/16', -5);
        $this->assertSame(256, $first['blocks']);
        $this->assertSame(1, $first['block']);
        $this->assertCount(256, $first['cells']);
        $next = $service->snapshot('10.0.0.0/16', 2);
        $this->assertSame('10.0.1.0', $next['first']);
        $this->assertSame('10.0.1.255', $next['last']);
        $last = $service->snapshot('0.0.0.0/0', PHP_INT_MAX);
        $this->assertSame(16777216, $last['blocks']);
        $this->assertSame('255.255.255.255', $last['last']);
        $this->assertCount(256, $last['cells']);
        $this->assertTrue($last['cells'][255]['protected']);
    }

    /**
     * Discover networks from mixed legacy subnet formats and invalid inventory metadata.
     *
     * Expect: Equivalent networks are deduplicated and malformed or IPv6 subnet entries are omitted.
     */
    public function test_documented_subnets_are_normalized_and_deduplicated(): void
    {
        foreach (['/24', '255.255.255.0', '10.0.0.0/24', 'invalid'] as $index => $mask) {
            IpAddress::factory()->create(['ip_address' => '10.0.0.'.($index + 1), 'version' => 4, 'subnet' => $mask]);
        }
        IpAddress::factory()->create(['ip_address' => '2001:db8::1', 'version' => 6, 'subnet' => '2001:db8::/64']);
        $this->assertSame(['10.0.0.0/24' => '10.0.0.0/24'], app(SubnetInventoryService::class)->knownSubnets());
    }

    /**
     * Assign an undocumented host and replace the same device's primary IP.
     *
     * Expect: One new canonical inventory row and assignment are created, and only the selected address remains primary.
     */
    public function test_assignment_creates_only_the_selected_host_and_updates_primary_ip(): void
    {
        $device = Device::factory()->create();
        $old = IpAddress::factory()->create(['status' => 'assigned']);
        $device->ipAddresses()->attach($old, ['is_primary' => true]);
        $record = app(SubnetInventoryService::class)->assign('10.0.0.123/24', '10.0.0.42', $device->id, true);

        $this->assertSame('10.0.0.0/24', $record->subnet);
        $this->assertSame('assigned', $record->status);
        $this->assertDatabaseCount('ip_addresses', 2);
        $this->assertDatabaseHas('device_ip_address', ['device_id' => $device->id, 'ip_address_id' => $record->id, 'is_primary' => true]);
        $this->assertDatabaseHas('device_ip_address', ['device_id' => $device->id, 'ip_address_id' => $old->id, 'is_primary' => false]);
    }

    /**
     * Assign an existing available host without rewriting its inventory metadata.
     *
     * Expect: The existing row is reused while its description, gateway, subnet and group remain unchanged.
     */
    public function test_existing_available_host_metadata_is_preserved(): void
    {
        $device = Device::factory()->create();
        $record = IpAddress::factory()->create([
            'ip_address' => '10.0.0.42', 'version' => 4, 'status' => 'available',
            'subnet' => '/25', 'description' => 'Management interface', 'gateway' => '10.0.0.1',
        ]);
        $groupId = $record->group_id;
        $assigned = app(SubnetInventoryService::class)->assign('10.0.0.0/24', '10.0.0.42', $device->id);
        $this->assertSame($record->id, $assigned->id);
        $this->assertSame('/25', $assigned->subnet);
        $this->assertSame('Management interface', $assigned->description);
        $this->assertSame('10.0.0.1', $assigned->gateway);
        $this->assertSame($groupId, $assigned->group_id);
        $this->assertDatabaseCount('ip_addresses', 1);
    }

    /**
     * Count inventory around a non-octet network boundary.
     *
     * Expect: Exact numeric membership includes an in-range host and excludes nearby hosts sharing the coarse query prefix.
     */
    public function test_non_octet_subnet_boundaries_filter_inventory_exactly(): void
    {
        foreach (['10.0.0.127', '10.0.0.129', '10.0.1.1'] as $address) {
            IpAddress::factory()->create(['ip_address' => $address, 'version' => 4, 'status' => 'assigned']);
        }
        $snapshot = app(SubnetInventoryService::class)->snapshot('10.0.0.128/25');
        $this->assertSame(['available' => 125, 'assigned' => 1, 'reserved' => 2], $snapshot['counts']);
        $this->assertSame('10.0.0.128', $snapshot['first']);
        $this->assertSame('10.0.0.255', $snapshot['last']);
    }

    /**
     * Reject manually reserved hosts and stale available hosts already linked to a device.
     *
     * Expect: The original assignments and metadata remain unchanged and no second device can claim the address.
     */
    public function test_occupied_hosts_cannot_be_reassigned(): void
    {
        $owner = Device::factory()->create();
        $other = Device::factory()->create();
        foreach (['reserved', 'available', 'assigned'] as $index => $status) {
            $record = IpAddress::factory()->create(['ip_address' => '10.0.0.'.($index + 1), 'version' => 4, 'status' => $status, 'subnet' => '/25']);
            if ($status !== 'reserved') {
                $record->devices()->attach($owner);
            }
            try {
                app(SubnetInventoryService::class)->assign('10.0.0.0/24', $record->ip_address, $other->id);
                $this->fail('An occupied host was reassigned.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('device_id', $exception->errors());
            }
            $this->assertSame('/25', $record->fresh()->subnet);
        }
        $this->assertSame(0, $other->ipAddresses()->count());
    }

    /**
     * Reject protected endpoints, unrelated hosts and forged non-IPv4 addresses.
     *
     * Expect: Every invalid assignment fails validation without creating inventory rows or device links.
     */
    public function test_invalid_or_protected_hosts_do_not_modify_inventory(): void
    {
        $device = Device::factory()->create();
        foreach (['10.0.0.0', '10.0.0.255', '10.0.1.1', 'not-an-ip', '::1'] as $address) {
            try {
                app(SubnetInventoryService::class)->assign('10.0.0.0/24', $address, $device->id);
                $this->fail('An invalid host was accepted.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertDatabaseCount('ip_addresses', 0);
        $this->assertDatabaseCount('device_ip_address', 0);
    }

    /**
     * Assign hosts in point-to-point and single-host networks.
     *
     * Expect: /31 endpoints and the sole /32 address are usable and counted as assigned after saving.
     */
    public function test_point_to_point_and_single_host_networks_are_assignable(): void
    {
        $device = Device::factory()->create();
        $service = app(SubnetInventoryService::class);
        $service->assign('10.0.0.0/31', '10.0.0.0', $device->id);
        $service->assign('10.0.0.0/31', '10.0.0.1', $device->id);
        $service->assign('255.255.255.255/32', '255.255.255.255', $device->id);
        $this->assertSame(['available' => 0, 'assigned' => 2, 'reserved' => 0], $service->snapshot('10.0.0.0/31')['counts']);
        $this->assertSame(['available' => 0, 'assigned' => 1, 'reserved' => 0], $service->snapshot('255.255.255.255/32')['counts']);
    }
}
