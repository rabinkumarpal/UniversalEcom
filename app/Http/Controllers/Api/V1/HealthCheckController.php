<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Registry\AddonRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthCheckController extends Controller
{
    public function __construct(
        protected AddonRegistry $addonRegistry
    ) {}

    public function __invoke(): JsonResponse
    {
        $startTime = microtime(true);
        $checks = [];
        $isHealthy = true;

        // 1. Database Probe
        try {
            $dbStart = microtime(true);
            DB::connection()->getPdo();
            $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);
            $checks['database'] = [
                'status' => 'healthy',
                'latency_ms' => $dbLatency,
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $checks['database'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }

        // 2. Cache Probe
        try {
            $cacheKey = '_probe_'.time();
            Cache::put($cacheKey, 'probe_value', 10);
            $val = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            $checks['cache'] = [
                'status' => $val === 'probe_value' ? 'healthy' : 'degraded',
                'driver' => config('cache.default'),
            ];
        } catch (\Throwable $e) {
            $checks['cache'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }

        // 3. Storage Probe
        try {
            $testFile = '_health_probe_'.time().'.txt';
            Storage::disk('local')->put($testFile, 'ok');
            $readBack = Storage::disk('local')->get($testFile);
            Storage::disk('local')->delete($testFile);

            $checks['storage'] = [
                'status' => $readBack === 'ok' ? 'healthy' : 'unhealthy',
                'driver' => config('filesystems.default'),
            ];
        } catch (\Throwable $e) {
            $checks['storage'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }

        // 4. Addons & Ecosystem
        $activeAddons = [];
        foreach ($this->addonRegistry->all() as $addon) {
            $activeAddons[] = [
                'id' => $addon->id(),
                'name' => $addon->name(),
                'version' => $addon->version(),
            ];
        }

        $totalDuration = round((microtime(true) - $startTime) * 1000, 2);

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'platform' => 'Universal Ecommerce Engine (Laravel '.app()->version().')',
            'php_version' => PHP_VERSION,
            'duration_ms' => $totalDuration,
            'checks' => $checks,
            'addons' => $activeAddons,
        ], $isHealthy ? 200 : 503);
    }
}
