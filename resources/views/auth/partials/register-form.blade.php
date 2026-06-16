@if ($errors->any())
    <ul class="errors">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

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

    <div class="field">
        <label>MFA delivery method</label>
        <div class="checkbox-group">
            @foreach ($mfaMethods as $method)
                <label>
                    <input type="radio" name="mfa_method" value="{{ $method }}" @checked(old('mfa_method', config('security.mfa.default_method', 'email')) === $method)>
                    {{ ucfirst($method) }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="field">
        <label for="phone">Phone (required for SMS MFA)</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="+923001234567">
    </div>

    @if (config('security.captcha.enabled', true) && function_exists('captcha_img'))
        <div class="field">
            <label for="captcha">CAPTCHA</label>
            <div class="captcha-wrap">{!! captcha_img('flat') !!}</div>
            <input id="captcha" name="{{ config('security.captcha.field', 'captcha') }}" type="text" required autocomplete="off" placeholder="Enter characters from image">
        </div>
    @endif

    <button class="btn btn-primary btn-block" type="submit">Register</button>
</form>

<div class="auth-links">
    <a href="{{ route('login') }}">Already have an account?</a>
</div>
