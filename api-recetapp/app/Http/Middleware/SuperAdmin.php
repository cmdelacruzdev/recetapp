<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $superadminEmail = config('recetapp.superadmin_email');

        if (!$superadminEmail || $user->username !== $superadminEmail) {
            return response()->json(['error' => 'No tienes permisos de superadmin.'], 403);
        }

        return $next($request);
    }
}
