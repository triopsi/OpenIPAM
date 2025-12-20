<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpAddressGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'color',
        'type',
    ];

    /**
     * Get the IP addresses in this group.
     */
    public function ipAddresses(): HasMany
    {
        return $this->hasMany(IpAddress::class, 'group_id');
    }
}
