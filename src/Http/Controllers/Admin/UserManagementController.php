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
use Pitbphp\Security\Support\PasswordStrength;
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
            'suggestedPassword' => PasswordStrength::suggestedTemporaryPassword(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:'.config('security.user.table', 'users').',email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'mfa_email' => ['nullable', 'email', 'max:255'],
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

        if (filled($validated['phone'] ?? null)) {
            $payload['phone'] = $validated['phone'];
        }

        if (filled($validated['mfa_email'] ?? null)) {
            $payload['mfa_email'] = $validated['mfa_email'];
        }

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:'.config('security.user.table', 'users').',email,'.$record->getKey()],
            'phone' => ['nullable', 'string', 'max:30'],
            'mfa_email' => ['nullable', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'confirmed', new PitbPassword($record)],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
            'access_expires_at' => ['nullable', 'date'],
            'must_change_password' => ['nullable', 'boolean'],
            'justification' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($request->user()->cannot('users.update')) {
            unset($validated['name'], $validated['email'], $validated['phone'], $validated['mfa_email'], $validated['password']);
        }

        if ($request->user()->cannot('roles.manage')) {
            unset($validated['roles']);
        }

        if ($request->user()->cannot('users.disable')) {
            unset($validated['is_active']);
        }

        if ($request->user()->cannot('users.update')) {
            unset($validated['access_expires_at'], $validated['must_change_password']);
        }

        $canChangeAnything = $request->user()->can('users.update')
            || $request->user()->can('roles.manage')
            || $request->user()->can('users.disable');

        if (! $canChangeAnything) {
            return back()->withErrors(['email' => 'You are not allowed to update this user.']);
        }

        if (isset($validated['roles'])
            && method_exists($request->user(), 'hasRole')
            && ! $request->user()->hasRole('super-admin')
            && in_array('super-admin', $validated['roles'], true)) {
            return back()->withErrors(['roles' => 'Only a super-admin may assign the super-admin role.']);
        }

        $payload = $this->buildUserPayload($request, $validated, $record);

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

        $this->provisioning->updateUser($record, $payload);

        return redirect()
            ->route(SecurityRoutes::adminName('partials.users'))
            ->with('status', 'User updated successfully.');
    }

    protected function buildUserPayload(Request $request, array $validated, $record = null): array
    {
        $payload = [];

        if (array_key_exists('name', $validated)) {
            $payload['name'] = $validated['name'];
        }

        if (array_key_exists('email', $validated)) {
            $payload['email'] = $validated['email'];
        }

        if (array_key_exists('phone', $validated)) {
            $payload['phone'] = $validated['phone'] ?: null;
        }

        if (array_key_exists('mfa_email', $validated)) {
            $payload['mfa_email'] = $validated['mfa_email'] ?: null;
        }

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
            $payload['password_changed_at'] = $request->boolean('must_change_password') ? null : now();
        }

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
}
