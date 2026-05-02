<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    public function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'status' => $status,
        ];

        if ($data !== null) {
            $response = array_merge(
                $response,
                is_array($data) ? $data : $data->toArray()
            );
        }

        return response()->json($response, $status);
    }
}
