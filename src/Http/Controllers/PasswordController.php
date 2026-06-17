<?php

namespace Pitbphp\Security\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Pitbphp\Security\Rules\PitbPassword;
use Pitbphp\Security\Services\PasswordHistoryService;
use Pitbphp\Security\Services\SecurityEventLogger;
use Pitbphp\Security\Support\SecurityRoutes;

class PasswordController extends Controller
{
    public function expired(): View
    {
        return view('security::password.expired');
    }

    public function showUpdateForm(): View
    {
        return view('security::password.update');
    }

    public function update(Request $request, PasswordHistoryService $passwordHistory, SecurityEventLogger $logger): RedirectResponse
    {
        $user = Auth::user();

        $rules = [
            'password' => ['required', 'confirmed', new PitbPassword($user)],
        ];

        if (! ($user->must_change_password ?? false)) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validate($rules);

        $hashed = Hash::make($validated['password']);

        $forcedChange = (bool) ($user->must_change_password ?? false);

        $user->password = $hashed;
        $user->password_changed_at = now();
        $user->must_change_password = false;
        $user->save();

        $passwordHistory->record($user, $hashed);

        $logger->auth('auth.password_changed', true, $user, [
            'forced_change' => $forcedChange,
        ]);

        if (config('security.mfa.enabled')
            && method_exists($user, 'needsMfaSetup')
            && $user->needsMfaSetup()) {
            return redirect()
                ->route(SecurityRoutes::name('mfa.setup'))
                ->with('status', 'Password updated. Configure multi-factor authentication to continue.');
        }

        return redirect()->intended('/')
            ->with('status', 'Your password has been updated successfully.');
    }

    public static function expiredRoute(): string
    {
        return SecurityRoutes::name('password.expired');
    }

    public static function updateRoute(): string
    {
        return SecurityRoutes::name('password.update');
    }
}
