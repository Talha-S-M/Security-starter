<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Expired</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 28rem; margin: 4rem auto; padding: 0 1rem; color: #1a1a1a; }
        .card { border: 1px solid #e5e5e5; border-radius: .5rem; padding: 1.5rem; }
        a.button { display: inline-block; margin-top: 1rem; padding: .6rem 1rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: .375rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Password expired</h1>
        <p>Your password has expired and must be changed before you can continue.</p>
        <a class="button" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('password.update')) }}">
            Change password
        </a>
    </div>
</body>
</html>
