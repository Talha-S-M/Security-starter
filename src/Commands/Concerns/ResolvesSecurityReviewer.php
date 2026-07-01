<?php

namespace Pitbphp\Security\Commands\Concerns;

use Illuminate\Support\Facades\Auth;

trait ResolvesSecurityReviewer
{
    protected function resolvePerformer()
    {
        if ($userId = $this->option('user')) {
            return $this->findReviewer($userId);
        }

        return Auth::user();
    }

    protected function findReviewer(int|string $userId)
    {
        $model = config('security.user.model');

        return (new $model)->newQuery()->find($userId);
    }
}
