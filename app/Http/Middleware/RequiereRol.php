<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequiereRol
{
    /** Uso: ->middleware('rol:admin,operador') */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $u = $request->user();
        if (! $u || ! $u->activo) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Tu usuario está inactivo.']);
        }
        if ($roles && ! in_array($u->rol, $roles, true)) {
            abort(403, 'No tienes permiso para ver esta sección.');
        }
        return $next($request);
    }
}
