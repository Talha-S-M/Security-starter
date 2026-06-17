<?php

namespace Pitbphp\Security\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Pitbphp\Security\Models\AccessRequest;
use Pitbphp\Security\Services\AccessProvisioningService;
use Pitbphp\Security\Support\SecurityRoutes;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        protected AccessProvisioningService $provisioning
    ) {}

    public function index(): View
    {
        return view('security::admin.partials.roles-table', [
            'roles' => Role::with('permissions')->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, Role $role): View
    {
        return view('security::admin.partials.role-form', [
            'role' => $role,
            'permissions' => Permission::orderBy('name')->get(),
            'requiresApproval' => $this->provisioning->requiresApproval($request->user()),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'justification' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($role->name === 'super-admin') {
            return back()->withErrors(['role' => 'Super-admin permissions cannot be modified from the panel.']);
        }

        $payload = ['permissions' => $validated['permissions'] ?? []];

        if ($this->provisioning->requiresApproval($request->user())) {
            $request->validate(['justification' => ['required', 'string', 'max:1000']]);

            $this->provisioning->submit(
                $request->user(),
                AccessRequest::TYPE_ROLE_UPDATE,
                Role::class,
                (int) $role->getKey(),
                $payload,
                $validated['justification'] ?? null
            );

            return redirect()
                ->route(SecurityRoutes::adminName('partials.roles'))
                ->with('status', 'Permission changes submitted for super-admin approval.');
        }

        $this->provisioning->updateRolePermissions($role, $payload['permissions']);

        return redirect()
            ->route(SecurityRoutes::adminName('partials.roles'))
            ->with('status', 'Role permissions updated.');
    }
}
