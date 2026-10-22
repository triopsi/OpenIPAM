<?php

namespace App\Services;

use App\Models\Device;
use App\Models\IpAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Create devices consistently from full resource modals and inline selectors. */
class DeviceCreationService
{
    /**
     * Persist validated device attributes and any selected IP assignments atomically.
     *
     * IP-only form fields are removed from the device attributes. Selected address
     * rows are locked in ID order and checked again before creation, so stale
     * selections cannot leave a partially created device or partial assignments.
     * Inline selectors supply no IP fields and therefore create only the device.
     *
     * @param  array<string, mixed>  $data  Validated data from the shared device schema.
     *
     * @throws ValidationException When a selected address no longer exists or is occupied.
     */
    public function create(array $data): Device
    {
        $primaryId = ! empty($data['primary_ip_address_id']) ? (int) $data['primary_ip_address_id'] : null;
        $additionalIds = array_values(array_diff(array_unique(array_map('intval', $data['additional_ip_addresses'] ?? [])), [$primaryId]));
        unset($data['primary_ip_address_id'], $data['additional_ip_addresses']);

        return DB::transaction(function () use ($data, $primaryId, $additionalIds): Device {
            $ids = array_values(array_unique(array_filter([$primaryId, ...$additionalIds])));
            $addresses = IpAddress::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($ids as $id) {
                $address = $addresses->get($id);
                if (! $address || $address->status !== 'available' || $address->devices()->exists()) {
                    $field = $id === $primaryId ? 'primary_ip_address_id' : 'additional_ip_addresses';
                    throw ValidationException::withMessages([$field => __('devices.ip_unavailable')]);
                }
            }

            $device = Device::create($data);
            $assignment = app(IpAssignmentService::class);
            foreach ($ids as $id) {
                $assignment->assignIpToDevice($device->id, $id, $id === $primaryId);
            }

            return $device;
        });
    }
}
