<div class="pitb-security">
    @include('security::admin.partials.styles')

    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form class="card" method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users.update'), $user) }}">
        @csrf
        @method('PUT')

        <p class="muted">{{ $user->email }}</p>

        @can('roles.manage')
            <label>Roles</label>
            <div class="checkbox-group">
                @foreach ($roles as $role)
                    <label>
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked($user->hasRole($role->name))>
                        {{ $role->name }}
                    </label>
                @endforeach
            </div>
        @endcan

        @can('users.disable')
            <label>
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($user->is_active ?? true)>
                Account active
            </label>
        @endcan

        @can('users.update')
            <label for="access_expires_at">Access expires at</label>
            <input type="date" id="access_expires_at" name="access_expires_at" value="{{ optional($user->access_expires_at)->format('Y-m-d') }}">

            <label>
                <input type="hidden" name="must_change_password" value="0">
                <input type="checkbox" name="must_change_password" value="1" @checked($user->must_change_password ?? false)>
                Force password change on next login
            </label>
        @endcan

        @if ($requiresApproval ?? false)
            <label for="justification">Justification (required — changes need super-admin approval)</label>
            <textarea id="justification" name="justification" rows="3" required>{{ old('justification') }}</textarea>
        @endif

        <div style="margin-top: .75rem;">
            <button class="btn btn-primary" type="submit">Save</button>
            <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users')) }}">Back</a>
        </div>
    </form>
</div>
