<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api/v1/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {

                // Check if this 404 was caused by a specific Model missing
                if ($e->getPrevious() instanceof ModelNotFoundException) {
                    $modelException = $e->getPrevious();
                    $modelName = class_basename($modelException->getModel());
                    $readableName = Str::headline($modelName);

                    return response()->json([
                        'success' => false,
                        'message' => "The requested {$readableName} details could not be found.",
                        'errors' => null,
                        'status' => 404
                    ], 404);
                }

                // Standard 404 (Wrong URL, Route doesn't exist, etc.)
                return response()->json([
                    'success' => false,
                    'message' => 'The requested endpoint could not be found.',
                    'errors' => null,
                    'status' => 404
                ], 404);
            }
        });

        // Handle Authenticated error
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'errors' => null,
                    'status' => 401
                ], 401);
            }
        });

        //  Handle Manual Action Errors (Status 400, 403, 404, etc.)
        // This handles your "throw new HttpException" from the Action
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => null,
                    'status' => $e->getStatusCode()
                ], $e->getStatusCode());
            }
        });


        // Handle Validation Errors (Status 422)
        // This replaces the code you had in "failedValidation"
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $e->errors(),
                    'status' => 422
                ], 422);
            }
        });

        // Handle Generic/Server Errors (Status 500)
        // This handles unexpected crashes (syntax errors, DB disconnects, etc.)
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                // In production, hide the real error message for security
                $message = config('app.debug') ? $e->getMessage() : 'Server Error';

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => config('app.debug') ? $e->getTrace() : null,
                    'status' => 500
                ], 500);
            }
        });
    })->create();
