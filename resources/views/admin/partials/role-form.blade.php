@include('security::admin.partials.page-open', [
    'title' => 'Edit role',
    'subtitle' => $role->name,
])

<form class="card form-card" method="POST" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.roles.update'), $role) }}">
    @csrf
    @method('PUT')

    <section class="form-section">
        <h2 class="form-section__title">Permissions</h2>
        <p class="form-section__desc">Choose which permissions are granted to users with this role.</p>

        <div class="choice-grid">
            @foreach ($permissions as $permission)
                <label class="choice-item">
                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($role->hasPermissionTo($permission->name))>
                    <span>{{ $permission->name }}</span>
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
        <button class="btn btn-primary" type="submit">Save changes</button>
        <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.roles')) }}">Cancel</a>
    </div>
</form>

@include('security::admin.partials.page-close')
