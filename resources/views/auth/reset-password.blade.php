<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset password</title>
</head>
<body>
    @include('security::partials.header')

    <h1>Reset password</h1>

    @include('security::auth.partials.reset-password-form', compact('token', 'email'))
</body>
</html>
