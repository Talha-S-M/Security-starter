<?php

namespace Pitbphp\Security\Support;

use Illuminate\Http\Request;

class RouteHelper
{
    public static function isAllowed(Request $request, array $routeNames): bool
    {
        $current = $request->route()?->getName();

        if (! $current) {
            return false;
        }

        return in_array($current, $routeNames, true);
    }
}
