<div class="pitb-security-auth">
    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    @if (session('status'))
        <p class="status">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

        <button type="submit">Email password reset link</button>
    </form>

    <p style="margin-top: .75rem;">
        <a href="{{ route('login') }}">Back to login</a>
    </p>
</div>
