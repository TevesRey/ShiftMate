<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

$projectRoot = dirname(__DIR__);
$storagePath = (string) env(
    'LARAVEL_STORAGE_PATH',
    env('VERCEL') ? sys_get_temp_dir().'/shiftmate-storage' : $projectRoot.'/storage'
);

// Ensure Laravel's writable runtime paths exist. Vercel containers are
// stateless, so runtime files are placed under /tmp there; durable data must
// live in the configured database/object-storage service.
$runtimeDirectories = [
    $projectRoot.'/bootstrap/cache',
    $storagePath.'/app/private',
    $storagePath.'/app/public',
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/testing',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
];

foreach ($runtimeDirectories as $runtimeDirectory) {
    if (! is_dir($runtimeDirectory) && ! mkdir($runtimeDirectory, 0775, true) && ! is_dir($runtimeDirectory)) {
        throw new RuntimeException('Unable to create Laravel runtime directory: '.$runtimeDirectory);
    }
}

$app = Application::configure(basePath: $projectRoot)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Vercel terminates HTTPS before forwarding requests to the container.
        $middleware->trustProxies(at: '*');
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->create();

$app->useStoragePath($storagePath);

return $app;
