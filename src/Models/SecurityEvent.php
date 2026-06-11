<?php

namespace Pitbphp\Security\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'success',
        'ip_address',
        'user_agent',
        'properties',
    ];

    protected $casts = [
        'success' => 'boolean',
        'properties' => 'array',
    ];
}
