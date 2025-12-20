<?php

namespace App\Http\Resources\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IpAddressResource extends JsonResource
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
            'address' => $this->ip_address, // Map ip_address to address
            'subnet' => $this->subnet,
            'gateway' => $this->gateway,
            'version' => $this->version,
            'status' => $this->status,
            'group_id' => $this->group_id,
            'description' => $this->description,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->toISOString() : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->toISOString() : null,
            'group' => new IpAddressGroupResource($this->whenLoaded('group')),
            'devices' => DeviceResource::collection($this->whenLoaded('devices')),
            'devices_count' => $this->when(isset($this->devices_count), $this->devices_count),
        ];
    }
}
