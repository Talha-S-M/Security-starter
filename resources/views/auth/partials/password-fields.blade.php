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
    $passwordRequired = (bool) ($passwordRequired ?? true);
    $confirmationRequired = (bool) ($confirmationRequired ?? true);
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
        @if ($passwordRequired) required @endif
        @if ($passwordAutocomplete) autocomplete="{{ $passwordAutocomplete }}" @endif
    >
    @if ($showGeneratePassword)
        <div
            class="password-preview"
            data-pitb-password-preview
            data-password-id="{{ $passwordId }}"
            role="button"
            tabindex="0"
            title="Click to copy password"
            @if (! $passwordValue) hidden @endif
        >
            <div class="password-preview__header">
                <span class="password-preview__label">Temporary password</span>
                <span class="password-preview__copy-hint" data-pitb-password-copy-hint>Click to copy</span>
            </div>
            <code class="password-preview__value" data-pitb-password-preview-value>{{ $passwordValue }}</code>
        </div>
        <p class="field-hint">Share this password with the user securely. They must change it on first login.</p>
    @endif

    @include('security::auth.partials.password-strength', [
        'passwordId' => $passwordId,
        'passwordOptional' => ! $passwordRequired,
    ])
</div>

<div class="field">
    <label for="{{ $confirmationId }}">{{ $confirmationLabel }}</label>
    <input
        id="{{ $confirmationId }}"
        name="password_confirmation"
        type="password"
        value="{{ $confirmationValue }}"
        @if ($confirmationRequired) required @endif
        @if ($confirmationAutocomplete) autocomplete="{{ $confirmationAutocomplete }}" @endif
    >
</div>

@if ($showGeneratePassword)
    @once('pitb-security-temporary-password-script')
        @php
            $publishedTemporaryPasswordScript = public_path('vendor/pitb-security/js/pitb-temporary-password.js');
            $temporaryPasswordScriptUrl = is_file($publishedTemporaryPasswordScript)
                ? asset('vendor/pitb-security/js/pitb-temporary-password.js')
                : route(\Pitbphp\Security\Support\SecurityRoutes::name('assets.temporary-password'));
        @endphp
        <script src="{{ $temporaryPasswordScriptUrl }}" defer></script>
    @endonce
@endif
