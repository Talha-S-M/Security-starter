@include('security::admin.partials.page-open', ['title' => 'Roles', 'subtitle' => 'Review role definitions and manage permission assignments'])

<div class="card">
    <div class="table-wrap">
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
                        <td class="muted">{{ $role->permissions->pluck('name')->join(', ') ?: '—' }}</td>
                        <td>
                            @can('roles.manage')
                                @if ($role->name !== 'super-admin')
                                    <a class="table-link" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.roles.edit'), $role) }}">Edit</a>
                                @else
                                    <span class="badge badge-neutral">Locked</span>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@include('security::admin.partials.page-close')
