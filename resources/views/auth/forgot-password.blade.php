<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot password</title>
</head>
<body>
    @include('security::partials.header')

    <h1>Forgot password</h1>

    @include('security::auth.partials.forgot-password-form')
</body>
</html>
