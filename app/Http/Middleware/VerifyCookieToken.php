<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VerifyCookieToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // ✅ VERIFICAR TOKEN EN COOKIE O HEADER
        $token = $request->cookie('token') ?? $request->bearerToken();
        
        if ($token) {
            try {
                // ✅ ESTABLECER EL TOKEN EN EL GUARD
                auth()->setToken($token);
                
                // ✅ VERIFICAR SI EL TOKEN ES VÁLIDO
                if (auth()->check()) {
                    Log::info('🟢 Token verificado correctamente', [
                        'user_id' => auth()->id(),
                        'url' => $request->fullUrl(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Token inválido', [
                    'error' => $e->getMessage(),
                    'url' => $request->fullUrl(),
                ]);
                // No hacer nada, dejar que el middleware de auth maneje el error
            }
        }

        return $next($request);
    }
}