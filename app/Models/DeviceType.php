<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Store reusable custom names separately from the existing device type values. */
class DeviceType extends Model
{
    protected $fillable = ['value', 'name'];
}
