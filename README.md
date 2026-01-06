# Laravel Trashcan

A beautiful trash management dashboard for Laravel soft deletes.

## Installation

```bash
composer require haybea/laravel-trashcan
```

## Publish Config & Migrations

```bash
php artisan vendor:publish --tag=trashcan-config
php artisan vendor:publish --tag=trashcan-migrations
php artisan migrate
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