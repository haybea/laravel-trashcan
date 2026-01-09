<div class="sidebar p-3 d-flex flex-column">
    <div class="d-flex align-items-center justify-content-between mb-4 px-2 pt-2">
        <div class="d-flex align-items-center">
            <i class="bi bi-trash3 text-white me-2" style="font-size: 1.75rem;"></i>
            <span class="brand-logo text-white">Trashcan</span>
        </div>
        @if(config('trashcan.dark_mode') === 'toggle')
            <div class="theme-toggle text-white" onclick="toggleTheme()" title="Toggle theme">
                <i id="themeIcon" class="bi bi-sun-fill"></i>
            </div>
        @endif
    </div>

    {{-- Dashboard Link --}}
    <nav class="nav flex-column mb-3">
        <a href="{{ route('trashcan.index') }}"
           class="nav-link d-flex align-items-center {{ request()->routeIs('trashcan.index') ? 'active' : '' }}">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
        @if(config('trashcan.statistics.enabled'))
            <a href="{{ route('trashcan.statistics') }}"
               class="nav-link d-flex align-items-center {{ request()->routeIs('trashcan.statistics') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line me-2"></i>Statistics
            </a>
        @endif
        @if(config('trashcan.logging.database'))
            <a href="{{ route('trashcan.activity') }}"
               class="nav-link d-flex align-items-center {{ request()->routeIs('trashcan.activity') ? 'active' : '' }}">
                <i class="bi bi-clock-history me-2"></i>Activity
            </a>
        @endif
    </nav>

    <div class="px-2 mb-2">
        <small class="text-uppercase text-white-50 fw-semibold" style="font-size: 0.7rem; letter-spacing: 1px;">
            Models
        </small>
    </div>

    <nav class="nav flex-column flex-grow-1">
        @forelse($models as $class => $info)
            @php $encoded = \Haybea\Trashcan\Http\Controllers\TrashcanController::encodeModelClass($class); @endphp
            <a href="{{ route('trashcan.show', $encoded) }}"
               class="nav-link d-flex justify-content-between align-items-center {{ (isset($modelClass) && $modelClass === $class) ? 'active' : '' }}">
                <span><i class="bi bi-folder2 me-2"></i>{{ $info['name'] }}</span>
                <span class="trash-count">{{ $info['trashed_count'] }}</span>
            </a>
        @empty
            <div class="text-white-50 px-3 py-2 small">
                <i class="bi bi-info-circle me-1"></i> No models found.
            </div>
        @endforelse
    </nav>

    <div class="mt-auto pt-3 px-2 border-top border-secondary">
        <small class="text-white-50 d-block">
            <i class="bi bi-clock me-1"></i> {{ now()->format('M d, Y H:i') }}
        </small>
        <small class="text-white-50">
            Laravel Trashcan v1.0
        </small>
    </div>
</div>