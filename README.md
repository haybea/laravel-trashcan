# Laravel Trashcan

A beautiful trash management dashboard for Laravel soft deletes.

## Installation

```bash
composer require haybea/laravel-trashcan

php artisan trashcan:install
```

## Usage

Visit `/trashcan` in your browser.

## Configuration

Edit `config/trashcan.php` to customize.

## Authorization

```php
// In AppServiceProvider
use Haybea\Trashcan\Trashcan;

Trashcan::auth(function ($request) {
    return $request->user()?->is_admin;
});
```

## License

MIT