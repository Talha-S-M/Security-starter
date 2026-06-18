@php
    $passwordId = $passwordId ?? 'password';
    $meterId = $meterId ?? 'pitb-password-strength-'.uniqid();
    $passwordOptional = (bool) ($passwordOptional ?? false);
@endphp

<div
    class="pitb-password-strength"
    data-pitb-password-strength
    data-password-id="{{ $passwordId }}"
    data-meter-id="{{ $meterId }}"
    data-policy='@json(\Pitbphp\Security\Support\PasswordStrength::policy())'
    @if ($passwordOptional ?? false) data-password-optional="true" @endif
    hidden
>
    <div class="pitb-password-strength__bar" aria-hidden="true">
        <div id="{{ $meterId }}" class="pitb-password-strength__fill" data-strength="weak"></div>
    </div>
    <p class="pitb-password-strength__label muted" data-strength-label>Password strength: —</p>
    <ul class="pitb-password-strength__rules" data-strength-rules></ul>
    <p class="pitb-password-strength__status" data-strength-status hidden></p>
</div>

@once('pitb-security-password-strength-script')
    @php
        $publishedStrengthScript = public_path('vendor/pitb-security/js/pitb-password-strength.js');
        $strengthScriptUrl = is_file($publishedStrengthScript)
            ? asset('vendor/pitb-security/js/pitb-password-strength.js')
            : route(\Pitbphp\Security\Support\SecurityRoutes::name('assets.password-strength'));
    @endphp
    <script src="{{ $strengthScriptUrl }}" defer></script>
@endonce
