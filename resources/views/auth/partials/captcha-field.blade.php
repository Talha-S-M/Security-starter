@if (config('security.captcha.enabled', true) && function_exists('captcha_src'))
    @php($captchaProfile = config('security.captcha.profile', 'flat'))
    @php($captchaId = $captchaId ?? 'pitb-captcha-img')

    <div class="field">
        <label for="captcha">CAPTCHA</label>
        <div class="captcha-wrap">
            <img id="{{ $captchaId }}" src="{{ captcha_src($captchaProfile) }}" alt="captcha" class="captcha-image">
            <button type="button" class="btn btn-secondary captcha-refresh" data-captcha-id="{{ $captchaId }}" data-captcha-src="{{ captcha_src($captchaProfile) }}">Refresh</button>
        </div>
        <input id="captcha" name="{{ config('security.captcha.field', 'captcha') }}" type="text" required autocomplete="off" placeholder="Enter characters from image">
    </div>
@endif
