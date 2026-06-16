<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request account</title>
    @include('security::admin.partials.styles')
</head>
<body class="pitb-security pitb-security-page">
    @include('security::partials.header')

    <main class="auth-shell">
        <div class="card auth-card">
            <h1>Request account</h1>
            @include('security::auth.partials.register-form')
        </div>
    </main>
</body>
</html>
