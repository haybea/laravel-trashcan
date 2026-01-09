<?php

namespace Haybea\Trashcan\Services;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class ModelDiscoveryService
{
    public function getModels(): Collection
    {
        $only = config('trashcan.only', []);

        if (!empty($only)) {
            return collect($only)
                ->filter(fn ($model) => $this->usesSoftDeletes($model))
                ->filter(fn ($model) => !$this->isExcluded($model))
                ->mapWithKeys(fn ($model) => [$model => $this->getModelInfo($model)]);
        }

        return $this->discoverModels();
    }

    protected function discoverModels(): Collection
    {
        $modelsPath = app_path(config('trashcan.models_path', 'Models'));

        if (!File::isDirectory($modelsPath)) {
            return collect();
        }

        return collect(File::allFiles($modelsPath))
            ->map(function ($file) {
                $relativePath = str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    $file->getRelativePathname()
                );
                return 'App\\' . config('trashcan.models_path', 'Models') . '\\' . $relativePath;
            })
            ->filter(fn ($class) => class_exists($class))
            ->filter(fn ($class) => $this->usesSoftDeletes($class))
            ->filter(fn ($class) => !$this->isExcluded($class))
            ->mapWithKeys(fn ($model) => [$model => $this->getModelInfo($model)]);
    }

    protected function usesSoftDeletes(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        return in_array(SoftDeletes::class, class_uses_recursive($class));
    }

    protected function isExcluded(string $class): bool
    {
        return in_array($class, config('trashcan.exclude', []));
    }

    protected function getModelInfo(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $instance = new $class;

        return [
            'class' => $class,
            'name' => class_basename($class),
            'table' => $instance->getTable(),
            'trashed_count' => $class::onlyTrashed()->count(),
            'columns' => $this->getDisplayColumns($class, $instance),
        ];
    }

    protected function getDisplayColumns(string $class, $instance): array
    {
        $configured = config("trashcan.columns.{$class}");

        if ($configured) {
            return $configured;
        }

        $columns = ['id'];

        $schema = $instance->getConnection()->getSchemaBuilder();
        $tableColumns = $schema->getColumnListing($instance->getTable());

        $displayColumns = ['name', 'title', 'label', 'subject', 'email', 'slug'];
        foreach ($displayColumns as $col) {
            if (in_array($col, $tableColumns)) {
                $columns[] = $col;
                break;
            }
        }

        $columns[] = 'deleted_at';

        return $columns;
    }

    public function getTrashedItems(string $modelClass, int $perPage = 15)
    {
        return $modelClass::onlyTrashed()
            ->latest('deleted_at')
            ->paginate($perPage);
    }
}