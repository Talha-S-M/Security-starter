@php
    $passwordId = $passwordId ?? 'password';
    $confirmationId = $confirmationId ?? 'password_confirmation';
    $passwordLabel = $passwordLabel ?? 'Password';
    $confirmationLabel = $confirmationLabel ?? 'Confirm password';
    $passwordAutocomplete = $passwordAutocomplete ?? null;
    $confirmationAutocomplete = $confirmationAutocomplete ?? null;
    $passwordValue = $passwordValue ?? old('password');
    $confirmationValue = $confirmationValue ?? old('password_confirmation');
    $showGeneratePassword = (bool) ($showGeneratePassword ?? false);
@endphp

<div class="field">
    <div class="field-label-row">
        <label for="{{ $passwordId }}">{{ $passwordLabel }}</label>
        @if ($showGeneratePassword)
            <button
                type="button"
                class="btn btn-secondary btn-sm"
                data-pitb-generate-password
                data-password-id="{{ $passwordId }}"
                data-confirmation-id="{{ $confirmationId }}"
                data-policy='@json(\Pitbphp\Security\Support\PasswordStrength::policy())'
            >
                Generate password
            </button>
        @endif
    </div>
    <input
        id="{{ $passwordId }}"
        name="password"
        type="password"
        value="{{ $passwordValue }}"
        required
        @if ($passwordAutocomplete) autocomplete="{{ $passwordAutocomplete }}" @endif
    >
    @if ($showGeneratePassword)
        <p class="field-hint">A temporary password is prefilled. The user must change it on first login.</p>
    @endif

    @include('security::auth.partials.password-strength', [
        'passwordId' => $passwordId,
    ])
</div>

<div class="field">
    <label for="{{ $confirmationId }}">{{ $confirmationLabel }}</label>
    <input
        id="{{ $confirmationId }}"
        name="password_confirmation"
        type="password"
        value="{{ $confirmationValue }}"
        required
        @if ($confirmationAutocomplete) autocomplete="{{ $confirmationAutocomplete }}" @endif
    >
</div>

@if ($showGeneratePassword)
    @once('pitb-security-generate-password-script')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                function generatePassword(policy) {
                    const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
                    const lower = 'abcdefghjkmnpqrstuvwxyz';
                    const numbers = '23456789';
                    const symbols = '!@#$%&*?';
                    const length = Math.max(Number(policy.min_length || 12), 12);
                    let required = '';
                    let pool = '';

                    if (policy.require_uppercase) {
                        required += upper[Math.floor(Math.random() * upper.length)];
                        pool += upper;
                    }
                    if (policy.require_lowercase) {
                        required += lower[Math.floor(Math.random() * lower.length)];
                        pool += lower;
                    }
                    if (policy.require_numbers) {
                        required += numbers[Math.floor(Math.random() * numbers.length)];
                        pool += numbers;
                    }
                    if (policy.require_symbols) {
                        required += symbols[Math.floor(Math.random() * symbols.length)];
                        pool += symbols;
                    }

                    if (!pool) {
                        pool = upper + lower + numbers + symbols;
                    }

                    let password = required;
                    while (password.length < length) {
                        password += pool[Math.floor(Math.random() * pool.length)];
                    }

                    return password.split('').sort(function () { return Math.random() - 0.5; }).join('');
                }

                document.querySelectorAll('[data-pitb-generate-password]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const policy = JSON.parse(button.dataset.policy || '{}');
                        const passwordInput = document.getElementById(button.dataset.passwordId);
                        const confirmationInput = document.getElementById(button.dataset.confirmationId);

                        if (!passwordInput || !confirmationInput) {
                            return;
                        }

                        const password = window.PitbPasswordStrength
                            ? (function () {
                                let candidate = generatePassword(policy);
                                let attempts = 0;

                                while (attempts < 5 && !window.PitbPasswordStrength.analyze(candidate, policy).valid) {
                                    candidate = generatePassword(policy);
                                    attempts++;
                                }

                                return candidate;
                            })()
                            : generatePassword(policy);

                        passwordInput.value = password;
                        confirmationInput.value = password;
                        passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
                        passwordInput.focus();
                    });
                });
            });
        </script>
    @endonce
@endif
