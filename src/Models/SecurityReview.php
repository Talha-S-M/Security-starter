<?php

namespace Pitbphp\Security\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityReview extends Model
{
    public const TYPE_ACCESS = 'access_review';

    public const TYPE_LOG = 'log_review';

    public const TYPE_INACTIVE = 'inactive_accounts_review';

    protected $fillable = [
        'type',
        'performed_by',
        'notes',
        'metadata',
        'performed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'performed_at' => 'datetime',
    ];
}
