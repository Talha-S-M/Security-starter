<?php

namespace Pitbphp\Security\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Pitbphp\Security\Http\Requests\SecurityRegisterRequest;
use Pitbphp\Security\Services\AccessProvisioningService;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('security::auth.register');
    }

    public function store(SecurityRegisterRequest $request, AccessProvisioningService $provisioning): RedirectResponse
    {
        $payload = $provisioning->buildUserPayload(
            $request->input('name'),
            $request->input('email'),
            Hash::make($request->input('password'))
        );

        $provisioning->submitRegistration($payload);

        return redirect()
            ->route('login')
            ->with('status', 'Your registration has been submitted. An administrator must approve it before you can sign in.');
    }
}
