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

<p class="muted">
    Enter the verification code sent to <strong>{{ $pendingEmail ?? 'your email' }}</strong>.
</p>

<form method="POST" action="{{ route('register.verify') }}">
    @csrf
    <div class="field">
        <label for="otp">Verification code</label>
        <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" required autofocus>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Verify and create account</button>
</form>

<form method="POST" action="{{ route('register.resend') }}" style="margin-top: .75rem;">
    @csrf
    <button class="btn btn-secondary btn-block" type="submit">Resend code</button>
</form>

<div class="auth-links">
    <a href="{{ route('register', ['restart' => 1]) }}">Start over</a>
    <a href="{{ route('login') }}">Already have an account?</a>
</div>
