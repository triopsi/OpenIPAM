<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class IpAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'version',
        'subnet',
        'gateway',
        'description',
        'status',
        'group_id',
    ];

    protected $casts = [
        'version' => 'integer',
    ];

    /**
     * Get the devices assigned to this IP address.
     */
    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'device_ip_address')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function group()
    {
        return $this->belongsTo(IpAddressGroup::class, 'group_id');
    }

    /**
     * Check if IP address is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Mark IP as assigned.
     */
    public function markAsAssigned(): void
    {
        $this->update(['status' => 'assigned']);
    }

    /**
     * Mark IP as available.
     */
    public function markAsAvailable(): void
    {
        $this->update(['status' => 'available']);
    }
}
