<?php

namespace App\Http\Resources\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiTokenResource extends JsonResource
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
            'abilities' => $this->abilities,
            'tokenable_type' => $this->tokenable_type,
            'tokenable_id' => $this->tokenable_id,
            'expires_at' => $this->expires_at ? Carbon::parse($this->expires_at)->toISOString() : null,
            'last_used_at' => $this->last_used_at ? Carbon::parse($this->last_used_at)->toISOString() : null,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->toISOString() : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->toISOString() : null,
            'is_expired' => $this->expires_at ? Carbon::now()->isAfter($this->expires_at) : false,
            'plain_text_token' => $this->when(
                isset($this->plainTextToken),
                $this->plainTextToken
            ),
        ];
    }
}
