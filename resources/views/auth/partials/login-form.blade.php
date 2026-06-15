<div class="pitb-security-auth">
    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ $action ?? route(config('security.captcha.login_route', 'login')) }}">
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
</div>
