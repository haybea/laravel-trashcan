@extends('trashcan::layouts.app-' . config('trashcan.css_framework', 'bootstrap'))
@section('content')
@include('trashcan::partials.' . config('trashcan.css_framework') . '.sidebar')
@php $bs = config('trashcan.css_framework') === 'bootstrap'; @endphp
@if($bs)
<div class="main-content p-4">
    <h1 class="h3 mb-4"><i class="bi bi-clock-history text-secondary me-2"></i>Activity Log</h1>
    <div class="card shadow-sm border-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Action</th><th>Model</th><th>Count</th><th>User</th><th>Date</th></tr></thead><tbody>@forelse($activities as $a)<tr><td><span class="badge bg-{{ $a->action_color }}">{{ $a->action_label }}</span></td><td>{{ $a->model_name }}</td><td>{{ $a->count }}</td><td>{{ $a->user_name ?? 'System' }}</td><td class="text-muted small">{{ $a->created_at->diffForHumans() }}</td></tr>@empty<tr><td colspan="5" class="text-center py-4 text-muted">No activity</td></tr>@endforelse</tbody></table></div></div>
    <div class="mt-3">{{ $activities->links() }}</div>
</div>
@else
<main class="ml-72 min-h-screen p-8">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white mb-6"><i class="ri-time-line text-gray-400 mr-2"></i>Activity Log</h1>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden"><table class="w-full"><thead class="bg-gray-50 dark:bg-slate-700 border-b dark:border-slate-600"><tr><th class="p-4 text-left text-sm font-medium text-gray-600 dark:text-gray-300">Action</th><th class="p-4 text-left">Model</th><th class="p-4 text-left">Count</th><th class="p-4 text-left">User</th><th class="p-4 text-left">Date</th></tr></thead><tbody class="divide-y dark:divide-slate-700">@forelse($activities as $a)<tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50"><td class="p-4"><span class="px-2 py-1 text-xs font-medium rounded-full {{ $a->action_color==='success'?'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400':($a->action_color==='danger'?'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400':'bg-blue-100 text-blue-800') }}">{{ $a->action_label }}</span></td><td class="p-4 text-sm text-gray-700 dark:text-gray-300">{{ $a->model_name }}</td><td class="p-4 text-sm">{{ $a->count }}</td><td class="p-4 text-sm">{{ $a->user_name ?? 'System' }}</td><td class="p-4 text-sm text-gray-400">{{ $a->created_at->diffForHumans() }}</td></tr>@empty<tr><td colspan="5" class="p-8 text-center text-gray-400">No activity</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $activities->links() }}</div>
</main>
@endif
@endsection