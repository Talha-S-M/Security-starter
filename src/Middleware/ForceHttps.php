<?php

namespace Pitbphp\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.https.force') || $request->secure()) {
            return $next($request);
        }

        return redirect()->secure($request->getRequestUri(), 301);
    }
}
