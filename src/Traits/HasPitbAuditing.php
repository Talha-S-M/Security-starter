<?php

namespace Pitbphp\Security\Traits;

trait HasPitbAuditing
{
    public function getAuditExclude(): array
    {
        return array_merge(
            ['password', 'remember_token'],
            config('security.auditing.exclude_attributes', [])
        );
    }
}
