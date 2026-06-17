@php
    $passwordId = $passwordId ?? 'password';
    $confirmationId = $confirmationId ?? 'password_confirmation';
    $passwordLabel = $passwordLabel ?? 'Password';
    $confirmationLabel = $confirmationLabel ?? 'Confirm password';
    $passwordAutocomplete = $passwordAutocomplete ?? null;
    $confirmationAutocomplete = $confirmationAutocomplete ?? null;
@endphp

<div class="field">
    <label for="{{ $passwordId }}">{{ $passwordLabel }}</label>
    <input
        id="{{ $passwordId }}"
        name="password"
        type="password"
        required
        @if ($passwordAutocomplete) autocomplete="{{ $passwordAutocomplete }}" @endif
    >

    @include('security::auth.partials.password-strength', [
        'passwordId' => $passwordId,
        'confirmationId' => $confirmationId,
    ])
</div>

<div class="field">
    <label for="{{ $confirmationId }}">{{ $confirmationLabel }}</label>
    <input
        id="{{ $confirmationId }}"
        name="password_confirmation"
        type="password"
        required
        @if ($confirmationAutocomplete) autocomplete="{{ $confirmationAutocomplete }}" @endif
    >
</div>
