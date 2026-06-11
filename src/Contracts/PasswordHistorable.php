<?php

namespace Pitbphp\Security\Contracts;

interface PasswordHistorable
{
    public function getPasswordHistoryIdentifier(): int|string;

    public function getPasswordHistoryType(): string;
}
