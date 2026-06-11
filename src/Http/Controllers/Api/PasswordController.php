<?php

namespace Pitbphp\Security\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Pitbphp\Security\Rules\PitbPassword;
use Pitbphp\Security\Services\PasswordHistoryService;

class PasswordController extends Controller
{
    public function update(Request $request, PasswordHistoryService $passwordHistory): JsonResponse
    {
        $user = Auth::user();

        $rules = [
            'password' => ['required', 'confirmed', new PitbPassword($user)],
        ];

        if (! ($user->must_change_password ?? false)) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validate($rules);

        $hashed = Hash::make($validated['password']);

        $user->password = $hashed;
        $user->password_changed_at = now();
        $user->must_change_password = false;
        $user->save();

        $passwordHistory->record($user, $hashed);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'password_expired' => method_exists($user, 'isPasswordExpired')
                ? $user->isPasswordExpired()
                : false,
            'must_change_password' => (bool) ($user->must_change_password ?? false),
        ]);
    }
}
