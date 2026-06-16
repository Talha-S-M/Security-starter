<div class="pitb-security-auth">
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

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        @if (config('security.captcha.enabled', true) && function_exists('captcha_img'))
            <label for="captcha">CAPTCHA</label>
            <div>{!! captcha_img('flat') !!}</div>
            <input id="captcha" name="{{ config('security.captcha.field', 'captcha') }}" type="text" required autocomplete="off">
        @endif

        <button type="submit">{{ $submitLabel ?? 'Sign in' }}</button>
    </form>

    <p style="margin-top: .75rem;">
        @if (config('security.auth.register', true))
            <a href="{{ route('register') }}">Create an account</a>
            &nbsp;|&nbsp;
        @endif
        <a href="{{ route('password.request') }}">Forgot password?</a>
    </p>
</div>
