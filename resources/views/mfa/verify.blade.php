<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Identity</title>
</head>
<body>
    <h1>Two-Factor Verification</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('mfa.verify.submit')) }}">
        @csrf
        <label for="otp">Verification code</label>
        <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" required>
        <button type="submit">Verify</button>
    </form>

    <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('mfa.resend')) }}">
        @csrf
        <button type="submit">Resend code</button>
    </form>
</body>
</html>
