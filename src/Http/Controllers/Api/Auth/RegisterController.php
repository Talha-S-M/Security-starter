<?php

namespace Pitbphp\Security\Http\Controllers\Api\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Pitbphp\Security\Http\Requests\Api\SecurityApiRegisterResendRequest;
use Pitbphp\Security\Http\Requests\Api\SecurityApiRegisterRequest;
use Pitbphp\Security\Http\Requests\Api\SecurityApiRegisterVerifyRequest;
use Pitbphp\Security\Services\AccessProvisioningService;
use Pitbphp\Security\Services\RegistrationOtpService;
use Pitbphp\Security\Support\SanctumInstaller;
use Pitbphp\Security\Support\SecurityLog;
use Pitbphp\Security\Support\SecurityResponder;
use Pitbphp\Security\Support\SecurityTier;

class RegisterController extends Controller
{
    public function store(
        SecurityApiRegisterRequest $request,
        AccessProvisioningService $provisioning,
        RegistrationOtpService $otp
    ): JsonResponse {
        $payload = $provisioning->buildUserPayload(
            $request->input('name'),
            $request->input('email'),
            Hash::make($request->input('password'))
        );

        if (SecurityTier::registrationRequiresApproval()) {
            $accessRequest = $provisioning->submitRegistration($payload);

            return SecurityResponder::apiSuccess(
                'Your registration has been submitted. An administrator must approve it before you can sign in.',
                ['request_id' => $accessRequest->id],
                'registration_pending',
                202
            );
        }

        if (SecurityTier::registrationUsesOtp()) {
            $otp->issue($payload['email']);
            $otp->storePendingPayload($payload['email'], $payload);

            return SecurityResponder::apiSuccess(
                'Enter the verification code sent to '.$payload['email'].'.',
                ['email' => $payload['email']],
                'registration_otp_sent',
                202
            );
        }

        $user = $provisioning->registerVerifiedUser($payload);
        event(new Registered($user));

        return $this->tokenResponse($user, $request->input('device_name'));
    }

    public function verify(
        SecurityApiRegisterVerifyRequest $request,
        RegistrationOtpService $otp,
        AccessProvisioningService $provisioning
    ): JsonResponse {
        $email = (string) $request->input('email');

        if (! $otp->verify($email, $request->input('otp'))) {
            SecurityLog::auth('registration.otp_failed', false, null, [
                'email' => $email,
            ]);

            return SecurityResponder::apiError(
                'Invalid or expired verification code.',
                'registration_otp_invalid',
                422
            );
        }

        $pending = $otp->pullPendingPayload($email);

        if (! is_array($pending)) {
            return SecurityResponder::apiError(
                'Your registration session has expired. Please start again.',
                'registration_expired',
                422
            );
        }

        $user = $provisioning->registerVerifiedUser($pending);
        event(new Registered($user));

        return $this->tokenResponse($user, $request->input('device_name'));
    }

    public function resend(
        SecurityApiRegisterResendRequest $request,
        RegistrationOtpService $otp
    ): JsonResponse {
        $email = (string) $request->input('email');

        if (! $otp->hasPendingPayload($email)) {
            return SecurityResponder::apiError(
                'Your registration session has expired. Please start again.',
                'registration_expired',
                422
            );
        }

        $otp->issue($email);

        return SecurityResponder::apiSuccess('A new verification code has been sent.');
    }

    protected function tokenResponse($user, ?string $deviceName = null): JsonResponse
    {
        if (! SanctumInstaller::userHasApiTokens()) {
            return SecurityResponder::apiSuccess('Your account has been created.', [
                'user_id' => $user->getKey(),
            ]);
        }

        $tokenName = (string) ($deviceName ?: config('security.api.auth.token_name', 'api'));
        $token = $user->createToken($tokenName);

        return SecurityResponder::apiSuccess('Your account has been created.', [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user_id' => $user->getKey(),
        ], null, 201);
    }
}
