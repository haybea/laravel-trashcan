@extends('trashcan::layouts.app-' . config('trashcan.css_framework', 'bootstrap'))
@section('content')
@include('trashcan::partials.' . config('trashcan.css_framework') . '.sidebar')
@php $bs = config('trashcan.css_framework') === 'bootstrap'; @endphp

@if($bs)
<div class="main-content p-4">
    <h1 class="h3 mb-4"><i class="bi bi-speedometer2 text-secondary me-2"></i>Dashboard</h1>
    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body d-flex align-items-center"><div class="stat-icon bg-primary bg-opacity-10 text-primary me-3"><i class="bi bi-trash3 fs-4"></i></div><div><h3 class="mb-0">{{ number_format($stats['total_trashed']) }}</h3><small class="text-muted">Total Trashed</small></div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body d-flex align-items-center"><div class="stat-icon bg-success bg-opacity-10 text-success me-3"><i class="bi bi-folder fs-4"></i></div><div><h3 class="mb-0">{{ $stats['total_models'] }}</h3><small class="text-muted">Models</small></div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body d-flex align-items-center"><div class="stat-icon bg-info bg-opacity-10 text-info me-3"><i class="bi bi-arrow-counterclockwise fs-4"></i></div><div><h3 class="mb-0">{{ number_format($stats['recent_activity']['restored'] ?? 0) }}</h3><small class="text-muted">Restored (30d)</small></div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body d-flex align-items-center"><div class="stat-icon bg-danger bg-opacity-10 text-danger me-3"><i class="bi bi-x-circle fs-4"></i></div><div><h3 class="mb-0">{{ number_format($stats['recent_activity']['deleted'] ?? 0) }}</h3><small class="text-muted">Deleted (30d)</small></div></div></div></div>
    </div>
    <div class="row g-4">
        <div class="col-lg-8"><div class="card shadow-sm"><div class="card-header bg-transparent border-0 pt-3"><h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Activity (30 Days)</h5></div><div class="card-body"><canvas id="activityChart" height="100"></canvas></div></div></div>
        <div class="col-lg-4"><div class="card shadow-sm"><div class="card-header bg-transparent border-0 pt-3"><h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>By Model</h5></div><div class="card-body">@foreach($stats['by_model']->take(5) as $m)<div class="d-flex justify-content-between mb-2"><span>{{ $m['name'] }}</span><span class="badge bg-primary">{{ $m['percentage'] }}%</span></div><div class="progress mb-3" style="height:6px"><div class="progress-bar" style="width:{{ $m['percentage'] }}%"></div></div>@endforeach</div></div></div>
    </div>
</div>
@else
<main class="ml-72 min-h-screen p-8">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white mb-6"><i class="ri-dashboard-line text-gray-400 mr-2"></i>Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6"><div class="flex items-center"><div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center mr-4"><i class="ri-delete-bin-line text-2xl"></i></div><div><h3 class="text-2xl font-bold dark:text-white">{{ number_format($stats['total_trashed']) }}</h3><p class="text-sm text-gray-500">Total Trashed</p></div></div></div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6"><div class="flex items-center"><div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 text-green-600 flex items-center justify-center mr-4"><i class="ri-folder-line text-2xl"></i></div><div><h3 class="text-2xl font-bold dark:text-white">{{ $stats['total_models'] }}</h3><p class="text-sm text-gray-500">Models</p></div></div></div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6"><div class="flex items-center"><div class="w-12 h-12 rounded-xl bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 flex items-center justify-center mr-4"><i class="ri-arrow-go-back-line text-2xl"></i></div><div><h3 class="text-2xl font-bold dark:text-white">{{ number_format($stats['recent_activity']['restored'] ?? 0) }}</h3><p class="text-sm text-gray-500">Restored (30d)</p></div></div></div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6"><div class="flex items-center"><div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/30 text-red-600 flex items-center justify-center mr-4"><i class="ri-close-circle-line text-2xl"></i></div><div><h3 class="text-2xl font-bold dark:text-white">{{ number_format($stats['recent_activity']['deleted'] ?? 0) }}</h3><p class="text-sm text-gray-500">Deleted (30d)</p></div></div></div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6"><h5 class="font-semibold dark:text-white mb-4"><i class="ri-line-chart-line text-gray-400 mr-2"></i>Activity (30 Days)</h5><canvas id="activityChart" height="100"></canvas></div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6"><h5 class="font-semibold dark:text-white mb-4"><i class="ri-pie-chart-line text-gray-400 mr-2"></i>By Model</h5>@foreach($stats['by_model']->take(5) as $m)<div class="mb-4"><div class="flex justify-between mb-1"><span class="text-sm text-gray-700 dark:text-gray-300">{{ $m['name'] }}</span><span class="text-xs px-2 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-full">{{ $m['percentage'] }}%</span></div><div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2"><div class="bg-indigo-600 h-2 rounded-full" style="width:{{ $m['percentage'] }}%"></div></div></div>@endforeach</div>
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