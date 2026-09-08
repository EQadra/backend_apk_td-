<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// Spatie middlewares
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        // ✅ ALIAS PERSONALIZADOS (Spatie)
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // ✅ AGREGAR MIDDLEWARE VERIFY COOKIE TOKEN AL GRUPO API
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\VerifyCookieToken::class,
        ]);

        // ❌ ELIMINAR ESTA LÍNEA - NO USAS SANCTUM
        // $middleware->statefulApi();
        
        // ✅ CONFIGURAR CORS MANUALMENTE (OPCIONAL)
        // $middleware->validateCsrfTokens(except: [
        //     'api/*',
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();