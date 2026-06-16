<?php

namespace Pitbphp\Security\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Pitbphp\Security\Services\LoginAttemptService;
use Pitbphp\Security\Services\MfaService;
use Pitbphp\Security\Services\PasswordHistoryService;
use Pitbphp\Security\Services\SecurityEventLogger;
use Pitbphp\Security\Support\SecurityRequest;

class LogAuthenticationEvents
{
    public function __construct(
        protected SecurityEventLogger $logger,
        protected LoginAttemptService $loginAttempts,
        protected PasswordHistoryService $passwordHistory,
        protected MfaService $mfa
    ) {}

    public function handleLogin(Login $event): void
    {
        $this->loginAttempts->clear($event->user);

        $this->updateLastLogin($event->user);

        $this->logger->auth('auth.login', true, $event->user);

        if (config('security.mfa.enabled')) {
            $tokenId = SecurityRequest::currentTokenId(request());
            $this->mfa->clearVerification($event->user, $tokenId);

            if (! SecurityRequest::isApi(request())) {
                request()->session()->forget(['security.mfa_verified', 'security.mfa_issued']);
            }
        }
    }

    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        $this->logger->auth('auth.failed', false, $event->user, [
            'email' => $email,
        ]);

        if ($event->user) {
            $this->loginAttempts->recordFailure($event->user, $email, request()->ip());
            return;
        }

        $this->loginAttempts->recordNetworkFailure($email, request()->ip());
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            $this->logger->auth('auth.logout', true, $event->user);
        }

        request()->session()->forget(['security.mfa_verified', 'security.mfa_issued', 'security.last_activity']);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $user = $event->user;
        $table = config('security.user.table', 'users');

        $updates = [
            'password_changed_at' => now(),
            'must_change_password' => false,
        ];

        if ($this->hasColumn('failed_login_attempts')) {
            $updates['failed_login_attempts'] = 0;
        }

        if ($this->hasColumn('locked_until')) {
            $updates['locked_until'] = null;
        }

        DB::table($table)->where('id', $user->getAuthIdentifier())->update($updates);

        if (isset($user->password) && $this->passwordHistory->isEnabledFor($user)) {
            $this->passwordHistory->record($user, $user->password);
        }

        $this->logger->auth('auth.password_reset', true, $user);
    }

    protected function updateLastLogin($user): void
    {
        if (! $this->hasColumn('last_login_at')) {
            return;
        }

        DB::table(config('security.user.table', 'users'))
            ->where('id', $user->getAuthIdentifier())
            ->update(['last_login_at' => now()]);
    }

    protected function hasColumn(string $column): bool
    {
        return in_array(
            $column,
            DB::getSchemaBuilder()->getColumnListing(config('security.user.table', 'users')),
            true
        );
    }
}
