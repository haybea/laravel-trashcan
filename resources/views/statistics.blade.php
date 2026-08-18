@extends('trashcan::layouts.app-' . config('trashcan.css_framework', 'bootstrap'))
@section('content')
@include('trashcan::partials.' . config('trashcan.css_framework') . '.sidebar')
@php
    $bs = config('trashcan.css_framework') === 'bootstrap';
    $trend = $stats['deletion_trend'];
    $trendIcon = $trend['trend'] === 'up' ? ($bs ? 'bi-arrow-up-short' : 'ri-arrow-up-line') : ($trend['trend'] === 'down' ? ($bs ? 'bi-arrow-down-short' : 'ri-arrow-down-line') : ($bs ? 'bi-dash' : 'ri-subtract-line'));
    $trendColor = $trend['trend'] === 'up' ? 'danger' : ($trend['trend'] === 'down' ? 'success' : 'secondary');
@endphp

@if($bs)
<div class="main-content p-4">
    <h1 class="h3 mb-4"><i class="bi bi-bar-chart text-secondary me-2"></i>Statistics</h1>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3"><h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Activity ({{ config('trashcan.statistics.chart_days', 30) }} Days)</h5></div>
                <div class="card-body"><canvas id="activityChart" height="90"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3"><h5 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>7-Day Trend</h5></div>
                <div class="card-body">
                    <h2 class="mb-0 text-{{ $trendColor }}"><i class="bi {{ $trendIcon }}"></i>{{ number_format(abs($trend['change']), 1) }}%</h2>
                    <p class="text-muted mb-3">vs. previous 7 days</p>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Last 7 days: <strong class="text-body">{{ $trend['current'] }}</strong></span>
                        <span>Prior 7 days: <strong class="text-body">{{ $trend['previous'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3"><h5 class="mb-0"><i class="bi bi-folder me-2"></i>Trash by Model</h5></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Model</th><th>Table</th><th class="text-end">Trashed</th><th class="text-end">Share</th></tr></thead>
                        <tbody>
                        @forelse($stats['by_model'] as $m)
                            <tr>
                                <td>{{ $m['name'] }}</td>
                                <td class="text-muted">{{ $m['table'] }}</td>
                                <td class="text-end">{{ number_format($m['count']) }}</td>
                                <td class="text-end" style="width:140px">
                                    <div class="d-flex align-items-center gap-2 justify-content-end">
                                        <div class="progress flex-grow-1" style="height:6px;max-width:70px"><div class="progress-bar" style="width:{{ $m['percentage'] }}%"></div></div>
                                        <span class="text-muted small">{{ $m['percentage'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No trashed records yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3"><h5 class="mb-0"><i class="bi bi-people me-2"></i>Top Deleters (30 Days)</h5></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>User</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        @forelse($stats['top_deleters'] as $deleter)
                            <tr><td>{{ $deleter->user_name ?? 'Unknown' }}</td><td class="text-end">{{ number_format($deleter->total_actions) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">No activity logged yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<main class="ml-72 min-h-screen p-8">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white mb-6"><i class="ri-bar-chart-line text-gray-400 mr-2"></i>Statistics</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6">
            <h5 class="font-semibold dark:text-white mb-4"><i class="ri-line-chart-line text-gray-400 mr-2"></i>Activity ({{ config('trashcan.statistics.chart_days', 30) }} Days)</h5>
            <canvas id="activityChart" height="90"></canvas>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6">
            <h5 class="font-semibold dark:text-white mb-4"><i class="ri-exchange-line text-gray-400 mr-2"></i>7-Day Trend</h5>
            <h2 class="text-3xl font-bold {{ $trend['trend'] === 'up' ? 'text-red-500' : ($trend['trend'] === 'down' ? 'text-emerald-500' : 'text-gray-500') }}">
                <i class="{{ $trendIcon }}"></i>{{ number_format(abs($trend['change']), 1) }}%
            </h2>
            <p class="text-sm text-gray-500 mb-4">vs. previous 7 days</p>
            <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>Last 7d: <strong class="text-gray-700 dark:text-gray-200">{{ $trend['current'] }}</strong></span>
                <span>Prior 7d: <strong class="text-gray-700 dark:text-gray-200">{{ $trend['previous'] }}</strong></span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <h5 class="font-semibold dark:text-white p-6 pb-0"><i class="ri-folder-line text-gray-400 mr-2"></i>Trash by Model</h5>
            <table class="w-full mt-4">
                <thead class="border-b dark:border-slate-700"><tr><th class="p-4 text-left text-sm font-medium text-gray-600 dark:text-gray-300">Model</th><th class="p-4 text-right text-sm font-medium text-gray-600 dark:text-gray-300">Trashed</th><th class="p-4 text-right text-sm font-medium text-gray-600 dark:text-gray-300">Share</th></tr></thead>
                <tbody class="divide-y dark:divide-slate-700">
                @forelse($stats['by_model'] as $m)
                    <tr>
                        <td class="p-4 text-sm text-gray-700 dark:text-gray-300">{{ $m['name'] }}</td>
                        <td class="p-4 text-right text-sm text-gray-700 dark:text-gray-300">{{ number_format($m['count']) }}</td>
                        <td class="p-4 text-right text-sm text-gray-500">{{ $m['percentage'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="p-6 text-center text-gray-400">No trashed records yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <h5 class="font-semibold dark:text-white p-6 pb-0"><i class="ri-team-line text-gray-400 mr-2"></i>Top Deleters (30 Days)</h5>
            <table class="w-full mt-4">
                <thead class="border-b dark:border-slate-700"><tr><th class="p-4 text-left text-sm font-medium text-gray-600 dark:text-gray-300">User</th><th class="p-4 text-right text-sm font-medium text-gray-600 dark:text-gray-300">Actions</th></tr></thead>
                <tbody class="divide-y dark:divide-slate-700">
                @forelse($stats['top_deleters'] as $deleter)
                    <tr>
                        <td class="p-4 text-sm text-gray-700 dark:text-gray-300">{{ $deleter->user_name ?? 'Unknown' }}</td>
                        <td class="p-4 text-right text-sm text-gray-700 dark:text-gray-300">{{ number_format($deleter->total_actions) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="p-6 text-center text-gray-400">No activity logged yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const d = @json($stats['activity_chart']);
    new Chart(document.getElementById('activityChart'), {
        type: 'line', data: { labels: d.labels, datasets: [
            { label: 'Restored', data: d.restored, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4 },
            { label: 'Deleted', data: d.deleted, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.4 }
        ]}, options: { responsive: true, plugins: { legend: { position: 'bottom' }}, scales: { y: { beginAtZero: true }}}
    });
});
</script>
@endsection
