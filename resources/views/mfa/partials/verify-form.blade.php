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
    <p class="muted">
        Code sent via {{ auth()->user()->mfaMethod() }}
        @if (auth()->user()->mfaMethod() === 'email')
            to {{ auth()->user()->mfaDeliveryEmail() }}.
        @else
            .
        @endif
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
    <button class="btn btn-secondary btn-block" type="submit">Resend code</button>
</form>
