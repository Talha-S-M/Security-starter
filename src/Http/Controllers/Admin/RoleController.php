<?php

namespace Pitbphp\Security\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Pitbphp\Security\Support\SecurityRoutes;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('security::admin.partials.roles-table', [
            'roles' => Role::with('permissions')->orderBy('name')->get(),
        ]);
    }

    public function edit(Role $role): View
    {
        return view('security::admin.partials.role-form', [
            'role' => $role,
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if ($role->name === 'super-admin') {
            return back()->withErrors(['role' => 'Super-admin permissions cannot be modified from the panel.']);
        }

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route(SecurityRoutes::adminName('partials.roles'))
            ->with('status', 'Role permissions updated.');
    }
}
