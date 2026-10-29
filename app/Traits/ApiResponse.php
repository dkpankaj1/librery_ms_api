<?php

namespace App\Traits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Return a successful JSON response.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(
        string $message = 'Error',
        mixed $errors = null,
        int $code = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }

    /**
     * Return a paginated JSON response.
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = 'Success'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ], 200);
    }

    /**
     * Format validation errors for a failed FormRequest/Validator.
     */
    protected function validationErrorResponse(
        Validator $validator,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->errorResponse($message, $validator->errors(), 422);
    }
}
