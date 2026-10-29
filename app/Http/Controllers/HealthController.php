<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    use ApiResponse;

    /**
     * Health check and application details combined into a single response.
     */
    public function index(): JsonResponse
    {
        return $this->successResponse([
            'status'   => 'ok',
            'database' => $this->databaseStatus(),
            'application' => [
                'name'            => config('app.name'),
                'environment'     => app()->environment(),
                'version'         => config('app.version', '1.0.0'),
                'php_version'     => PHP_VERSION,
                'laravel_version' => app()->version(),
                'timezone'        => config('app.timezone'),
                'api_status'      => 'available',
            ],
        ], 'Service is healthy');
    }

    /**
     * Determine the database connectivity status.
     */
    private function databaseStatus(): string
    {
        try {
            DB::connection()->getPdo();

            return 'connected';
        } catch (\Throwable) {
            return 'disconnected';
        }
    }
}
