<?php

namespace Pitbphp\Security\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Pitbphp\Security\Services\MfaService;
use Pitbphp\Security\Services\SecurityEventLogger;
use Pitbphp\Security\Support\SecurityRoutes;

class MfaSetupController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! method_exists($user, 'needsMfaSetup') || ! $user->needsMfaSetup()) {
            return redirect()->intended(config('security.routes.after_mfa_redirect', '/'));
        }

        return view('security::mfa.setup', [
            'step' => $request->session()->get('mfa_setup_step', 'configure'),
            'methods' => config('security.mfa.methods', ['email', 'sms']),
        ]);
    }

    public function store(Request $request, MfaService $mfa, SecurityEventLogger $logger): RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($request->session()->get('mfa_setup_step') === 'verify') {
            return $this->verifySetup($request, $mfa, $logger, $user);
        }

        $methods = config('security.mfa.methods', ['email', 'sms']);

        $validated = $request->validate([
            'mfa_method' => ['required', 'in:'.implode(',', $methods)],
            'mfa_email' => ['nullable', 'required_if:mfa_method,email', 'email', 'max:255'],
            'phone' => ['nullable', 'required_if:mfa_method,sms', 'string', 'max:30'],
        ]);

        $user->mfa_method = $validated['mfa_method'];
        $user->mfa_email = $validated['mfa_method'] === 'email'
            ? ($validated['mfa_email'] ?? $user->email)
            : null;

        if ($validated['mfa_method'] === 'sms') {
            $user->phone = $validated['phone'] ?? null;
        }

        $user->save();

        $mfa->issue($user, null, 'mfa_setup');
        $request->session()->put('mfa_setup_step', 'verify');

        return redirect()
            ->route(SecurityRoutes::name('mfa.setup'))
            ->with('status', 'Enter the verification code sent to your MFA contact.');
    }

    protected function verifySetup(
        Request $request,
        MfaService $mfa,
        SecurityEventLogger $logger,
        $user
    ): RedirectResponse {
        $request->validate([
            'otp' => ['required', 'string', 'size:'.config('security.mfa.otp_length', 6)],
        ]);

        if (! $mfa->verify($user, $request->input('otp'))) {
            $logger->auth('mfa.setup.failed', false, $user);

            return back()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        $user->mfa_configured_at = now();
        $user->save();

        $request->session()->forget('mfa_setup_step');
        $logger->auth('mfa.setup.completed', true, $user);

        return redirect()
            ->intended(config('security.routes.after_mfa_redirect', '/'))
            ->with('status', 'Multi-factor authentication has been configured.');
    }

    public function resend(Request $request, MfaService $mfa): RedirectResponse
    {
        $user = Auth::user();

        if ($user && $request->session()->get('mfa_setup_step') === 'verify') {
            $mfa->issue($user, null, 'mfa_setup_resend');
        }

        return back()->with('status', 'A new verification code has been sent.');
    }
}
