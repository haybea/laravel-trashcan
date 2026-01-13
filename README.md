# Laravel Trashcan

An observability & recovery console for Laravel soft-deleted records.

Laravel Trashcan provides a beautiful dashboard/console to manage your soft-deleted Eloquent records. Instead of digging through database tables or writing manual "Restore" logic, Trashcan gives you a central hub to visualize, restore, or permanently purge "trashed" data.

## Features

- **Centralized Dashboard**: View all models using the `SoftDeletes` trait in one place
- **Relationship Awareness**: See which child records will be affected before you restore or purge a parent
- **Safe by Default**: Automatically restricted to local environments, with a simple Gate for production access
- **Search & Filter**: Filter by model type, deletion date, or search for specific IDs/attributes
- **Bulk Operations**: Restore or permanently delete multiple items at once
- **Activity Logging**: Track all restore and delete operations
- **Export Functionality**: Export trashed records to CSV or JSON

## Installation

You can install the package via Composer. After the package is installed, publish its assets and configuration file.

```bash
composer require haybea/laravel-trashcan

php artisan trashcan:install
```

## Usage

Visit `/trashcan` in your browser to access the dashboard.

### Registering Models

By default, Trashcan will attempt to find all models in your `app/Models` directory that use the `SoftDeletes` trait. If you have models in custom locations, you can define them in the `config/trashcan.php` file:

```php
'only' => [
    App\Models\User::class,
    App\Models\Post::class,
    // Add your custom models here
],
```

Alternatively, you can exclude specific models:

```php
'exclude' => [
    App\Models\SensitiveModel::class,
],
```

## Configuration

Edit `config/trashcan.php` to customize Trashcan's behavior:

- **Middleware**: Add custom middleware for authentication/authorization
- **Models**: Configure which models to include/exclude
- **Columns**: Customize which columns to display per model
- **Searchable**: Define searchable columns per model
- **Logging**: Enable/disable activity logging
- **Export**: Configure export functionality

## Authorization

By default, Trashcan only allows access in the `local` environment. To grant access in production, you should modify the gate method within your `AppServiceProvider` or create a custom service provider:

```php
use Illuminate\Support\Facades\Gate;

/**
 * Register the Trashcan gate.
 *
 * This gate determines who can access Trashcan in non-local environments.
 */
protected function gate(): void
{
    Gate::define('viewTrashcan', function ($user) {
        return in_array($user->email, [
            'admin@yourdomain.com',
        ]);
    });
}
```

You can also configure per-model permissions in `config/trashcan.php`:

```php
'model_permissions' => [
    App\Models\User::class => [
        'view' => 'viewTrashedUsers',
        'restore' => 'restoreUsers',
        'delete' => 'forceDeleteUsers',
    ],
],
```

## License

MIT
