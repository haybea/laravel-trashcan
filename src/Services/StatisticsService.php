<?php

namespace Haybea\Trashcan\Services;

use Haybea\Trashcan\Models\TrashcanActivity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    public function __construct(protected ModelDiscoveryService $discoveryService) {}

    /**
     * Get complete dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        $models = $this->discoveryService->getModels();

        return [
            'total_trashed' => $models->sum('trashed_count'),
            'total_models' => $models->count(),
            'by_model' => $this->getStatsByModel($models),
            'recent_activity' => $this->getRecentActivityStats(),
            'activity_chart' => $this->getActivityChartData(),
            'top_deleters' => $this->getTopDeleters(),
            'deletion_trend' => $this->getDeletionTrend(),
        ];
    }

    /**
     * Get trash count by model with percentage.
     */
    protected function getStatsByModel(Collection $models): Collection
    {
        $total = $models->sum('trashed_count');

        return $models->map(function ($info, $class) use ($total) {
            return [
                'class' => $class,
                'name' => $info['name'],
                'table' => $info['table'],
                'count' => $info['trashed_count'],
                'percentage' => $total > 0 ? round(($info['trashed_count'] / $total) * 100, 1) : 0,
            ];
        })->sortByDesc('count')->values();
    }

    /**
     * Get recent activity summary.
     */
    protected function getRecentActivityStats(): array
    {
        if (! config('trashcan.logging.database')) {
            return [];
        }

        $days = config('trashcan.statistics.chart_days', 30);

        return [
            'restored' => TrashcanActivity::where('action', 'like', '%restored%')
                ->where('created_at', '>=', now()->subDays($days))
                ->sum('count'),
            'deleted' => TrashcanActivity::where('action', 'like', '%deleted%')
                ->where('created_at', '>=', now()->subDays($days))
                ->sum('count'),
            'exported' => TrashcanActivity::where('action', 'exported')
                ->where('created_at', '>=', now()->subDays($days))
                ->count(),
        ];
    }

    /**
     * Get activity data for chart (last X days).
     */
    public function getActivityChartData(): array
    {
        if (! config('trashcan.logging.database')) {
            return ['labels' => [], 'restored' => [], 'deleted' => []];
        }

        $days = config('trashcan.statistics.chart_days', 30);
        $startDate = now()->subDays($days - 1)->startOfDay();

        $activities = TrashcanActivity::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw("SUM(CASE WHEN action LIKE '%restored%' THEN count ELSE 0 END) as restored"),
            DB::raw("SUM(CASE WHEN action LIKE '%deleted%' OR action = 'emptied' THEN count ELSE 0 END) as deleted")
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $restored = [];
        $deleted = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            $labels[] = $date->format('M d');
            $restored[] = (int) ($activities[$dateStr]->restored ?? 0);
            $deleted[] = (int) ($activities[$dateStr]->deleted ?? 0);
        }

        return compact('labels', 'restored', 'deleted');
    }

    /**
     * Get top users who perform trash actions.
     */
    protected function getTopDeleters(int $limit = 5): Collection
    {
        if (! config('trashcan.logging.database')) {
            return collect();
        }

        return TrashcanActivity::select('user_id', 'user_name', DB::raw('SUM(count) as total_actions'))
            ->whereNotNull('user_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('user_id', 'user_name')
            ->orderByDesc('total_actions')
            ->limit($limit)
            ->get();
    }

    /**
     * Get deletion trend (comparison with previous period).
     */
    protected function getDeletionTrend(): array
    {
        $models = $this->discoveryService->getModels();

        // Count items deleted in last 7 days vs previous 7 days
        $recentCount = 0;
        $previousCount = 0;

        foreach ($models as $class => $info) {
            $recentCount += $class::onlyTrashed()
                ->where('deleted_at', '>=', now()->subDays(7))
                ->count();

            $previousCount += $class::onlyTrashed()
                ->whereBetween('deleted_at', [now()->subDays(14), now()->subDays(7)])
                ->count();
        }

        $change = $previousCount > 0
            ? round((($recentCount - $previousCount) / $previousCount) * 100, 1)
            : ($recentCount > 0 ? 100 : 0);

        return [
            'current' => $recentCount,
            'previous' => $previousCount,
            'change' => $change,
            'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get model-specific statistics.
     */
    public function getModelStats(string $modelClass): array
    {
        $total = $modelClass::onlyTrashed()->count();

        // Group by deletion date
        $byDate = $modelClass::onlyTrashed()
            ->select(DB::raw('DATE(deleted_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(30)
            ->get();

        // Oldest and newest
        $oldest = $modelClass::onlyTrashed()->oldest('deleted_at')->first();
        $newest = $modelClass::onlyTrashed()->latest('deleted_at')->first();

        return [
            'total' => $total,
            'by_date' => $byDate,
            'oldest' => $oldest?->deleted_at,
            'newest' => $newest?->deleted_at,
        ];
    }
}
