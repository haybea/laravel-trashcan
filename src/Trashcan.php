<?php

namespace Haybea\Trashcan;

use Illuminate\Contracts\Foundation\Application;

class Trashcan
{
    protected Application $app;

    protected static ?\Closure $authCallback = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register the Trash authorization callback.
     */
    public static function auth(\Closure $callback): void
    {
        static::$authCallback = $callback;
    }

    /**
     * Determine if the given request can access Trash.
     */
    public static function check($request): bool
    {
        return (static::$authCallback ?: function () {
            return app()->environment(config('trashcan.allowed_environments', ['local']));
        })($request);
    }

    /**
     * Get the CSS framework being used.
     */
    public static function cssFramework(): string
    {
        return config('trashcan.css_framework', 'bootstrap');
    }

    /**
     * Check if using Bootstrap.
     */
    public static function isBootstrap(): bool
    {
        return static::cssFramework() === 'bootstrap';
    }

    /**
     * Check if using Tailwind.
     */
    public static function isTailwind(): bool
    {
        return static::cssFramework() === 'tailwind';
    }

    /**
     * Get dark mode setting.
     */
    public static function darkMode(): string
    {
        return config('trashcan.dark_mode', 'toggle');
    }

    /**
     * Check if dark mode toggle is enabled.
     */
    public static function hasDarkModeToggle(): bool
    {
        return static::darkMode() === 'toggle';
    }
}
