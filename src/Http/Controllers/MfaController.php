<?php

namespace Pitbphp\Security\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Services\LoginAttemptService;
use Pitbphp\Security\Services\MfaService;
use Pitbphp\Security\Support\SecurityLog;

class MfaController extends Controller
{
    public function show(): \Illuminate\View\View
    {
        return view('security::mfa.verify');
    }

    public function verify(
        Request $request,
        MfaService $mfa,
        LoginAttemptService $loginAttempts
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
            SecurityLog::auth('mfa.failed', false, $user);

            if ($loginAttempts->isLocked($user)) {
                return back()->withErrors(['otp' => 'Too many failed attempts. Your account has been locked.']);
            }

            return back()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        $loginAttempts->clear($user);
        SecurityLog::auth('mfa.verified', true, $user);

        return redirect()->intended(config('security.routes.after_mfa_redirect', '/'));
    }

    public function resend(Request $request, MfaService $mfa): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $enabledMethods = config('security.mfa.methods', ['email', 'sms']);
            $validated = $request->validate([
                'delivery_method' => ['nullable', 'in:'.implode(',', $enabledMethods)],
            ]);

            $deliveryMethod = $mfa->preferredMethod($user, $validated['delivery_method'] ?? null);
            $request->session()->put('security.mfa_delivery_method', $deliveryMethod);
            $mfa->issue($user, null, 'resend_otp', $deliveryMethod);
            $request->session()->put('security.mfa_issued', true);
        }

        return back()->with('status', 'A new verification code has been sent.');
    }
}
