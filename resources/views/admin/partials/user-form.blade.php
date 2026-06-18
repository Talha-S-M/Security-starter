@include('security::admin.partials.page-open', [
    'title' => 'Edit user',
    'subtitle' => $user->email,
])

<form class="card form-card" method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users.update'), $user) }}">
    @csrf
    @method('PUT')

    @can('users.update')
        <section class="form-section">
            <h2 class="form-section__title">Profile</h2>
            <p class="form-section__desc">Update the user's account identity and MFA contacts.</p>

            <div class="field">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="field">
                <label for="email">Account email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
            </div>

            <div class="field">
                <label for="phone">Phone number</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" placeholder="03XXXXXXXXX">
                <p class="field-hint">Used for SMS MFA when a number is present.</p>
            </div>

            <div class="field">
                <label for="mfa_email">MFA email</label>
                <input id="mfa_email" name="mfa_email" type="email" value="{{ old('mfa_email', $user->mfa_email) }}" placeholder="Optional separate email for OTP delivery">
            </div>

            @if (! empty($user->mfa_methods))
                <p class="field-hint">Enabled MFA methods: {{ implode(', ', $user->mfa_methods) }}</p>
            @endif
        </section>

        <section class="form-section">
            <h2 class="form-section__title">Password</h2>
            <p class="form-section__desc">Leave blank to keep the current password.</p>

            @include('security::auth.partials.password-fields', [
                'passwordLabel' => 'New password',
                'confirmationLabel' => 'Confirm new password',
                'passwordValue' => old('password'),
                'confirmationValue' => old('password_confirmation'),
                'showGeneratePassword' => true,
                'passwordRequired' => false,
                'confirmationRequired' => false,
            ])
        </section>
    @endcan

    @can('roles.manage')
        <section class="form-section">
            <h2 class="form-section__title">Roles</h2>
            <p class="form-section__desc">Assign one or more roles to control what this user can access.</p>

            <div class="choice-grid">
                @foreach ($roles as $role)
                    <label class="choice-item">
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked($user->hasRole($role->name))>
                        <span>{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
        </section>
    @endcan

    <section class="form-section">
        <h2 class="form-section__title">Account settings</h2>
        <p class="form-section__desc">Manage account status, access expiry, and password policy.</p>

        <div class="toggle-list">
            @can('users.disable')
                <div class="toggle-item">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked($user->is_active ?? true)>
                    <div>
                        <label class="toggle-item__label" for="is_active">Account active</label>
                        <p class="toggle-item__hint">Inactive users cannot sign in until re-enabled.</p>
                    </div>
                </div>
            @endcan

            @can('users.update')
                <div class="toggle-item">
                    <input type="hidden" name="must_change_password" value="0">
                    <input type="checkbox" id="must_change_password" name="must_change_password" value="1" @checked($user->must_change_password ?? false)>
                    <div>
                        <label class="toggle-item__label" for="must_change_password">Force password change on next login</label>
                        <p class="toggle-item__hint">User will be redirected to update their password after signing in.</p>
                    </div>
                </div>
            @endcan
        </div>

        @can('users.update')
            <div class="field" style="margin-top: 1rem; max-width: 20rem;">
                <label for="access_expires_at">Access expires at</label>
                <input type="date" id="access_expires_at" name="access_expires_at" value="{{ old('access_expires_at', optional($user->access_expires_at)->format('Y-m-d')) }}">
                <p class="field-hint">Leave empty for no expiry date.</p>
            </div>
        @endcan
    </section>

    @if ($requiresApproval ?? false)
        <section class="form-section">
            <h2 class="form-section__title">Approval</h2>
            <p class="form-section__desc">Changes require super-admin approval before they take effect.</p>

            <div class="field">
                <label for="justification">Justification</label>
                <textarea id="justification" name="justification" rows="3" required placeholder="Explain why these changes are needed">{{ old('justification') }}</textarea>
            </div>
        </section>
    @endif

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save changes</button>
        <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users')) }}">Cancel</a>
    </div>
</form>

@include('security::admin.partials.page-close')
