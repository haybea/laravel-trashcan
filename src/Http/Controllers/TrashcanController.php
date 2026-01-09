<?php

namespace Haybea\Trashcan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Haybea\Trashcan\Events\{ItemRestored, ItemForceDeleted, BulkRestored, BulkForceDeleted, TrashEmptied};
use Haybea\Trashcan\Models\TrashcanActivity;
use Haybea\Trashcan\Services\{ActivityLogger, ExportService, ModelDiscoveryService, StatisticsService};

class TrashcanController extends Controller
{
    public function __construct(
        protected ModelDiscoveryService $discoveryService,
        protected ActivityLogger $logger,
        protected ExportService $exportService,
        protected StatisticsService $statisticsService
    ) {}

    public function index(Request $request)
    {
        $models = $this->discoveryService->getModels();
        $stats = $this->statisticsService->getDashboardStats();

        $recentActivity = config('trashcan.logging.database')
            ? TrashcanActivity::with('user')->latest()->limit(10)->get()
            : collect();

        return view('trashcan::dashboard', compact('models', 'stats', 'recentActivity'));
    }

    public function show(Request $request, string $model)
    {
        $models = $this->discoveryService->getModels();
        $modelClass = $this->decodeModelClass($model);

        if (!$models->has($modelClass)) {
            abort(404, 'Model not found');
        }

        $this->authorizeModel($modelClass, 'view');

        $activeModel = $models->get($modelClass);

        $query = $modelClass::onlyTrashed();

        // Search
        if ($search = $request->get('search')) {
            $searchableColumns = $this->getSearchableColumns($modelClass, $activeModel);
            $query->where(function ($q) use ($search, $searchableColumns) {
                foreach ($searchableColumns as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        // Date filter
        if ($from = $request->get('from')) {
            $query->whereDate('deleted_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('deleted_at', '<=', $to);
        }

        // Sorting
        $sortBy = $request->get('sort', 'deleted_at');
        $sortDir = $request->get('dir', 'desc');

        if (in_array($sortBy, $activeModel['columns'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest('deleted_at');
        }

        $items = $query->paginate(config('trash.per_page', 15))->withQueryString();
        $encoded = self::encodeModelClass($modelClass);

        return view('trashcan::model', compact('models', 'activeModel', 'items', 'modelClass', 'encoded'));
    }

    public function restore(Request $request, string $model, int $id)
    {
        $modelClass = $this->decodeModelClass($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'restore');

        $item = $modelClass::onlyTrashed()->findOrFail($id);

        $this->restoreWithRelations($item, $modelClass);

        $this->logger->logRestored($modelClass, $id);
        event(new ItemRestored($modelClass, $id));

        return back()->with('success', class_basename($modelClass) . ' restored successfully.');
    }

    public function forceDelete(Request $request, string $model, int $id)
    {
        $modelClass = $this->decodeModelClass($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'delete');

        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        $this->logger->logForceDeleted($modelClass, $id);
        event(new ItemForceDeleted($modelClass, $id));

        return back()->with('success', class_basename($modelClass) . ' permanently deleted.');
    }

    public function bulkRestore(Request $request, string $model)
    {
        $modelClass = $this->decodeModelClass($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'restore');

        $ids = $this->parseIds($request->input('ids'));

        $items = $modelClass::onlyTrashed()->whereIn('id', $ids)->get();

        foreach ($items as $item) {
            $this->restoreWithRelations($item, $modelClass);
        }

        $this->logger->logBulkRestored($modelClass, $ids);
        event(new BulkRestored($modelClass, $ids));

        return back()->with('success', count($ids) . ' items restored.');
    }

    public function bulkForceDelete(Request $request, string $model)
    {
        $modelClass = $this->decodeModelClass($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'delete');

        $ids = $this->parseIds($request->input('ids'));

        $modelClass::onlyTrashed()
            ->whereIn('id', $ids)
            ->get()
            ->each->forceDelete();

        $this->logger->logBulkDeleted($modelClass, $ids);
        event(new BulkForceDeleted($modelClass, $ids));

        return back()->with('success', count($ids) . ' items permanently deleted.');
    }

    public function emptyTrash(Request $request, string $model)
    {
        $modelClass = $this->decodeModelClass($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'delete');

        $count = $modelClass::onlyTrashed()->count();
        $modelClass::onlyTrashed()->forceDelete();

        $this->logger->logEmptied($modelClass, $count);
        event(new TrashEmptied($modelClass, $count));

        return back()->with('success', "Trash emptied. {$count} items deleted.");
    }

    public function export(Request $request, string $model)
    {
        $modelClass = $this->decodeModelClass($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'view');

        $format = $request->get('format', 'csv');
        $ids = $request->has('ids') ? $this->parseIds($request->input('ids')) : null;

        return $this->exportService->export($modelClass, $format, $ids);
    }

    public function activity(Request $request)
    {
        if (!config('trash.logging.database')) {
            abort(404, 'Activity logging is disabled');
        }

        $activities = TrashcanActivity::with('user')
            ->latest()
            ->paginate(20);

        $models = $this->discoveryService->getModels();

        return view('trashcan::activity', compact('activities', 'models'));
    }

    public function statistics(Request $request)
    {
        if (!config('trashcan.statistics.enabled')) {
            abort(404, 'Statistics are disabled');
        }

        $models = $this->discoveryService->getModels();
        $stats = $this->statisticsService->getDashboardStats();

        return view('trashcan::statistics', compact('models', 'stats'));
    }

    protected function restoreWithRelations($item, string $modelClass): void
    {
        $item->restore();

        $relations = config("trashcan.restore_with_relations.{$modelClass}", []);

        foreach ($relations as $relation) {
            if (method_exists($item, $relation)) {
                $item->{$relation}()->onlyTrashed()->restore();
            }
        }
    }

    protected function getSearchableColumns(string $modelClass, array $modelInfo): array
    {
        $configured = config("trashcan.searchable.{$modelClass}");

        if ($configured) {
            return $configured;
        }

        return array_filter($modelInfo['columns'], fn ($col) => !in_array($col, ['id', 'deleted_at']));
    }

    protected function authorizeModel(string $modelClass, string $action): void
    {
        $permissions = config("trashcan.model_permissions.{$modelClass}");

        if ($permissions && isset($permissions[$action])) {
            if (!Gate::allows($permissions[$action])) {
                abort(403, "Unauthorized to {$action} " . class_basename($modelClass));
            }
        }
    }

    protected function parseIds($ids): array
    {
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }

        return array_map('intval', (array) $ids);
    }

    protected function decodeModelClass(string $encoded): string
    {
        return base64_decode($encoded);
    }

    public static function encodeModelClass(string $class): string
    {
        return base64_encode($class);
    }

    protected function validateModel(string $modelClass): void
    {
        $models = $this->discoveryService->getModels();

        if (!$models->has($modelClass)) {
            abort(404, 'Model not found or not allowed');
        }
    }
}