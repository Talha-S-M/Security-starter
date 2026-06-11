<?php

namespace Pitbphp\Security\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PasswordHistory extends Model
{
    protected $fillable = [
        'passwordable_type',
        'passwordable_id',
        'password',
    ];

    public function passwordable(): MorphTo
    {
        return $this->morphTo();
    }
}
