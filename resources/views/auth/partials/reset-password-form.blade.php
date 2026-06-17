@if ($errors->any())
    <ul class="errors">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ route('password.store') }}">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">

    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus>
    </div>

    @include('security::auth.partials.password-fields', [
        'passwordLabel' => 'New password',
    ])

    <button class="btn btn-primary btn-block" type="submit">Reset password</button>
</form>
