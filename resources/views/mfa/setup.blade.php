<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configure MFA</title>
    @include('security::admin.partials.styles')
</head>
<body class="pitb-security pitb-security-page">
    @include('security::partials.header')

    <main class="auth-shell">
        <div class="card auth-card">
            <h1>Configure MFA</h1>
            <p class="muted">Choose how you want to receive verification codes. This contact can differ from your account email.</p>

            @include('security::mfa.partials.setup-form', [
                'step' => $step ?? 'configure',
                'methods' => $methods ?? config('security.mfa.methods', ['email', 'sms']),
            ])
        </div>
    </main>
</body>
</html>
