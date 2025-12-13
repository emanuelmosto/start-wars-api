<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        $stats = Cache::get('search_stats:latest');

        if ($stats === null) {
            return response()->json([
                'generated_at' => null,
                'total_queries' => 0,
                'top_queries' => [],
                'avg_duration_ms' => null,
                'popular_hour' => null,
            ]);
        }

        return response()->json($stats);
    }
}
