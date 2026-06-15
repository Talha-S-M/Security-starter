<div class="pitb-security">
    @include('security::admin.partials.styles')

    <div class="card">
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
                        <td>{{ $permission->guard_name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="muted">No permissions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
