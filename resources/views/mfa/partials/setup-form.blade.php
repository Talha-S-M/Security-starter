@if (session('status'))
    <p class="status">{{ session('status') }}</p>
@endif

@if ($errors->any())
    <ul class="errors">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

@if (($step ?? 'configure') === 'verify')
    @php
        $user = auth()->user();
        $deliveryMethod = $deliveryMethod ?? session('security.mfa_delivery_method', $user?->mfaMethod());
        $availableMethods = $availableMethods ?? ($user ? \Pitbphp\Security\Support\MfaContactSupport::resolveMethods($user) : []);
    @endphp

    <p class="muted">
        Code sent via {{ ucfirst((string) $deliveryMethod) }}
        to {{ \Pitbphp\Security\Support\MfaContactSupport::deliveryLabel($user, (string) $deliveryMethod) }}.
    </p>

    <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('mfa.setup.submit')) }}">
        @csrf
        <div class="field">
            <label for="otp">Verification code</label>
            <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" required>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Verify and finish</button>
    </form>

    <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('mfa.setup.resend')) }}" style="margin-top: .75rem;">
        @csrf
        @if (count($availableMethods) > 1)
            <div class="field">
                <label for="setup_delivery_method">Resend via</label>
                <select id="setup_delivery_method" name="delivery_method">
                    @foreach ($availableMethods as $method)
                        <option value="{{ $method }}" @selected($deliveryMethod === $method)>
                            {{ ucfirst($method) }}
                            ({{ \Pitbphp\Security\Support\MfaContactSupport::deliveryLabel($user, $method) }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <button class="btn btn-secondary btn-block" type="submit">Resend code</button>
    </form>
@else
    @php
        $enabledMethods = $enabledMethods ?? \Pitbphp\Security\Support\MfaContactSupport::enabledMethods();
        $user = auth()->user();
    @endphp

    <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('mfa.setup.submit')) }}" id="mfa-setup-form">
        @csrf

        @if (in_array('email', $enabledMethods, true))
            <div class="field">
                <label for="mfa_email">MFA email</label>
                <input
                    id="mfa_email"
                    name="mfa_email"
                    type="email"
                    value="{{ old('mfa_email', $user->mfa_email ?? '') }}"
                    placeholder="Personal email for OTP delivery"
                >
            </div>
        @endif

        @if (in_array('sms', $enabledMethods, true))
            <div class="field">
                <label for="phone">Phone number</label>
                <input
                    id="phone"
                    name="phone"
                    type="text"
                    value="{{ old('phone', $user->phone ?? '') }}"
                    placeholder="03XXXXXXXXX"
                >
            </div>
        @endif

        <p class="field-hint">Provide every contact you want to use for MFA. Enabled methods are derived from these fields.</p>

        @if (count($enabledMethods) > 1)
            <div class="field">
                <label for="delivery_method">Send verification code via</label>
                <select id="delivery_method" name="delivery_method">
                    @foreach ($enabledMethods as $method)
                        <option value="{{ $method }}" @selected(old('delivery_method', config('security.mfa.default_method', 'email')) === $method)>
                            {{ ucfirst($method) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <button class="btn btn-primary btn-block" type="submit">Send verification code</button>
    </form>
@endif
