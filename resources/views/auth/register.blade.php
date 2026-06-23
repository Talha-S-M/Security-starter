<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($step ?? 'form') === 'verify' ? 'Verify registration' : 'Create account' }}</title>
    @include('security::admin.partials.styles')
</head>
<body class="pitb-security pitb-security-page">
    @include('security::partials.header')

    <main class="auth-shell">
        <div class="card auth-card">
            <h1>{{ ($step ?? 'form') === 'verify' ? 'Verify your email' : 'Create account' }}</h1>

            @if (($step ?? 'form') === 'verify')
                @include('security::auth.partials.register-verify-form')
            @else
                @include('security::auth.partials.register-form')
            @endif
        </div>
    </main>
</body>
</html>
