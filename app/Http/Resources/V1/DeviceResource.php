<?php

namespace App\Http\Resources\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'location' => $this->location,
            'department' => $this->department,
            'serial_number' => $this->serial_number,
            'asset_tag' => $this->asset_tag,
            'status' => $this->status,
            'purchase_date' => $this->purchase_date ? Carbon::parse($this->purchase_date)->toDateString() : null,
            'warranty_until' => $this->warranty_until ? Carbon::parse($this->warranty_until)->toDateString() : null,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->toISOString() : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->toISOString() : null,
            'ip_addresses' => IpAddressResource::collection($this->whenLoaded('ipAddresses')),
            'ip_addresses_count' => $this->when(isset($this->ip_addresses_count), $this->ip_addresses_count),
        ];
    }
}
