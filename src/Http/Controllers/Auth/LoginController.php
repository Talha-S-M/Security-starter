<?php

namespace Pitbphp\Security\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Pitbphp\Security\Http\Requests\SecurityLoginRequest;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('security::auth.login');
    }

    public function login(SecurityLoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(config('security.auth.redirect_after_login'));
    }
}
