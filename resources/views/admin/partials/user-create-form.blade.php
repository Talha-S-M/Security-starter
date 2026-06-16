<div class="pitb-security">
    @include('security::admin.partials.styles')

    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <div class="card">
        <h2>Create user</h2>

        @if ($requiresApproval ?? false)
            <p class="muted">Your request will be sent to a super-admin for approval.</p>
        @endif

        <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users.store')) }}">
            @csrf

            <div class="field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required>
            </div>

            <div class="field">
                <label for="email">Account email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            </div>

            <div class="field">
                <label for="password">Temporary password</label>
                <input id="password" name="password" type="password" required>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required>
            </div>

            <div class="field">
                <label>Roles</label>
                @foreach ($roles as $role)
                    <label class="checkbox">
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(collect(old('roles', [config('security.permissions.default_user_role', 'user')]))->contains($role->name))>
                        {{ $role->name }}
                    </label>
                @endforeach
            </div>

            @if ($requiresApproval ?? false)
                <div class="field">
                    <label for="justification">Justification</label>
                    <textarea id="justification" name="justification" rows="3" required>{{ old('justification') }}</textarea>
                </div>
            @endif

            <button class="btn btn-primary" type="submit">Create user</button>
            <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users')) }}">Cancel</a>
        </form>
    </div>
</div>
