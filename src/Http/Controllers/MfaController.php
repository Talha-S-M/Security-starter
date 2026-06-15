<?php

namespace Pitbphp\Security\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Services\LoginAttemptService;
use Pitbphp\Security\Services\MfaService;
use Pitbphp\Security\Services\SecurityEventLogger;

class MfaController extends Controller
{
    public function show(): \Illuminate\View\View
    {
        return view('security::mfa.verify');
    }

    public function verify(
        Request $request,
        MfaService $mfa,
        LoginAttemptService $loginAttempts,
        SecurityEventLogger $logger
    ): RedirectResponse {
        $request->validate([
            'otp' => ['required', 'string', 'size:'.config('security.mfa.otp_length', 6)],
        ]);

        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($loginAttempts->isLocked($user)) {
            return back()->withErrors(['otp' => 'Your account is temporarily locked. Please try again later.']);
        }

        if (! $mfa->verify($user, $request->input('otp'))) {
            $loginAttempts->recordFailure($user);
            $logger->auth('mfa.failed', false, $user);

            if ($loginAttempts->isLocked($user)) {
                return back()->withErrors(['otp' => 'Too many failed attempts. Your account has been locked.']);
            }

            return back()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        $loginAttempts->clear($user);
        $logger->auth('mfa.verified', true, $user);

        return redirect()->intended(config('security.routes.after_mfa_redirect', '/'));
    }

    public function resend(Request $request, MfaService $mfa): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $mfa->issue($user);
            $request->session()->put('security.mfa_issued', true);
        }

        return back()->with('status', 'A new verification code has been sent.');
    }
}
