@php
    $passwordId = $passwordId ?? 'password';
    $confirmationId = $confirmationId ?? 'password_confirmation';
    $meterId = $meterId ?? 'pitb-password-strength-'.uniqid();
    $requireConfirmation = $requireConfirmation ?? true;
@endphp

<div
    class="pitb-password-strength"
    data-pitb-password-strength
    data-password-id="{{ $passwordId }}"
    data-confirmation-id="{{ $requireConfirmation ? $confirmationId : '' }}"
    data-meter-id="{{ $meterId }}"
    data-policy='@json(\Pitbphp\Security\Support\PasswordStrength::policy())'
>
    <div class="pitb-password-strength__bar" aria-hidden="true">
        <div id="{{ $meterId }}" class="pitb-password-strength__fill" data-strength="weak"></div>
    </div>
    <p class="pitb-password-strength__label muted" data-strength-label>Password strength: —</p>
    <ul class="pitb-password-strength__rules" data-strength-rules></ul>
    <p class="pitb-password-strength__status" data-strength-status hidden></p>
</div>

@once('pitb-security-password-strength-script')
    <script src="{{ asset('vendor/pitb-security/js/pitb-password-strength.js') }}" defer></script>
@endonce
