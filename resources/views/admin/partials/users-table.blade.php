@include('security::admin.partials.page-open', ['title' => 'Users', 'subtitle' => 'Manage accounts, roles, and access status'])

<form class="toolbar filters" method="GET" action="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users')) }}">
    <input type="text" name="search" placeholder="Search name or email" value="{{ $filters['search'] ?? '' }}">
    <button class="btn btn-primary" type="submit">Search</button>
    @can('users.create')
        <a class="btn btn-secondary" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users.create')) }}">Create user</a>
    @endcan
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name ?? '—' }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ method_exists($user, 'getRoleNames') ? $user->getRoleNames()->join(', ') : '—' }}</td>
                        <td>
                            <span class="badge {{ ($user->is_active ?? true) ? 'badge-success' : 'badge-danger' }}">
                                {{ ($user->is_active ?? true) ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td>
                            @can('users.update')
                                <a class="table-link" href="{{ route(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.users.edit'), $user) }}">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state">No users found.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $users->links() }}</div>
</div>

@include('security::admin.partials.page-close')
