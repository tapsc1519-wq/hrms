<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user?->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs('account.password.edit', 'account.password.update', 'logout')) {
            return $next($request);
        }

        return redirect()
            ->route('account.password.edit')
            ->with('warning', 'Please set your own password before continuing.');
    }
}
