<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Always answer API requests with JSON, never an HTML error page.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $message = $e->getPrevious() instanceof ModelNotFoundException
                    ? 'The requested record was not found.'
                    : 'The requested endpoint does not exist.';

                return response()->json(['message' => $message], 404);
            }
        });

        // Deleting a record still referenced elsewhere (e.g. a branch with users).
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*') && $e->getCode() === '23000') {
                return response()->json([
                    'message' => 'This record is linked to other data and cannot be changed this way.',
                ], 409);
            }
        });
    })->create();
