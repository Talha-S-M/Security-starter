<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Expired</title>
    @include('security::admin.partials.styles')
</head>
<body class="pitb-security pitb-security-page">
    @include('security::partials.header')

    <main class="auth-shell">
        <div class="card auth-card">
            <h1>Password expired</h1>
            <p class="muted">Your password has expired and must be changed before you can continue.</p>
            <a class="btn btn-primary btn-block" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('password.update')) }}">
                Change password
            </a>
        </div>
    </main>
</body>
</html>
