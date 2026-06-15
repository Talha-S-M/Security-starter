<div class="pitb-security">
    @include('security::admin.partials.styles')

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Permissions</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $role)
                    <tr>
                        <td><strong>{{ $role->name }}</strong></td>
                        <td>{{ $role->permissions->pluck('name')->join(', ') }}</td>
                        <td>
                            @can('roles.manage')
                                @if ($role->name !== 'super-admin')
                                    <a href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.roles.edit'), $role) }}">Edit</a>
                                @else
                                    <span class="muted">Locked</span>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
