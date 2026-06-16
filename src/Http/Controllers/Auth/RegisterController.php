<?php

namespace Pitbphp\Security\Http\Controllers\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Pitbphp\Security\Http\Requests\SecurityRegisterRequest;
use Pitbphp\Security\Services\PasswordHistoryService;
use Pitbphp\Security\Support\SecurityRoutes;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('security::auth.register', [
            'mfaMethods' => config('security.mfa.methods', ['email', 'sms']),
        ]);
    }

    public function store(SecurityRegisterRequest $request, PasswordHistoryService $passwordHistory): RedirectResponse
    {
        $model = config('security.user.model');
        $hashed = Hash::make($request->input('password'));

        $user = (new $model)->newQuery()->create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $hashed,
            'phone' => $request->input('phone'),
            'mfa_method' => $request->input('mfa_method', config('security.mfa.default_method', 'email')),
            'password_changed_at' => now(),
            'is_active' => true,
        ]);

        if ($passwordHistory->isEnabledFor($user)) {
            $passwordHistory->record($user, $hashed);
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        if (config('security.mfa.enabled')) {
            return redirect()->route(SecurityRoutes::name('mfa.verify'));
        }

        return redirect()->intended(config('security.auth.redirect_after_register', '/'));
    }
}
