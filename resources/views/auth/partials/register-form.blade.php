<div class="pitb-security-auth">
    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label for="name">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required>

        <label>MFA delivery method</label>
        <div class="checkbox-group">
            @foreach ($mfaMethods as $method)
                <label>
                    <input type="radio" name="mfa_method" value="{{ $method }}" @checked(old('mfa_method', config('security.mfa.default_method', 'email')) === $method)>
                    {{ ucfirst($method) }}
                </label>
            @endforeach
        </div>

        <label for="phone">Phone (required for SMS MFA)</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="+923001234567">

        @if (config('security.captcha.enabled', true) && function_exists('captcha_img'))
            <label for="captcha">CAPTCHA</label>
            <div>{!! captcha_img('flat') !!}</div>
            <input id="captcha" name="{{ config('security.captcha.field', 'captcha') }}" type="text" required autocomplete="off">
        @endif

        <button type="submit">Register</button>
    </form>

    <p style="margin-top: .75rem;">
        <a href="{{ route('login') }}">Already have an account?</a>
    </p>
</div>
