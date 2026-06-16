<?php

namespace Pitbphp\Security\Contracts;

interface SmsGatewayInterface
{
    /**
     * @param  array<string, mixed>  $options  identifier, source_type, language, validate_rate_limit
     * @return array{status: bool, message: string}
     */
    public function send(string $phone, string $message, array $options = []): array;
}
