<?php

namespace App\Services;

use App\Models\Device;
use App\Models\IpAddress;
use App\Support\Network\Ipv4Subnet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/** Build bounded subnet snapshots from inventory and assign addresses atomically. */
class SubnetInventoryService
{
    public const BLOCK_SIZE = 256;

    /**
     * Discover canonical networks from existing CIDR, prefix and netmask values.
     *
     * @return array<string, string> CIDR values suitable for a searchable select.
     */
    public function knownSubnets(): array
    {
        $networks = [];
        foreach (IpAddress::query()->where('version', 4)->whereNotNull('subnet')->lazyById(500) as $record) {
            try {
                $cidr = Ipv4Subnet::fromInventory($record->ip_address, $record->subnet)->cidr();
                $networks[$cidr] = $cidr;
            } catch (InvalidArgumentException) {
                // Legacy free-text subnet values do not prevent opening the grid.
            }
        }

        ksort($networks, SORT_NATURAL);

        return $networks;
    }

    /**
     * Count occupancy across a whole subnet while materializing at most 256 cells.
     *
     * Missing inventory rows represent undocumented, available addresses. Device
     * relationships take precedence over a stale available status. Counts include
     * protected endpoints, but never double-count them when inventory rows exist.
     *
     * @return array{cidr: string, total: int, counts: array<string, int>, utilization: float, block: int, blocks: int, first: string, last: string, cells: array<int, array<string, mixed>>}
     */
    public function snapshot(string $cidr, int $block = 1): array
    {
        $subnet = Ipv4Subnet::parse($cidr);
        $blocks = (int) ceil($subnet->size() / self::BLOCK_SIZE);
        $block = max(1, min($blocks, $block));
        $first = $subnet->network + ($block - 1) * self::BLOCK_SIZE;
        $last = min($subnet->last(), $first + self::BLOCK_SIZE - 1);
        $protected = $subnet->prefix <= 30 ? 2 : 0;
        $counts = ['available' => $subnet->size() - $protected, 'assigned' => 0, 'reserved' => $protected];
        $visible = [];

        // Whole-octet prefix filtering is portable across SQLite, MariaDB and PostgreSQL.
        // Exact numeric membership below also handles prefixes such as /20 and /25.
        $octets = intdiv($subnet->prefix, 8);
        $prefix = implode('.', array_slice(explode('.', long2ip($subnet->network)), 0, $octets));
        $query = IpAddress::query()->where('version', 4)->with('devices:id,name');
        if ($octets > 0) {
            $query->where('ip_address', 'like', $prefix.($octets < 4 ? '.' : '').'%');
        }

        foreach ($query->lazyById(500) as $record) {
            try {
                $address = Ipv4Subnet::addressToInteger($record->ip_address);
            } catch (InvalidArgumentException) {
                continue;
            }
            if (! $subnet->contains($address)) {
                continue;
            }

            $counts[$subnet->isProtected($address) ? 'reserved' : 'available']--;
            $counts[$this->status($subnet, $address, $record)]++;
            if ($address >= $first && $address <= $last) {
                $visible[$address] = $record;
            }
        }

        $cells = [];
        for ($address = $first; $address <= $last; $address++) {
            $record = $visible[$address] ?? null;
            $cells[] = [
                'address' => long2ip($address),
                'suffix' => '.'.($address % 256),
                'status' => $this->status($subnet, $address, $record),
                'protected' => $subnet->isProtected($address),
                'kind' => $subnet->isProtected($address) ? ($address === $subnet->network ? 'network' : 'broadcast') : null,
                'record_id' => $record?->id,
                'devices' => $record?->devices->pluck('name')->all() ?? [],
            ];
        }

        return [
            'cidr' => $subnet->cidr(), 'total' => $subnet->size(), 'counts' => $counts,
            'utilization' => round(100 * ($counts['assigned'] + $counts['reserved']) / $subnet->size(), 1),
            'block' => $block, 'blocks' => $blocks, 'first' => long2ip($first), 'last' => long2ip($last), 'cells' => $cells,
        ];
    }

    /**
     * Assign a free host, creating its inventory row only when the action succeeds.
     *
     * Device and IP row locks serialize grid assignments and primary-IP changes.
     * Rechecking both status and device links prevents a stale browser tab from
     * claiming an occupied address. Any failure rolls back row creation as well.
     *
     * @throws ValidationException When the address is outside the subnet, protected or occupied.
     */
    public function assign(string $cidr, string $address, int $deviceId, bool $primary = false): IpAddress
    {
        try {
            $subnet = Ipv4Subnet::parse($cidr);
            $numeric = Ipv4Subnet::addressToInteger($address);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['device_id' => __('subnets.validation.cidr')]);
        }
        if (! $subnet->contains($numeric) || $subnet->isProtected($numeric)) {
            throw ValidationException::withMessages(['device_id' => __('subnets.validation.host')]);
        }

        return DB::transaction(function () use ($subnet, $address, $deviceId, $primary): IpAddress {
            Device::query()->lockForUpdate()->findOrFail($deviceId);
            $record = IpAddress::query()->firstOrCreate(
                ['ip_address' => $address, 'version' => 4],
                ['subnet' => $subnet->cidr(), 'status' => 'available'],
            );
            $record = IpAddress::query()->lockForUpdate()->findOrFail($record->id);
            if ($record->status !== 'available' || $record->devices()->exists()) {
                throw ValidationException::withMessages(['device_id' => __('subnets.validation.occupied')]);
            }

            app(IpAssignmentService::class)->assignIpToDevice($deviceId, $record->id, $primary);

            return $record->refresh();
        });
    }

    /** Resolve an address's displayed status conservatively when inventory is inconsistent. */
    private function status(Ipv4Subnet $subnet, int $address, ?IpAddress $record): string
    {
        if ($record && ($record->status === 'assigned' || $record->devices->isNotEmpty())) {
            return 'assigned';
        }
        if ($subnet->isProtected($address) || ($record && $record->status !== 'available')) {
            return 'reserved';
        }

        return 'available';
    }
}
