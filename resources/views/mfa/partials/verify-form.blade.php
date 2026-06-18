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

@auth
    @php
        $user = auth()->user();
        $deliveryMethod = session('security.mfa_delivery_method', $user->mfaMethod());
        $availableMethods = \Pitbphp\Security\Support\MfaContactSupport::resolveMethods($user);
    @endphp
    <p class="muted">
        Code sent via {{ ucfirst($deliveryMethod) }}
        to {{ \Pitbphp\Security\Support\MfaContactSupport::deliveryLabel($user, $deliveryMethod) }}.
    </p>
@endauth

<form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('mfa.verify.submit')) }}">
    @csrf
    <div class="field">
        <label for="otp">Verification code</label>
        <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" required>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Verify</button>
</form>

<form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('mfa.resend')) }}" style="margin-top: .75rem;">
    @csrf
    @if (count($availableMethods ?? []) > 1)
        <div class="field">
            <label for="delivery_method">Resend via</label>
            <select id="delivery_method" name="delivery_method">
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
