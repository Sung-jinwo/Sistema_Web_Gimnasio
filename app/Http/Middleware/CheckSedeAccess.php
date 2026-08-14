<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSedeAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            abort(403, 'No autorizado.');
        }

        $user = $request->user();

        if ($user->hasRole('Administrador')) {
            return $next($request);
        }

        $route = $request->route();
        $params = $route?->parameters() ?? [];

        foreach ($params as $param) {
            if (is_object($param) && method_exists($param, 'fksede')) {
                if ($param->fksede && $param->fksede !== $user->fksede) {
                    abort(403, 'No tienes acceso a recursos de otra sede.');
                }
            }
        }

        return $next($request);
    }
}
