<?php

namespace Zakirjarir\RbacAutomator\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (!$request->user() || !$request->user()->hasModule($module)) {
            abort(403, 'Unauthorized module access.');
        }

        return $next($request);
    }
}
