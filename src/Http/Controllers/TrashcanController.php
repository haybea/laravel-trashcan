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
        protected ModelDiscoveryService $discovery,
        protected ActivityLogger $logger,
        protected ExportService $exportService,
        protected StatisticsService $stats
    ) {}

    public function index()
    {
        $models = $this->discovery->getModels();
        $stats = $this->stats->getDashboardStats();
        $recentActivity = config('trashcan.logging.database') ? TrashcanActivity::with('user')->latest()->limit(10)->get() : collect();
        return view('trashcan::dashboard', compact('models', 'stats', 'recentActivity'));
    }

    public function show(Request $request, string $model)
    {
        $models = $this->discovery->getModels();
        $modelClass = base64_decode($model);
        if (!$models->has($modelClass)) abort(404);
        $this->authorizeModel($modelClass, 'view');

        $activeModel = $models->get($modelClass);
        $query = $modelClass::onlyTrashed();

        if ($search = $request->get('search')) {
            $cols = config("trashcan.searchable.{$modelClass}") ?? array_filter($activeModel['columns'], fn ($c) => !in_array($c, ['id', 'deleted_at']));
            $query->where(fn ($q) => collect($cols)->each(fn ($c) => $q->orWhere($c, 'like', "%{$search}%")));
        }
        if ($from = $request->get('from')) $query->whereDate('deleted_at', '>=', $from);
        if ($to = $request->get('to')) $query->whereDate('deleted_at', '<=', $to);

        $sortBy = $request->get('sort', 'deleted_at');
        $sortDir = $request->get('dir', 'desc');
        in_array($sortBy, $activeModel['columns']) ? $query->orderBy($sortBy, $sortDir) : $query->latest('deleted_at');

        $items = $query->paginate(config('trashcan.per_page', 15))->withQueryString();
        $encoded = base64_encode($modelClass);
        return view('trashcan::model', compact('models', 'activeModel', 'items', 'modelClass', 'encoded'));
    }

    public function restore(Request $request, string $model, int $id)
    {
        $modelClass = base64_decode($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'restore');

        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->restore();
        foreach (config("trashcan.restore_with_relations.{$modelClass}", []) as $rel) {
            if (method_exists($item, $rel)) $item->{$rel}()->onlyTrashed()->restore();
        }

        $this->logger->logRestored($modelClass, $id);
        event(new ItemRestored($modelClass, $id));
        return back()->with('success', class_basename($modelClass) . ' restored successfully.');
    }

    public function forceDelete(Request $request, string $model, int $id)
    {
        $modelClass = base64_decode($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'delete');

        $modelClass::onlyTrashed()->findOrFail($id)->forceDelete();
        $this->logger->logForceDeleted($modelClass, $id);
        event(new ItemForceDeleted($modelClass, $id));
        return back()->with('success', class_basename($modelClass) . ' permanently deleted.');
    }

    public function bulkRestore(Request $request, string $model)
    {
        $modelClass = base64_decode($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'restore');

        $ids = $this->parseIds($request->input('ids'));
        $modelClass::onlyTrashed()->whereIn('id', $ids)->get()->each->restore();
        $this->logger->logBulkRestored($modelClass, $ids);
        event(new BulkRestored($modelClass, $ids));
        return back()->with('success', count($ids) . ' items restored.');
    }

    public function bulkForceDelete(Request $request, string $model)
    {
        $modelClass = base64_decode($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'delete');

        $ids = $this->parseIds($request->input('ids'));
        $modelClass::onlyTrashed()->whereIn('id', $ids)->get()->each->forceDelete();
        $this->logger->logBulkDeleted($modelClass, $ids);
        event(new BulkForceDeleted($modelClass, $ids));
        return back()->with('success', count($ids) . ' items permanently deleted.');
    }

    public function emptyTrash(Request $request, string $model)
    {
        $modelClass = base64_decode($model);
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
        $modelClass = base64_decode($model);
        $this->validateModel($modelClass);
        return $this->exportService->export($modelClass, $request->get('format', 'csv'), $request->has('ids') ? $this->parseIds($request->input('ids')) : null);
    }

    public function activity()
    {
        if (!config('trashcan.logging.database')) abort(404);
        $activities = TrashcanActivity::with('user')->latest()->paginate(20);
        $models = $this->discovery->getModels();
        return view('trashcan::activity', compact('activities', 'models'));
    }

    public function statistics()
    {
        if (!config('trashcan.statistics.enabled')) abort(404);
        $models = $this->discovery->getModels();
        $stats = $this->stats->getDashboardStats();
        return view('trashcan::statistics', compact('models', 'stats'));
    }

    protected function authorizeModel(string $modelClass, string $action): void
    {
        $perms = config("trashcan.model_permissions.{$modelClass}");
        if ($perms && isset($perms[$action]) && !Gate::allows($perms[$action])) abort(403);
    }

    protected function parseIds($ids): array { return array_map('intval', is_string($ids) ? (json_decode($ids, true) ?? []) : (array) $ids); }
    protected function validateModel(string $mc): void { if (!$this->discovery->getModels()->has($mc)) abort(404); }
    public static function encodeModelClass(string $class): string { return base64_encode($class); }
}