<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalDomain
{
    public function handle(Request $request, Closure $next, string $portal): Response
    {
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        $targetDomain = $this->targetDomain($portal);

        if ($targetDomain === '' || Str::lower($request->getHost()) === Str::lower($targetDomain)) {
            return $next($request);
        }

        if (! $request->isMethodSafe()) {
            abort(409, 'Please retry this action from https://' . $targetDomain . '.');
        }

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

        return redirect()->to($scheme . '://' . $targetDomain . $request->getRequestUri());
    }

    private function targetDomain(string $portal): string
    {
        if ($portal === 'platform') {
            return (string) config('niyantron.platform_domain');
        }

        return (string) config("niyantron.products.{$portal}.domain");
    }
}
