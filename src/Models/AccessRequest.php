<?php

namespace Pitbphp\Security\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const TYPE_USER_UPDATE = 'user_update';

    public const TYPE_ROLE_UPDATE = 'role_update';

    protected $fillable = [
        'type',
        'status',
        'requester_id',
        'target_type',
        'target_id',
        'payload',
        'justification',
        'reviewer_id',
        'review_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
