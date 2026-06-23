@if ($errors->any())
    <ul class="errors">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

@if (session('status'))
    <p class="status">{{ session('status') }}</p>
@endif

<p class="muted">
    @if (\Pitbphp\Security\Support\SecurityTier::registrationRequiresApproval())
        Your request will be reviewed by an administrator before you can sign in.
    @elseif (\Pitbphp\Security\Support\SecurityTier::registrationUsesOtp())
        We will email you a verification code to confirm your address before creating your account.
    @else
        Create your account to get started.
    @endif
</p>

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

    @include('security::auth.partials.password-fields')

    @include('security::auth.partials.captcha-field', ['captchaId' => 'pitb-register-captcha-img'])

    <button class="btn btn-primary btn-block" type="submit">
        @if (\Pitbphp\Security\Support\SecurityTier::registrationRequiresApproval())
            Submit for approval
        @elseif (\Pitbphp\Security\Support\SecurityTier::registrationUsesOtp())
            Send verification code
        @else
            Create account
        @endif
    </button>
</form>

<div class="auth-links">
    <a href="{{ route('login') }}">Already have an account?</a>
</div>

<script>
    document.querySelectorAll('.captcha-refresh').forEach(function (button) {
        button.addEventListener('click', function () {
            const img = document.getElementById(button.dataset.captchaId);
            const base = button.dataset.captchaSrc;
            img.src = base + (base.includes('?') ? '&' : '?') + '_=' + Date.now();
        });
    });
</script>
