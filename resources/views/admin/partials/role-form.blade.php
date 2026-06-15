<div class="pitb-security">
    @include('security::admin.partials.styles')

    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form class="card" method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.roles.update'), $role) }}">
        @csrf
        @method('PUT')

        <p><strong>{{ $role->name }}</strong></p>

        <label>Permissions</label>
        <div class="checkbox-group">
            @foreach ($permissions as $permission)
                <label>
                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($role->hasPermissionTo($permission->name))>
                    {{ $permission->name }}
                </label>
            @endforeach
        </div>

        @if ($requiresApproval ?? false)
            <label for="justification">Justification (required — changes need super-admin approval)</label>
            <textarea id="justification" name="justification" rows="3" required>{{ old('justification') }}</textarea>
        @endif

        <div style="margin-top: .75rem;">
            <button class="btn btn-primary" type="submit">Save</button>
            <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.roles')) }}">Back</a>
        </div>
    </form>
</div>
