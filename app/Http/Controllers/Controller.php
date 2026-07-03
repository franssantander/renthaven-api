<?php

namespace App\Http\Controllers;

use App\Support\ApiResponder;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

abstract class Controller
{
    protected function success(
        mixed $data = null,
        string $message = 'Request was successful.',
        int $status = 200
    ): JsonResponse {
        return ApiResponder::success($data, $message, $status);
    }

    /**
     * Return a standardized error JSON response.
     */
    protected function error(
        ?Throwable $exception = null,
        ?string $message = null,
        int $status = 500
    ): JsonResponse {
        return ApiResponder::error($exception, $message, $status);
    }
}
