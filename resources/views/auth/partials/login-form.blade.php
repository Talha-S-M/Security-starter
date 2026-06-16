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

    @if (config('security.captcha.enabled', true) && function_exists('captcha_img'))
        <div class="field">
            <label for="captcha">CAPTCHA</label>
            <div class="captcha-wrap">{!! captcha_img('flat') !!}</div>
            <input id="captcha" name="{{ config('security.captcha.field', 'captcha') }}" type="text" required autocomplete="off" placeholder="Enter characters from image">
        </div>
    @endif

    <button class="btn btn-primary btn-block" type="submit">{{ $submitLabel ?? 'Sign in' }}</button>
</form>

<div class="auth-links">
    @if (config('security.auth.register', true))
        <a href="{{ route('register') }}">Create an account</a>
        &nbsp;|&nbsp;
    @endif
    <a href="{{ route('password.request') }}">Forgot password?</a>
</div>
