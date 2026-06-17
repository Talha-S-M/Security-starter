@include('security::admin.partials.page-open', [
    'title' => 'Create user',
    'subtitle' => ($requiresApproval ?? false) ? 'This request will be sent for super-admin approval.' : 'Provision a new account with roles and a temporary password.',
])

<div class="card form-card">
    <form method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users.store')) }}">
        @csrf

        <section class="form-section">
            <h2 class="form-section__title">Account details</h2>

            <div class="field">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required>
            </div>

            <div class="field">
                <label for="email">Account email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            </div>

            @include('security::auth.partials.password-fields', [
                'passwordLabel' => 'Temporary password',
            ])
        </section>

        <section class="form-section">
            <h2 class="form-section__title">Roles</h2>
            <p class="form-section__desc">Select the roles this user should have after provisioning.</p>

            <div class="choice-grid">
                @foreach ($roles as $role)
                    <label class="choice-item">
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(collect(old('roles', [config('security.permissions.default_user_role', 'user')]))->contains($role->name))>
                        <span>{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
        </section>

        @if ($requiresApproval ?? false)
            <section class="form-section">
                <h2 class="form-section__title">Approval</h2>
                <div class="field">
                    <label for="justification">Justification</label>
                    <textarea id="justification" name="justification" rows="3" required>{{ old('justification') }}</textarea>
                </div>
            </section>
        @endif

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Create user</button>
            <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users')) }}">Cancel</a>
        </div>
    </form>
</div>

@include('security::admin.partials.page-close')
