<div class="pitb-security-mfa">
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
        <p class="muted">Code sent via {{ auth()->user()->mfaMethod() }}.</p>
    @endauth

    <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('mfa.verify.submit')) }}">
        @csrf
        <label for="otp">Verification code</label>
        <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" required>
        <button type="submit">Verify</button>
    </form>

    <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('mfa.resend')) }}">
        @csrf
        <button type="submit">Resend code</button>
    </form>
</div>
