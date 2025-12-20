<?php

namespace App\Filament\Resources\DeviceResource\Pages;

use App\Filament\Resources\DeviceResource;
use App\Services\IpAssignmentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateDevice extends CreateRecord
{
    protected ?int $primaryIpId = null;

    protected array $additionalIpIds = [];

    protected static string $resource = DeviceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Entferne IP-Adress Felder aus den Device-Daten
        $primaryIpId = $data['primary_ip_address_id'] ?? null;
        $additionalIpIds = $data['additional_ip_addresses'] ?? [];

        unset($data['primary_ip_address_id'], $data['additional_ip_addresses']);

        // Speichere für später
        $this->primaryIpId = $primaryIpId;
        $this->additionalIpIds = $additionalIpIds;

        return $data;
    }

    protected function afterCreate(): void
    {
        $service = new IpAssignmentService;
        $deviceId = $this->record->id;

        // Weise primäre IP zu
        if ($this->primaryIpId) {
            try {
                $service->assignIpToDevice($deviceId, $this->primaryIpId, true);
            } catch (\Exception $e) {
                // Log error but don't fail device creation
                Log::warning('Fehler beim Zuweisen der primären IP: '.$e->getMessage());
            }
        }

        // Weise zusätzliche IPs zu
        foreach ($this->additionalIpIds as $ipId) {
            try {
                $service->assignIpToDevice($deviceId, $ipId, false);
            } catch (\Exception $e) {
                // Log error but don't fail device creation
                Log::warning("Fehler beim Zuweisen der zusätzlichen IP {$ipId}: ".$e->getMessage());
            }
        }
    }
}
