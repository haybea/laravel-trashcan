<aside class="fixed inset-y-0 left-0 w-72 sidebar-gradient flex flex-col">
    <div class="flex items-center justify-between p-6">
        <div class="flex items-center gap-3"><i class="ri-delete-bin-line text-3xl text-white"></i><span class="text-xl font-bold text-white">Trashcan</span></div>
        @if(config('trashcan.dark_mode') === 'toggle')<button onclick="toggleTheme()" class="p-2 rounded-lg text-white hover:bg-white/10"><i id="themeIcon" class="ri-sun-fill text-xl"></i></button>@endif
    </div>
    <nav class="px-3 space-y-1 mb-4">
        <a href="{{ route('trashcan.index') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-lg {{ request()->routeIs('trashcan.index') ? 'bg-primary-600 text-white' : 'text-slate-300 hover:bg-slate-700/50' }}"><i class="ri-dashboard-line"></i>Dashboard</a>
        @if(config('trashcan.statistics.enabled'))<a href="{{ route('trashcan.statistics') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-lg {{ request()->routeIs('trashcan.statistics') ? 'bg-primary-600 text-white' : 'text-slate-300 hover:bg-slate-700/50' }}"><i class="ri-bar-chart-2-line"></i>Statistics</a>@endif
        @if(config('trashcan.logging.database'))<a href="{{ route('trashcan.activity') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-lg {{ request()->routeIs('trashcan.activity') ? 'bg-primary-600 text-white' : 'text-slate-300 hover:bg-slate-700/50' }}"><i class="ri-time-line"></i>Activity</a>@endif
    </nav>
    <div class="px-6 mb-3"><span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Models</span></div>
    <nav class="px-3 space-y-1 flex-1 overflow-y-auto">
        @foreach($models as $class => $info)
        <a href="{{ route('trashcan.show', base64_encode($class)) }}" class="flex items-center justify-between px-4 py-2.5 rounded-lg {{ (isset($modelClass) && $modelClass === $class) ? 'bg-primary-600 text-white' : 'text-slate-300 hover:bg-slate-700/50' }}">
            <span class="flex items-center gap-2"><i class="ri-folder-line"></i>{{ $info['name'] }}</span>
            <span class="text-xs px-2 py-0.5 rounded-full {{ (isset($modelClass) && $modelClass === $class) ? 'bg-white/20' : 'bg-slate-700' }}">{{ $info['trashed_count'] }}</span>
        </a>
        @endforeach
    </nav>
    <div class="p-6 border-t border-slate-700"><span class="text-xs text-slate-500">Laravel Trashcan v1.0</span></div>
</aside>