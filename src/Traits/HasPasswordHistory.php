<?php

namespace Pitbphp\Security\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Pitbphp\Security\Models\PasswordHistory;

trait HasPasswordHistory
{
    public function passwordHistories(): MorphMany
    {
        return $this->morphMany(PasswordHistory::class, 'passwordable');
    }
}
