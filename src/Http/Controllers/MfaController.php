<?php

namespace Pitbphp\Security\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Services\MfaService;

class MfaController extends Controller
{
    public function show(): \Illuminate\View\View
    {
        return view('security::mfa.verify');
    }

    public function verify(Request $request, MfaService $mfa): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:'.config('security.mfa.otp_length', 6)],
        ]);

        $user = Auth::user();

        if (! $user || ! $mfa->verify($user, $request->input('otp'))) {
            return back()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        $mfa->markVerified($user);

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
