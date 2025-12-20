<?php

namespace App\Services;

use App\Models\IpAddress;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class IpAssignmentService
{
    /**
     * Find the next available IP address based on system settings.
     *
     * This method searches for available IP addresses, first checking in a preferred
     * or default group, then falling back to a global search if nothing is found.
     *
     * @param  int|null  $preferredGroupId  The preferred group ID to search in first
     * @return \App\Models\IpAddress|null The next available IP address or null
     */
    public function getNextAvailableIp(?int $preferredGroupId = null): ?IpAddress
    {
        // Prüfe ob automatische Zuweisung aktiviert ist
        if (! Setting::get('auto_assign_primary_ip', true)) {
            return null;
        }

        // Bestimme die Gruppe für die Suche
        $groupId = $preferredGroupId ?? Setting::get('default_ip_group');

        // Suche zuerst in der angegebenen/Standard-Gruppe
        if ($groupId) {
            $ip = $this->findNextAvailableInGroup($groupId);
            if ($ip) {
                return $ip;
            }
        }

        // Falls in der Gruppe nichts gefunden wurde, suche global
        return $this->findNextAvailableGlobally();
    }

    /**
     * Find the next available IP address within a specific group.
     *
     * @param  int  $groupId  The group ID to search within
     * @return \App\Models\IpAddress|null The next available IP or null
     */
    protected function findNextAvailableInGroup(int $groupId): ?IpAddress
    {
        return IpAddress::where('group_id', $groupId)
            ->where('status', 'available')
            ->whereDoesntHave('devices')
            ->orderBy('ip_address')
            ->first();
    }

    /**
     * Find the next available IP address globally across all groups.
     *
     * @return \App\Models\IpAddress|null The next available IP or null
     */
    protected function findNextAvailableGlobally(): ?IpAddress
    {
        return IpAddress::where('status', 'available')
            ->whereDoesntHave('devices')
            ->orderBy('ip_address')
            ->first();
    }

    /**
     * Assign an IP address to a device.
     *
     * This method creates a relationship between a device and an IP address,
     * optionally setting it as the primary IP. It also updates the IP status
     * and handles primary IP conflicts.
     *
     * @param  int  $deviceId  The device ID to assign the IP to
     * @param  int  $ipAddressId  The IP address ID to assign
     * @param  bool  $isPrimary  Whether this should be the primary IP
     *
     * @throws \Exception When the IP address is not available
     */
    public function assignIpToDevice(int $deviceId, int $ipAddressId, bool $isPrimary = false): void
    {
        $ipAddress = IpAddress::findOrFail($ipAddressId);

        // Prüfe ob IP verfügbar ist
        if ($ipAddress->status !== 'available') {
            throw new \Exception('IP-Adresse ist nicht verfügbar');
        }

        // Wenn primäre IP, setze andere als nicht-primär
        if ($isPrimary) {
            DB::table('device_ip_address')
                ->where('device_id', $deviceId)
                ->update(['is_primary' => false]);
        }

        // Erstelle die Verbindung
        DB::table('device_ip_address')->updateOrInsert(
            [
                'device_id' => $deviceId,
                'ip_address_id' => $ipAddressId,
            ],
            [
                'is_primary' => $isPrimary,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Aktualisiere IP-Status
        $ipAddress->update(['status' => 'assigned']);
    }

    /**
     * Remove IP address assignment from a device.
     *
     * This method removes the relationship between a device and an IP address,
     * and updates the IP status to available if no other devices are using it.
     *
     * @param  int  $deviceId  The device ID to remove the IP from
     * @param  int  $ipAddressId  The IP address ID to unassign
     */
    public function unassignIpFromDevice(int $deviceId, int $ipAddressId): void
    {
        // Entferne die Verbindung
        DB::table('device_ip_address')
            ->where('device_id', $deviceId)
            ->where('ip_address_id', $ipAddressId)
            ->delete();

        // Prüfe ob IP noch anderen Geräten zugeordnet ist
        $stillInUse = DB::table('device_ip_address')
            ->where('ip_address_id', $ipAddressId)
            ->exists();

        // Wenn nicht mehr in Verwendung, setze Status auf verfügbar
        if (! $stillInUse) {
            IpAddress::where('id', $ipAddressId)
                ->update(['status' => 'available']);
        }
    }

    /**
     * Prepare IP address options for device forms.
     *
     * This method returns available IP addresses grouped by their group names,
     * formatted for use in select dropdowns or form options.
     *
     * @return array<string, array<int, string>> Grouped IP options with group names as keys
     */
    public function prepareIpOptionsForDevice(): array
    {
        $options = [];

        // Gruppiere verfügbare IPs nach Gruppen
        $availableIps = IpAddress::with('group')
            ->where('status', 'available')
            ->whereDoesntHave('devices')
            ->orderBy('ip_address')
            ->get();

        foreach ($availableIps as $ip) {
            $groupName = $ip->group?->name ?? 'Ohne Gruppe';
            $options[$groupName][$ip->id] = $ip->ip_address.($ip->description ? " ({$ip->description})" : '');
        }

        return $options;
    }
}
