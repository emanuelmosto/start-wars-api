<?php

namespace App\Jobs;

use App\Models\SearchQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecomputeSearchStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $total = SearchQuery::count();

        $now = Carbon::now();

        if ($total === 0) {
            $stats = [
                'generated_at' => $now->toIso8601String(),
                'total_queries' => 0,
                'top_queries' => [],
                'avg_duration_ms' => null,
                'popular_hour' => null,
            ];

            Cache::forever('search_stats:latest', $stats);

            return;
        }

        $top = SearchQuery::select('query', 'type', DB::raw('COUNT(*) as count'))
            ->groupBy('query', 'type')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $topQueries = $top->map(static function (SearchQuery $row) use ($total): array {
            $count = (int) $row->count;

            return [
                'query' => $row->query,
                'type' => $row->type,
                'count' => $count,
                'percentage' => $total > 0 ? $count / $total : 0.0,
            ];
        })->all();

        $avgDuration = (float) SearchQuery::avg('duration_ms');

        $popular = SearchQuery::select(DB::raw('HOUR(performed_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('HOUR(performed_at)'))
            ->orderByDesc('count')
            ->orderBy('hour')
            ->limit(1)
            ->first();

        $popularHour = $popular ? (int) $popular->hour : null;

        $stats = [
            'generated_at' => $now->toIso8601String(),
            'total_queries' => $total,
            'top_queries' => $topQueries,
            'avg_duration_ms' => $avgDuration,
            'popular_hour' => $popularHour,
        ];

        Cache::forever('search_stats:latest', $stats);
    }
}
