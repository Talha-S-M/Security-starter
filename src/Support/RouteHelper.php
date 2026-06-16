<?php

namespace Pitbphp\Security\Support;

use Illuminate\Http\Request;

class RouteHelper
{
    public static function isAllowed(Request $request, array $routeNames): bool
    {
        $current = $request->route()?->getName();

        if ($current && in_array($current, $routeNames, true)) {
            return true;
        }

        foreach (config('security.session.allowed_paths', []) as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
