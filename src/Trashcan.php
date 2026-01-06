<?php

namespace Haybea\Trashcan;

use Illuminate\Contracts\Foundation\Application;

class Trashcan
{
    protected Application $app;
    protected static ?\Closure $authCallback = null;

    public function __construct(Application $app) { $this->app = $app; }

    public static function auth(\Closure $callback): void { static::$authCallback = $callback; }

    public static function check($request): bool
    {
        return (static::$authCallback ?: fn () => app()->environment(config('trashcan.allowed_environments', ['local'])))($request);
    }

    public static function cssFramework(): string { return config('trashcan.css_framework', 'bootstrap'); }
    public static function isBootstrap(): bool { return static::cssFramework() === 'bootstrap'; }
    public static function isTailwind(): bool { return static::cssFramework() === 'tailwind'; }
    public static function darkMode(): string { return config('trashcan.dark_mode', 'toggle'); }
    public static function hasDarkModeToggle(): bool { return static::darkMode() === 'toggle'; }
}