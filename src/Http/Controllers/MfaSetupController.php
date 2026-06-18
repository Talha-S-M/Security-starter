<?php

namespace Pitbphp\Security\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Pitbphp\Security\Services\MfaService;
use Pitbphp\Security\Services\SecurityEventLogger;
use Pitbphp\Security\Support\MfaContactSupport;
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

        $enabledMethods = MfaContactSupport::enabledMethods();

        return view('security::mfa.setup', [
            'step' => $request->session()->get('mfa_setup_step', 'configure'),
            'enabledMethods' => $enabledMethods,
            'availableMethods' => MfaContactSupport::availableMethods($user),
            'deliveryMethod' => $request->session()->get('security.mfa_delivery_method'),
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

        $enabledMethods = MfaContactSupport::enabledMethods();
        $rules = [
            'delivery_method' => ['nullable', 'in:'.implode(',', $enabledMethods)],
        ];

        if ($enabledMethods === ['email']) {
            $rules['mfa_email'] = ['required', 'email', 'max:255'];
        } elseif ($enabledMethods === ['sms']) {
            $rules['phone'] = ['required', 'string', 'max:30'];
        } else {
            $rules['mfa_email'] = ['nullable', 'email', 'max:255', 'required_without:phone'];
            $rules['phone'] = ['nullable', 'string', 'max:30', 'required_without:mfa_email'];
        }

        $validated = $request->validate($rules);

        $user->mfa_email = in_array('email', $enabledMethods, true)
            ? (filled($validated['mfa_email'] ?? null) ? $validated['mfa_email'] : null)
            : $user->mfa_email;

        $user->phone = in_array('sms', $enabledMethods, true)
            ? (filled($validated['phone'] ?? null) ? $validated['phone'] : null)
            : $user->phone;

        if (method_exists($user, 'syncMfaMethods')) {
            $user->syncMfaMethods();
        }

        if (! MfaContactSupport::hasRequiredContact($user)) {
            return back()->withErrors([
                'mfa_email' => 'Provide at least one MFA contact (email or phone).',
            ])->withInput();
        }

        $user->save();

        $deliveryMethod = MfaContactSupport::resolveDeliveryMethod(
            $user,
            $validated['delivery_method'] ?? null
        );

        $request->session()->put('security.mfa_delivery_method', $deliveryMethod);
        $mfa->issue($user, null, 'mfa_setup', $deliveryMethod);
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

        if (method_exists($user, 'syncMfaMethods')) {
            $user->syncMfaMethods();
        }

        $user->mfa_configured_at = now();
        $user->save();

        $request->session()->forget(['mfa_setup_step', 'security.mfa_delivery_method']);
        $logger->auth('mfa.setup.completed', true, $user);

        return redirect()
            ->intended(config('security.routes.after_mfa_redirect', '/'))
            ->with('status', 'Multi-factor authentication has been configured.');
    }

    public function resend(Request $request, MfaService $mfa): RedirectResponse
    {
        $user = Auth::user();

        if ($user && $request->session()->get('mfa_setup_step') === 'verify') {
            $enabledMethods = MfaContactSupport::enabledMethods();
            $validated = $request->validate([
                'delivery_method' => ['nullable', 'in:'.implode(',', $enabledMethods)],
            ]);

            $deliveryMethod = MfaContactSupport::resolveDeliveryMethod(
                $user,
                $validated['delivery_method'] ?? $request->session()->get('security.mfa_delivery_method')
            );

            $request->session()->put('security.mfa_delivery_method', $deliveryMethod);
            $mfa->issue($user, null, 'mfa_setup_resend', $deliveryMethod);
        }

        return back()->with('status', 'A new verification code has been sent.');
    }
}
