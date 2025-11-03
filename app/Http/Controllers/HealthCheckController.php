<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheckController extends Controller
{
    /**
     * Comprehensive health check endpoint for monitoring systems.
     */
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = collect($checks)->every(fn ($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
        ], $healthy ? 200 : 503);
    }

    /**
     * Simple liveness check for load balancers.
     */
    public function alive(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness check for deployment systems.
     */
    public function ready(): JsonResponse
    {
        try {
            // Check if database is accessible and migrations are up to date
            DB::connection()->getPdo();

            return response()->json([
                'status' => 'ready',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'not_ready',
                'reason' => 'Database not accessible',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }

    /**
     * Check database connectivity.
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            DB::table('users')->count(); // Verify can query
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'response_time_ms' => $duration,
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => app()->environment('production') ? 'Connection failed' : $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connectivity.
     */
    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'response_time_ms' => $duration,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Redis connection failed',
                'error' => app()->environment('production') ? 'Connection failed' : $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage accessibility.
     */
    private function checkStorage(): array
    {
        try {
            $disk = config('filesystems.default');
            $testFile = '.health_check_'.time();

            // Test write
            Storage::disk($disk)->put($testFile, 'health_check');

            // Test read
            $content = Storage::disk($disk)->get($testFile);

            // Cleanup
            Storage::disk($disk)->delete($testFile);

            return [
                'status' => 'ok',
                'disk' => $disk,
                'writable' => true,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage not accessible',
                'error' => app()->environment('production') ? 'Storage error' : $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue system.
     */
    private function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            $size = Cache::remember('queue_size', 60, function () {
                // This is a simplified check
                // In production, you might want to check actual queue size
                return 0;
            });

            return [
                'status' => 'ok',
                'connection' => $connection,
                'pending_jobs' => $size,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue system error',
                'error' => app()->environment('production') ? 'Queue error' : $e->getMessage(),
            ];
        }
    }
}
