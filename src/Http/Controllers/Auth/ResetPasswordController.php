<?php

namespace Pitbphp\Security\Http\Controllers\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Pitbphp\Security\Rules\PitbPassword;
use Pitbphp\Security\Services\PasswordHistoryService;

class ResetPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('security::auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function store(Request $request, PasswordHistoryService $passwordHistory): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', new PitbPassword],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) use ($passwordHistory) {
                $hashed = Hash::make($password);

                $user->forceFill([
                    'password' => $hashed,
                    'password_changed_at' => now(),
                    'must_change_password' => false,
                ])->save();

                if ($passwordHistory->isEnabledFor($user)) {
                    $passwordHistory->record($user, $hashed);
                }

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
