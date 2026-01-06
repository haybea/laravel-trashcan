<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Trashcan - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root { --sidebar-width: 280px; }
        [data-bs-theme="dark"] { --bs-body-bg: #0f172a; --bs-body-color: #e2e8f0; }
        body { min-height: 100vh; }
        .sidebar { width: var(--sidebar-width); position: fixed; top: 0; left: 0; height: 100vh; overflow-y: auto; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); z-index: 1000; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .sidebar .nav-link { color: rgba(255,255,255,0.7); border-radius: 8px; margin: 2px 8px; transition: all 0.2s; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar .nav-link.active { background: rgba(99, 102, 241, 0.8); color: #fff; }
        .trash-count { font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; background: rgba(255,255,255,0.15); }
        .btn-restore { background: #10b981; border-color: #10b981; }
        .btn-restore:hover { background: #059669; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .theme-toggle { cursor: pointer; padding: 8px; border-radius: 8px; }
        .theme-toggle:hover { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dm = '{{ config("trashcan.dark_mode", "toggle") }}';
        function getTheme() { return dm === 'dark' ? 'dark' : dm === 'light' ? 'light' : dm === 'auto' ? (matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light') : (localStorage.getItem('trashcan-theme') || 'light'); }
        function setTheme(t) { document.documentElement.setAttribute('data-bs-theme', t); localStorage.setItem('trashcan-theme', t); const i = document.getElementById('themeIcon'); if(i) i.className = t === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill'; }
        function toggleTheme() { setTheme(document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark'); }
        setTheme(getTheme());
        function toggleAll(s) { document.querySelectorAll('.item-checkbox').forEach(c => c.checked = s.checked); updateBulkBtns(); }
        function updateBulkBtns() { const n = document.querySelectorAll('.item-checkbox:checked').length; document.querySelectorAll('.bulk-btn').forEach(b => b.disabled = n === 0); const c = document.getElementById('selectedCount'); if(c) c.textContent = n > 0 ? `(${n})` : ''; }
        function getSelectedIds() { return [...document.querySelectorAll('.item-checkbox:checked')].map(c => +c.value); }
        document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('.item-checkbox').forEach(c => c.addEventListener('change', updateBulkBtns)));
    </script>
</body>
</html>