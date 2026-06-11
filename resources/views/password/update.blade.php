<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 28rem; margin: 4rem auto; padding: 0 1rem; color: #1a1a1a; }
        .card { border: 1px solid #e5e5e5; border-radius: .5rem; padding: 1.5rem; }
        label { display: block; margin-top: 1rem; font-weight: 600; }
        input { width: 100%; margin-top: .25rem; padding: .5rem; box-sizing: border-box; }
        button { margin-top: 1.25rem; padding: .6rem 1rem; background: #2563eb; color: #fff; border: 0; border-radius: .375rem; cursor: pointer; }
        .errors { color: #b91c1c; margin: 0; padding-left: 1.25rem; }
        .status { color: #166534; margin-bottom: 1rem; }
        .hint { color: #6b7280; font-size: .875rem; margin-top: .5rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Update password</h1>

        @if (session('status'))
            <p class="status">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <ul class="errors">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::name('password.update.submit')) }}">
            @csrf

            @unless (auth()->user()->must_change_password ?? false)
                <label for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
            @endunless

            <label for="password">New password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">

            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">

            <p class="hint">
                Minimum {{ config('security.password.min_length', 12) }} characters with uppercase, lowercase, numbers, and symbols.
            </p>

            <button type="submit">Save password</button>
        </form>
    </div>
</body>
</html>
