@if (session('status'))
    <p class="status">{{ session('status') }}</p>
@endif

@if ($errors->any())
    <ul class="errors">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

@if (($step ?? 'configure') === 'verify')
    <p class="muted">Enter the verification code sent to your MFA contact.</p>

    <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('mfa.setup.submit')) }}">
        @csrf
        <div class="field">
            <label for="otp">Verification code</label>
            <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" required>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Verify and finish</button>
    </form>

    <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('mfa.setup.resend')) }}" style="margin-top: .75rem;">
        @csrf
        <button class="btn btn-secondary btn-block" type="submit">Resend code</button>
    </form>
@else
    <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('mfa.setup.submit')) }}" id="mfa-setup-form">
        @csrf

        <div class="field">
            <label for="mfa_method">MFA method</label>
            <select id="mfa_method" name="mfa_method" required>
                @foreach ($methods as $method)
                    <option value="{{ $method }}" @selected(old('mfa_method', config('security.mfa.default_method', 'email')) === $method)>
                        {{ ucfirst($method) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field" id="mfa-email-field">
            <label for="mfa_email">MFA email</label>
            <input id="mfa_email" name="mfa_email" type="email" value="{{ old('mfa_email') }}" placeholder="Personal email for OTP delivery">
        </div>

        <div class="field" id="mfa-phone-field" style="display: none;">
            <label for="phone">Phone number</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', auth()->user()->phone ?? '') }}" placeholder="03XXXXXXXXX">
        </div>

        <button class="btn btn-primary btn-block" type="submit">Send verification code</button>
    </form>

    <script>
        (function () {
            const method = document.getElementById('mfa_method');
            const emailField = document.getElementById('mfa-email-field');
            const phoneField = document.getElementById('mfa-phone-field');
            const emailInput = document.getElementById('mfa_email');
            const phoneInput = document.getElementById('phone');

            function syncFields() {
                const isSms = method.value === 'sms';
                emailField.style.display = isSms ? 'none' : '';
                phoneField.style.display = isSms ? '' : 'none';
                emailInput.required = !isSms;
                phoneInput.required = isSms;
            }

            method.addEventListener('change', syncFields);
            syncFields();
        })();
    </script>
@endif
