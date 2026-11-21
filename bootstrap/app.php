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

// Keep writable runtime files in the configured path; container filesystems
// may be ephemeral, so durable data belongs in a database or object storage.
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
        // Trust the platform proxy so Laravel detects the original HTTPS request.
        $middleware->trustProxies(at: '*');
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Configure JSON responses for API errors.
        $exceptions->shouldRenderJsonWhen(
            // Keep all API responses in JSON format.
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->create();

$app->useStoragePath($storagePath);

return $app;
