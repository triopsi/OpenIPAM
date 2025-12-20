<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hostname',
        'mac_address',
        'description',
        'url',
        'type',
        'location',
        'status',
        'last_seen',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'last_seen' => 'datetime',
        ];
    }

    /**
     * Get the IP addresses assigned to this device.
     */
    public function ipAddresses(): BelongsToMany
    {
        return $this->belongsToMany(IpAddress::class, 'device_ip_address')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the primary IP address for this device.
     */
    public function primaryIpAddress()
    {
        return $this->ipAddresses()->wherePivot('is_primary', true)->first();
    }
}
