<?php

namespace App\Support\Http;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => (object) $meta,
            'errors' => [],
        ], $status);
    }

    public static function error(string $message, int $status, array $errors = [], array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => (object) array_merge(['status' => $status], $meta),
            'errors' => $errors !== [] ? $errors : [
                [
                    'message' => $message,
                ],
            ],
        ], $status);
    }

    public static function validation(array $errors): JsonResponse
    {
        $formatted = [];

        foreach ($errors as $field => $messages) {
            foreach ((array) $messages as $message) {
                $formatted[] = [
                    'field' => $field,
                    'message' => $message,
                ];
            }
        }

        return self::error('Dados inválidos.', 422, $formatted);
    }
}
