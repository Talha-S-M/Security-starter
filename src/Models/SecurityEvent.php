<?php

namespace Pitbphp\Security\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function user(): BelongsTo
    {
        $model = config('security.user.model');

        return $this->belongsTo($model, 'user_id');
    }
}
