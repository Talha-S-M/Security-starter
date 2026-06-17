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

<p class="muted">Your request will be reviewed by an administrator before you can sign in.</p>

<form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="field">
        <label for="name">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
    </div>

    <div class="field">
        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required>
    </div>

    @include('security::auth.partials.password-strength')

    @include('security::auth.partials.captcha-field', ['captchaId' => 'pitb-register-captcha-img'])

    <button class="btn btn-primary btn-block" type="submit">Submit for approval</button>
</form>

<div class="auth-links">
    <a href="{{ route('login') }}">Already have an account?</a>
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
