<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
    @include('security::admin.partials.styles')
</head>
<body class="pitb-security pitb-security-page">
    @include('security::partials.header')

    <main class="auth-shell">
        <div class="card auth-card">
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
                    <div class="field">
                        <label for="current_password">Current password</label>
                        <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
                    </div>
                @endunless

                @include('security::auth.partials.password-fields', [
                    'passwordLabel' => 'New password',
                    'confirmationLabel' => 'Confirm new password',
                    'passwordAutocomplete' => 'new-password',
                    'confirmationAutocomplete' => 'new-password',
                ])

                <button class="btn btn-primary btn-block" type="submit">Save password</button>
            </form>
        </div>
    </main>
</body>
</html>
