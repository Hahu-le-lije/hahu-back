<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncCheckpoint extends Model
{
    protected $fillable = [
        'service_name',
        'last_successful_sync',
    ];

    protected $casts = [
        'last_successful_sync' => 'datetime',
    ];
}
