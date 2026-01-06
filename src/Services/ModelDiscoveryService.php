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
            return collect($only)->filter(fn ($m) => $this->usesSoftDeletes($m))->filter(fn ($m) => !$this->isExcluded($m))->mapWithKeys(fn ($m) => [$m => $this->getModelInfo($m)]);
        }
        return $this->discoverModels();
    }

    protected function discoverModels(): Collection
    {
        $path = app_path(config('trashcan.models_path', 'Models'));
        if (!File::isDirectory($path)) return collect();

        return collect(File::allFiles($path))
            ->map(fn ($f) => 'App\\' . config('trashcan.models_path', 'Models') . '\\' . str_replace(['/', '.php'], ['\\', ''], $f->getRelativePathname()))
            ->filter(fn ($c) => class_exists($c))->filter(fn ($c) => $this->usesSoftDeletes($c))->filter(fn ($c) => !$this->isExcluded($c))
            ->mapWithKeys(fn ($m) => [$m => $this->getModelInfo($m)]);
    }

    protected function usesSoftDeletes(string $class): bool { return class_exists($class) && in_array(SoftDeletes::class, class_uses_recursive($class)); }
    protected function isExcluded(string $class): bool { return in_array($class, config('trashcan.exclude', [])); }

    protected function getModelInfo(string $class): array
    {
        $instance = new $class;
        return [
            'class' => $class, 'name' => class_basename($class), 'table' => $instance->getTable(),
            'trashed_count' => $class::onlyTrashed()->count(), 'columns' => $this->getDisplayColumns($class, $instance),
        ];
    }

    protected function getDisplayColumns(string $class, $instance): array
    {
        if ($configured = config("trashcan.columns.{$class}")) return $configured;
        $cols = ['id'];
        $tableCols = $instance->getConnection()->getSchemaBuilder()->getColumnListing($instance->getTable());
        foreach (['name', 'title', 'label', 'subject', 'email', 'slug'] as $c) { if (in_array($c, $tableCols)) { $cols[] = $c; break; } }
        $cols[] = 'deleted_at';
        return $cols;
    }
}