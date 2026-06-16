@if ($errors->any())
    <ul class="errors">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

@if (session('status'))
    <p class="status">{{ session('status') }}</p>
@endif

<form method="POST" action="{{ url('login') }}">
    @csrf

    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
    </div>

    @include('security::auth.partials.captcha-field', ['captchaId' => 'pitb-login-captcha-img'])

    <button class="btn btn-primary btn-block" type="submit">{{ $submitLabel ?? 'Sign in' }}</button>
</form>

<div class="auth-links">
    @if (config('security.auth.register', false))
        <a href="{{ route('register') }}">Request an account</a>
        &nbsp;|&nbsp;
    @endif
    <a href="{{ route('password.request') }}">Forgot password?</a>
</div>

<script>
    document.querySelectorAll('.captcha-refresh').forEach(function (button) {
        button.addEventListener('click', function () {
            const img = document.getElementById(button.dataset.captchaId);
            const base = button.dataset.captchaSrc;
            img.src = base + (base.includes('?') ? '&' : '?') + '_=' + Date.now();
        });
    });
</script>
