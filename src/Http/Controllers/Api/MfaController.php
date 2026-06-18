<?php

namespace Pitbphp\Security\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Services\MfaService;
use Pitbphp\Security\Support\SecurityRequest;
use Pitbphp\Security\Support\SecurityResponder;

class MfaController extends Controller
{
    public function verify(Request $request, MfaService $mfa): JsonResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:'.config('security.mfa.otp_length', 6)],
        ]);

        $user = Auth::user();
        $tokenId = SecurityRequest::currentTokenId($request);

        if (! $user || ! $mfa->verify($user, $request->input('otp'), $tokenId)) {
            return SecurityResponder::apiError('Invalid or expired verification code.', 'mfa_invalid', 422);
        }

        return SecurityResponder::apiSuccess('MFA verification successful.', ['mfa_verified' => true]);
    }

    public function resend(Request $request, MfaService $mfa): JsonResponse
    {
        $user = Auth::user();
        $deliveryMethod = null;

        if ($user) {
            $enabledMethods = config('security.mfa.methods', ['email', 'sms']);
            $validated = $request->validate([
                'delivery_method' => ['nullable', 'in:'.implode(',', $enabledMethods)],
            ]);

            $deliveryMethod = $mfa->preferredMethod($user, $validated['delivery_method'] ?? null);
            $mfa->issue($user, SecurityRequest::currentTokenId($request), 'resend_otp', $deliveryMethod);
        }

        return SecurityResponder::apiSuccess('A new verification code has been sent.', [
            'delivery_method' => $deliveryMethod,
        ]);
    }

    public function status(Request $request, MfaService $mfa): JsonResponse
    {
        $user = Auth::user();

        return SecurityResponder::apiSuccess(null, [
            'mfa_required' => config('security.mfa.enabled'),
            'mfa_verified' => $user ? $mfa->isVerified($user, $request) : false,
        ]);
    }
}
