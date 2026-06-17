@include('security::admin.partials.page-open', ['title' => 'Permissions', 'subtitle' => 'All permissions available in the RBAC system'])

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Guard</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($permissions as $permission)
                    <tr>
                        <td>{{ $permission->name }}</td>
                        <td><span class="badge badge-neutral">{{ $permission->guard_name }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="2"><div class="empty-state">No permissions found.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('security::admin.partials.page-close')
