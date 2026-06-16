<?php

namespace Pitbphp\Security\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Pitbphp\Security\Models\AccessRequest;
use Pitbphp\Security\Rules\PitbPassword;
use Pitbphp\Security\Services\AccessProvisioningService;
use Pitbphp\Security\Support\SecurityRoutes;

class UserManagementController extends Controller
{
    public function __construct(
        protected AccessProvisioningService $provisioning
    ) {}

    public function index(Request $request): View
    {
        $model = config('security.user.model');

        $users = (new $model)->newQuery()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) config('security.admin.per_page', 25))
            ->withQueryString();

        return view('security::admin.partials.users-table', [
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('security::admin.partials.user-create-form', [
            'roles' => \Spatie\Permission\Models\Role::orderBy('name')->get(),
            'requiresApproval' => $this->provisioning->requiresApproval($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:'.config('security.user.table', 'users').',email'],
            'password' => ['required', 'string', 'confirmed', new PitbPassword()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'justification' => ['nullable', 'string', 'max:1000'],
        ]);

        if (isset($validated['roles'])
            && method_exists($request->user(), 'hasRole')
            && ! $request->user()->hasRole('super-admin')
            && in_array('super-admin', $validated['roles'], true)) {
            return back()->withErrors(['roles' => 'Only a super-admin may assign the super-admin role.'])->withInput();
        }

        $payload = $this->provisioning->buildUserPayload(
            $validated['name'],
            $validated['email'],
            Hash::make($validated['password']),
            $validated['roles'] ?? []
        );

        if ($this->provisioning->requiresApproval($request->user())) {
            $request->validate(['justification' => ['required', 'string', 'max:1000']]);

            $this->provisioning->submit(
                $request->user(),
                AccessRequest::TYPE_USER_CREATE,
                config('security.user.model'),
                0,
                $payload,
                $validated['justification'] ?? null
            );

            return redirect()
                ->route(SecurityRoutes::adminName('partials.users'))
                ->with('status', 'User creation request submitted for approval.');
        }

        $this->provisioning->createUser($payload);

        return redirect()
            ->route(SecurityRoutes::adminName('partials.users'))
            ->with('status', 'User created successfully. They must change password and configure MFA on first login.');
    }

    public function edit(Request $request, int $user): View
    {
        $model = config('security.user.model');
        $record = (new $model)->newQuery()->findOrFail($user);

        return view('security::admin.partials.user-form', [
            'user' => $record,
            'roles' => \Spatie\Permission\Models\Role::orderBy('name')->get(),
            'requiresApproval' => $this->provisioning->requiresApproval($request->user()),
        ]);
    }

    public function update(Request $request, int $user): RedirectResponse
    {
        $model = config('security.user.model');
        $record = (new $model)->newQuery()->findOrFail($user);

        $validated = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
            'access_expires_at' => ['nullable', 'date'],
            'must_change_password' => ['nullable', 'boolean'],
            'justification' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($request->user()->cannot('roles.manage')) {
            unset($validated['roles']);
        }

        if ($request->user()->cannot('users.disable')) {
            unset($validated['is_active']);
        }

        if ($request->user()->cannot('users.update')) {
            return back()->withErrors(['email' => 'You are not allowed to update this user.']);
        }

        if (isset($validated['roles'])
            && method_exists($request->user(), 'hasRole')
            && ! $request->user()->hasRole('super-admin')
            && in_array('super-admin', $validated['roles'], true)) {
            return back()->withErrors(['roles' => 'Only a super-admin may assign the super-admin role.']);
        }

        $payload = $this->buildUserPayload($request, $validated);

        if ($payload === []) {
            return back()->with('status', 'No changes to save.');
        }

        if ($this->provisioning->requiresApproval($request->user())) {
            $request->validate(['justification' => ['required', 'string', 'max:1000']]);

            $this->provisioning->submit(
                $request->user(),
                AccessRequest::TYPE_USER_UPDATE,
                config('security.user.model'),
                (int) $record->getKey(),
                $payload,
                $validated['justification'] ?? null
            );

            return redirect()
                ->route(SecurityRoutes::adminName('partials.users'))
                ->with('status', 'Changes submitted for super-admin approval.');
        }

        $this->applyUserChanges($record, $payload);

        return redirect()
            ->route(SecurityRoutes::adminName('partials.users'))
            ->with('status', 'User updated successfully.');
    }

    protected function buildUserPayload(Request $request, array $validated): array
    {
        $payload = [];

        if (array_key_exists('roles', $validated)) {
            $payload['roles'] = $validated['roles'] ?? [];
        }

        if (array_key_exists('is_active', $validated)) {
            $payload['is_active'] = $request->boolean('is_active');
        }

        if ($request->has('access_expires_at')) {
            $payload['access_expires_at'] = $validated['access_expires_at'] ?? null;
        }

        if (array_key_exists('must_change_password', $validated)) {
            $payload['must_change_password'] = $request->boolean('must_change_password');
        }

        return $payload;
    }

    protected function applyUserChanges($record, array $payload): void
    {
        if (isset($payload['roles']) && method_exists($record, 'syncRoles')) {
            $record->syncRoles($payload['roles']);
        }

        $updates = array_intersect_key($payload, array_flip([
            'is_active', 'access_expires_at', 'must_change_password',
        ]));

        if ($updates !== []) {
            $record->update($updates);
        }
    }
}
