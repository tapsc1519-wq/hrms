<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!in_array($user->role, $roles, true) && !$this->canPassByRoutePermission($request)) {
            abort(403, 'Unauthorized. You do not have permission to access this area.');
        }

        if ($user->status === 'inactive') {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        return $next($request);
    }

    private function canPassByRoutePermission(Request $request): bool
    {
        $user = auth()->user();

        if (!$user?->isStaff()) {
            return false;
        }

        foreach ($request->route()?->gatherMiddleware() ?? [] as $middleware) {
            if (!is_string($middleware) || !str_starts_with($middleware, 'permission:')) {
                continue;
            }

            $permission = substr($middleware, strlen('permission:'));

            if ($permission !== '' && $user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
