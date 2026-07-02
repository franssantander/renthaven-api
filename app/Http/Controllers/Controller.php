<?php

namespace App\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

abstract class Controller
{
    /**
     * Return a standardized success JSON response.
     */
    protected function success(
        mixed $data = null,
        string $message = 'Request was successful.',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'data' => $data,
            'status' => $status,
            'message' => $message,
        ], $status);
    }

    /**
     * Return a standardized error JSON response.
     */
    protected function error(
        ?Throwable $exception = null,
        ?string $message = null,
        int $status = 500
    ): JsonResponse {
        $errorData = null;

        if ($exception instanceof ValidationException) {
            $status = 422;
            $message = $message ?? 'The given data was invalid.';
            $errorData = $exception->errors();
        } elseif ($exception instanceof AuthenticationException) {
            $status = 401;
            $message = $message ?? 'Unauthenticated.';
        } elseif ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            $status = 404;
            $message = $message ?? 'Resource not found.';
        } elseif ($exception) {
            $message = $message ?? $exception->getMessage() ?: 'Something went wrong.';
        }

        $payload = [
            'data' => $errorData,
            'status' => $status,
            'message' => $message ?? 'Something went wrong.',
        ];

        if (!App::environment('production') && $exception) {
            $payload['debug'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->take(10)->toArray(),
            ];
        }

        return response()->json($payload, $status);
    }
}