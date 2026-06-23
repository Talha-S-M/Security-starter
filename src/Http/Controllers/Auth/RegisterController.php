<?php

namespace Pitbphp\Security\Http\Controllers\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Pitbphp\Security\Http\Requests\SecurityRegisterRequest;
use Pitbphp\Security\Http\Requests\SecurityRegisterVerifyRequest;
use Pitbphp\Security\Services\AccessProvisioningService;
use Pitbphp\Security\Services\RegistrationOtpService;
use Pitbphp\Security\Services\SecurityEventLogger;
use Pitbphp\Security\Support\SecurityTier;

class RegisterController extends Controller
{
    public function show(Request $request): View
    {
        if ($request->boolean('restart')) {
            $request->session()->forget(['security.registration.pending', 'security.registration.step']);
        }

        return view('security::auth.register', [
            'step' => $request->session()->get('security.registration.step', 'form'),
            'pendingEmail' => $request->session()->get('security.registration.pending.email'),
        ]);
    }

    public function store(
        SecurityRegisterRequest $request,
        AccessProvisioningService $provisioning,
        RegistrationOtpService $otp
    ): RedirectResponse {
        $payload = $provisioning->buildUserPayload(
            $request->input('name'),
            $request->input('email'),
            Hash::make($request->input('password'))
        );

        if (SecurityTier::registrationRequiresApproval()) {
            $provisioning->submitRegistration($payload);

            return redirect()
                ->route('login')
                ->with('status', 'Your registration has been submitted. An administrator must approve it before you can sign in.');
        }

        if (SecurityTier::registrationUsesOtp()) {
            $otp->issue($payload['email']);

            $request->session()->put('security.registration.pending', $payload);
            $request->session()->put('security.registration.step', 'verify');

            return redirect()
                ->route('register')
                ->with('status', 'Enter the verification code sent to '.$payload['email'].'.');
        }

        $user = $provisioning->registerVerifiedUser($payload);
        event(new Registered($user));
        Auth::login($user);

        return redirect()
            ->intended(config('security.auth.redirect_after_login', '/'))
            ->with('status', 'Your account has been created.');
    }

    public function verify(
        SecurityRegisterVerifyRequest $request,
        RegistrationOtpService $otp,
        AccessProvisioningService $provisioning,
        SecurityEventLogger $logger
    ): RedirectResponse {
        $pending = $request->session()->get('security.registration.pending');

        if (! is_array($pending) || empty($pending['email'])) {
            return redirect()
                ->route('register')
                ->withErrors(['otp' => 'Your registration session has expired. Please start again.']);
        }

        if (! $otp->verify((string) $pending['email'], $request->input('otp'))) {
            $logger->auth('registration.otp_failed', false, null, [
                'email' => $pending['email'],
            ]);

            return back()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        $user = $provisioning->registerVerifiedUser($pending);

        $request->session()->forget(['security.registration.pending', 'security.registration.step']);

        Auth::login($user);

        return redirect()
            ->intended(config('security.auth.redirect_after_login', '/'))
            ->with('status', 'Your account has been created. You are now signed in.');
    }

    public function resend(Request $request, RegistrationOtpService $otp): RedirectResponse
    {
        $pending = $request->session()->get('security.registration.pending');

        if (! is_array($pending) || empty($pending['email'])) {
            return redirect()
                ->route('register')
                ->withErrors(['otp' => 'Your registration session has expired. Please start again.']);
        }

        $otp->issue((string) $pending['email']);

        return back()->with('status', 'A new verification code has been sent.');
    }
}
