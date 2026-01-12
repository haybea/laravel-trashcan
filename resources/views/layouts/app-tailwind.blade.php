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
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca' }
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar-gradient { background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); }
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body class="bg-gray-50 dark:bg-slate-900 min-h-screen transition-colors duration-300">
@yield('content')

<script>
    // Dark mode handling
    const darkMode = '{{ config("trashcan.dark_mode", "toggle") }}';

    function getPreferredTheme() {
        if (darkMode === 'dark') return 'dark';
        if (darkMode === 'light') return 'light';
        if (darkMode === 'auto') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        return localStorage.getItem('trash-theme') || 'light';
    }

    function setTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        localStorage.setItem('trash-theme', theme);
        updateThemeIcon(theme);
    }

    function updateThemeIcon(theme) {
        const icon = document.getElementById('themeIcon');
        if (icon) {
            icon.className = theme === 'dark' ? 'ri-moon-fill text-xl' : 'ri-sun-fill text-xl';
        }
    }

    function toggleTheme() {
        const isDark = document.documentElement.classList.contains('dark');
        setTheme(isDark ? 'light' : 'dark');
    }

    setTheme(getPreferredTheme());

    if (darkMode === 'auto') {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            setTheme(e.matches ? 'dark' : 'light');
        });
    }

    function toggleAll(source) {
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = source.checked);
        updateBulkButtons();
    }

    function updateBulkButtons() {
        const checked = document.querySelectorAll('.item-checkbox:checked').length;
        document.querySelectorAll('.bulk-btn').forEach(btn => {
            btn.disabled = checked === 0;
            btn.classList.toggle('opacity-50', checked === 0);
            btn.classList.toggle('cursor-not-allowed', checked === 0);
        });
        const counter = document.getElementById('selectedCount');
        if (counter) counter.textContent = checked > 0 ? `(${checked} selected)` : '';
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => parseInt(cb.value));
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.item-checkbox').forEach(cb =>
            cb.addEventListener('change', updateBulkButtons)
        );
    });
</script>
@stack('scripts')
</body>
</html>