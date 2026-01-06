<?php

namespace Haybea\Trashcan\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Haybea\Trashcan\Models\TrashcanActivity;

class StatisticsService
{
    public function __construct(protected ModelDiscoveryService $discovery) {}

    public function getDashboardStats(): array
    {
        $models = $this->discovery->getModels();
        return [
            'total_trashed' => $models->sum('trashed_count'),
            'total_models' => $models->count(),
            'by_model' => $this->getStatsByModel($models),
            'recent_activity' => $this->getRecentActivityStats(),
            'activity_chart' => $this->getActivityChartData(),
            'deletion_trend' => $this->getDeletionTrend($models),
        ];
    }

    protected function getStatsByModel(Collection $models): Collection
    {
        $total = $models->sum('trashed_count');
        return $models->map(fn ($info, $class) => [
            'class' => $class, 'name' => $info['name'], 'table' => $info['table'], 'count' => $info['trashed_count'],
            'percentage' => $total > 0 ? round(($info['trashed_count'] / $total) * 100, 1) : 0,
        ])->sortByDesc('count')->values();
    }

    protected function getRecentActivityStats(): array
    {
        if (!config('trashcan.logging.database')) return [];
        $days = config('trashcan.statistics.chart_days', 30);
        return [
            'restored' => TrashcanActivity::where('action', 'like', '%restored%')->where('created_at', '>=', now()->subDays($days))->sum('count'),
            'deleted' => TrashcanActivity::where('action', 'like', '%deleted%')->where('created_at', '>=', now()->subDays($days))->sum('count'),
        ];
    }

    public function getActivityChartData(): array
    {
        if (!config('trashcan.logging.database')) return ['labels' => [], 'restored' => [], 'deleted' => []];
        $days = config('trashcan.statistics.chart_days', 30);
        $start = now()->subDays($days - 1)->startOfDay();

        $activities = TrashcanActivity::select(DB::raw('DATE(created_at) as date'),
            DB::raw("SUM(CASE WHEN action LIKE '%restored%' THEN count ELSE 0 END) as restored"),
            DB::raw("SUM(CASE WHEN action LIKE '%deleted%' OR action = 'emptied' THEN count ELSE 0 END) as deleted")
        )->where('created_at', '>=', $start)->groupBy('date')->orderBy('date')->get()->keyBy('date');

        $labels = $restored = $deleted = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $start->copy()->addDays($i);
            $labels[] = $d->format('M d');
            $restored[] = (int) ($activities[$d->format('Y-m-d')]->restored ?? 0);
            $deleted[] = (int) ($activities[$d->format('Y-m-d')]->deleted ?? 0);
        }
        return compact('labels', 'restored', 'deleted');
    }

    protected function getDeletionTrend(Collection $models): array
    {
        $recent = $previous = 0;
        foreach ($models as $class => $info) {
            $recent += $class::onlyTrashed()->where('deleted_at', '>=', now()->subDays(7))->count();
            $previous += $class::onlyTrashed()->whereBetween('deleted_at', [now()->subDays(14), now()->subDays(7)])->count();
        }
        $change = $previous > 0 ? round((($recent - $previous) / $previous) * 100, 1) : ($recent > 0 ? 100 : 0);
        return ['current' => $recent, 'previous' => $previous, 'change' => $change, 'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')];
    }
}