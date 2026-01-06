<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Trashcan - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>tailwind.config = { darkMode: 'class', theme: { extend: { colors: { primary: { 500: '#6366f1', 600: '#4f46e5' } } } } }</script>
    <style>.sidebar-gradient { background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); }</style>
</head>
<body class="bg-gray-50 dark:bg-slate-900 min-h-screen transition-colors">
    @yield('content')
    <script>
        const dm = '{{ config("trashcan.dark_mode", "toggle") }}';
        function getTheme() { return dm === 'dark' ? 'dark' : dm === 'light' ? 'light' : dm === 'auto' ? (matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light') : (localStorage.getItem('trashcan-theme') || 'light'); }
        function setTheme(t) { t === 'dark' ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark'); localStorage.setItem('trashcan-theme', t); const i = document.getElementById('themeIcon'); if(i) i.className = t === 'dark' ? 'ri-moon-fill text-xl' : 'ri-sun-fill text-xl'; }
        function toggleTheme() { setTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark'); }
        setTheme(getTheme());
        function toggleAll(s) { document.querySelectorAll('.item-checkbox').forEach(c => c.checked = s.checked); updateBulkBtns(); }
        function updateBulkBtns() { const n = document.querySelectorAll('.item-checkbox:checked').length; document.querySelectorAll('.bulk-btn').forEach(b => { b.disabled = n === 0; b.classList.toggle('opacity-50', n === 0); }); const c = document.getElementById('selectedCount'); if(c) c.textContent = n > 0 ? `(${n})` : ''; }
        function getSelectedIds() { return [...document.querySelectorAll('.item-checkbox:checked')].map(c => +c.value); }
        document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('.item-checkbox').forEach(c => c.addEventListener('change', updateBulkBtns)));
    </script>
</body>
</html>