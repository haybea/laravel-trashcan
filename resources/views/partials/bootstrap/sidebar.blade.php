<div class="sidebar p-3 d-flex flex-column">
    <div class="d-flex align-items-center justify-content-between mb-4 px-2 pt-2">
        <div class="d-flex align-items-center"><i class="bi bi-trash3 text-white me-2" style="font-size:1.75rem"></i><span class="text-white fw-bold fs-4">Trashcan</span></div>
        @if(config('trashcan.dark_mode') === 'toggle')<div class="theme-toggle text-white" onclick="toggleTheme()"><i id="themeIcon" class="bi bi-sun-fill"></i></div>@endif
    </div>
    <nav class="nav flex-column mb-3">
        <a href="{{ route('trashcan.index') }}" class="nav-link {{ request()->routeIs('trashcan.index') ? 'active' : '' }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        @if(config('trashcan.statistics.enabled'))<a href="{{ route('trashcan.statistics') }}" class="nav-link {{ request()->routeIs('trashcan.statistics') ? 'active' : '' }}"><i class="bi bi-bar-chart-line me-2"></i>Statistics</a>@endif
        @if(config('trashcan.logging.database'))<a href="{{ route('trashcan.activity') }}" class="nav-link {{ request()->routeIs('trashcan.activity') ? 'active' : '' }}"><i class="bi bi-clock-history me-2"></i>Activity</a>@endif
    </nav>
    <div class="px-2 mb-2"><small class="text-uppercase text-white-50 fw-semibold" style="font-size:0.7rem">Models</small></div>
    <nav class="nav flex-column flex-grow-1">
        @foreach($models as $class => $info)
        <a href="{{ route('trashcan.show', base64_encode($class)) }}" class="nav-link d-flex justify-content-between {{ (isset($modelClass) && $modelClass === $class) ? 'active' : '' }}">
            <span><i class="bi bi-folder2 me-2"></i>{{ $info['name'] }}</span><span class="trash-count">{{ $info['trashed_count'] }}</span>
        </a>
        @endforeach
    </nav>
    <div class="mt-auto pt-3 px-2 border-top border-secondary"><small class="text-white-50">Laravel Trashcan v1.0</small></div>
</div>