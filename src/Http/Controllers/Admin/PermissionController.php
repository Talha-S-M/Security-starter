<?php

namespace Pitbphp\Security\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(): View
    {
        return view('security::admin.partials.permissions-table', [
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }
}
