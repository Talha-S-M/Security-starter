<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    @include('security::partials.header')

    <h1>Create account</h1>

    @include('security::auth.partials.register-form', [
        'mfaMethods' => $mfaMethods ?? config('security.mfa.methods', ['email', 'sms']),
    ])
</body>
</html>
