<?php

namespace App\Http\Middleware;

use App\Support\PortalContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SharePortalContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = PortalContext::fromRequest($request);

        app()->instance(PortalContext::class, $context);
        View::share('portalContext', $context->toArray());

        return $next($request);
    }
}
