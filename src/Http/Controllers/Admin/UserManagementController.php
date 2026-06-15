<?php

namespace Pitbphp\Security\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Pitbphp\Security\Support\SecurityRoutes;

class UserManagementController extends Controller
{
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

    public function edit(int $user): View
    {
        $model = config('security.user.model');
        $record = (new $model)->newQuery()->findOrFail($user);

        return view('security::admin.partials.user-form', [
            'user' => $record,
            'roles' => \Spatie\Permission\Models\Role::orderBy('name')->get(),
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

        if (array_key_exists('roles', $validated) && method_exists($record, 'syncRoles')) {
            $record->syncRoles($validated['roles'] ?? []);
        }

        $updates = [];

        if (array_key_exists('is_active', $validated)) {
            $updates['is_active'] = $request->boolean('is_active');
        }

        if ($request->has('access_expires_at')) {
            $updates['access_expires_at'] = $validated['access_expires_at'] ?? null;
        }

        if (array_key_exists('must_change_password', $validated)) {
            $updates['must_change_password'] = $request->boolean('must_change_password');
        }

        if ($updates !== []) {
            $record->update($updates);
        }

        return redirect()
            ->route(SecurityRoutes::adminName('partials.users'))
            ->with('status', 'User updated successfully.');
    }
}
