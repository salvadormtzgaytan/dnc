<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class FilamentAdminMiddleware
{
    /**
     * Maneja la solicitud entrante.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $panelPath = Filament::getCurrentPanel()?->getPath() ?? 'admin';

        // Permitir acceso libre al login y logout del panel
        if ($request->is("{$panelPath}/login") || $request->is("{$panelPath}/logout")) {
            return $next($request);
        }

        // Si no hay sesión iniciada, redirigir a login
        if (!Auth::check()) {
            return redirect("/{$panelPath}/login");
        }

        $user = Auth::user();

        // Validar si el usuario está activo
        if (!$user->is_active) {
            Auth::logout();
            abort(403, 'Acceso denegado. Usuario inactivo.'); 
        }

        // 🚨 Validar si el usuario tiene algún permiso asignado
        if ($user->getAllPermissions()->isEmpty()) {
            Auth::logout();
            abort(403, 'No tienes permisos asignados para acceder al panel.');
        }

        return $next($request);
    }
}
