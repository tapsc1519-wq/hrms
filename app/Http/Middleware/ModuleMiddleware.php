<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleMiddleware
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role === 'super_admin') {
            return $next($request);
        }

        abort_if(!$user->organization || !$user->organization->hasModule($module), 403, 'This module is not enabled for your organization.');
        abort_if(!$user->organization->hasBillingAccess(), 402, $user->organization->billingAccessMessage());

        return $next($request);
    }
}
