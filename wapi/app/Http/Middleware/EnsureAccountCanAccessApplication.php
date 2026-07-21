<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountCanAccessApplication
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (! $user->is_active) {
            abort(Response::HTTP_FORBIDDEN, __('Votre compte doit etre active avant d acceder a l application.'));
        }

        if (! $user->isSystemAdmin() && ! $user->belongsToActiveCabinet()) {
            abort(Response::HTTP_FORBIDDEN, __('Votre cabinet est suspendu ou indisponible.'));
        }

        return $next($request);
    }
}
