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
        $recentActivity = config('trashcan.logging.database')
            ? TrashcanActivity::with('user')->latest()->limit(10)->get()
            : collect();

        return view('trashcan::dashboard', compact('models', 'stats', 'recentActivity'));
    }

    public function show(Request $request, string $model)
    {
        $models = $this->discovery->getModels();
        $modelClass = base64_decode($model);

        if (!$models->has($modelClass)) {
            abort(404);
        }

        $this->authorizeModel($modelClass, 'view');

        $activeModel = $models->get($modelClass);
        $query = $modelClass::onlyTrashed();

        if ($search = $request->get('search')) {
            $cols = config("trashcan.searchable.{$modelClass}")
                ?? array_filter($activeModel['columns'], fn ($c) => !in_array($c, ['id', 'deleted_at']));
            $query->where(fn ($q) => collect($cols)->each(fn ($c) => $q->orWhere($c, 'like', "%{$search}%")));
        }

        if ($from = $request->get('from')) {
            $query->whereDate('deleted_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('deleted_at', '<=', $to);
        }

        $sortBy = $request->get('sort', 'deleted_at');
        $sortDir = $request->get('dir', 'desc');
        in_array($sortBy, $activeModel['columns']) ? $query->orderBy($sortBy, $sortDir) : $query->latest('deleted_at');

        $items = $query->paginate(config('trashcan.per_page', 15))->withQueryString();
        $encoded = base64_encode($modelClass);

        // Get orphaned children for each item
//        $orphanedChildren = $this->getOrphanedChildrenForItems($modelClass, $items->items());

        return view('trashcan::model', compact('models', 'activeModel', 'items', 'modelClass', 'encoded'));
    }

    public function restore(Request $request, string $model, string $id)
    {
        $modelClass = base64_decode($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'restore');

        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->restore();

        foreach ($this->resolveRelations($modelClass) as $rel) {
            if (method_exists($item, $rel)) {
                $item->{$rel}()->onlyTrashed()->restore();
            }
        }

        $this->logger->logRestored($modelClass, $id);
        event(new ItemRestored($modelClass, $id));

        return back()->with('success', class_basename($modelClass) . ' restored successfully.');
    }

    public function forceDelete(Request $request, string $model, string $id)
    {
        $modelClass = base64_decode($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'delete');
        $this->guardAgainstRelatedRecords($modelClass, [$id]);

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
        $items = $modelClass::onlyTrashed()->whereIn('id', $ids)->get();
        
        $relations = $this->resolveRelations($modelClass);

        foreach ($items as $item) {
            $item->restore();

            foreach ($relations as $rel) {
                if (method_exists($item, $rel)) {
                    $item->{$rel}()->onlyTrashed()->restore();
                }
            }
        }

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
        $this->guardAgainstRelatedRecords($modelClass, $ids);
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
        $this->authorizeModel($modelClass, 'view');

        return $this->exportService->export(
            $modelClass,
            $request->get('format', 'csv'),
            $request->has('ids') ? $this->parseIds($request->input('ids')) : null
        );
    }

    public function activity()
    {
        if (!config('trashcan.logging.database')) {
            abort(404);
        }

        $activities = TrashcanActivity::with('user')->latest()->paginate(20);
        $models = $this->discovery->getModels();

        return view('trashcan::activity', compact('activities', 'models'));
    }

    public function statistics()
    {
        if (!config('trashcan.statistics.enabled')) {
            abort(404);
        }

        $models = $this->discovery->getModels();
        $stats = $this->stats->getDashboardStats();

        return view('trashcan::statistics', compact('models', 'stats'));
    }

    public function getAffectedChildren(Request $request, string $model)
    {
        $modelClass = base64_decode($model);
        $this->validateModel($modelClass);
        $this->authorizeModel($modelClass, 'view');

        $ids = $this->parseIds($request->input('ids', $request->input('id')));
        
        if (empty($ids)) {
            return response()->json(['affected_children' => []]);
        }

        $affectedChildren = $this->getAffectedChildRecords($modelClass, $ids);

        return response()->json(['affected_children' => $affectedChildren]);
    }

    protected function authorizeModel(string $modelClass, string $action): void
    {
        $perms = config("trashcan.model_permissions.{$modelClass}");
        if ($perms && isset($perms[$action]) && !Gate::allows($perms[$action])) {
            abort(403);
        }
    }

    protected function parseIds($ids): array
    {
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }
        return array_map('intval', (array) $ids);
    }

    protected function validateModel(string $modelClass): void
    {
        if (!$this->discovery->getModels()->has($modelClass)) {
            abort(404);
        }
    }

    public static function encodeModelClass(string $class): string
    {
        return base64_encode($class);
    }

    protected function getAffectedChildRecords(string $modelClass, array $ids): array
    {
        $affectedChildren = [];
        $relations = $this->resolveRelations($modelClass);

        if (empty($relations)) {
            return [];
        }

        $items = $modelClass::onlyTrashed()->whereIn('id', $ids)->get();

        foreach ($items as $item) {
            foreach ($relations as $relationName) {
                if (!method_exists($item, $relationName)) {
                    continue;
                }

                try {
                    $relation = $item->{$relationName}();
                    $relatedModel = $relation->getRelated();

                    // Check if the related model uses SoftDeletes
                    if (!in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($relatedModel))) {
                        continue;
                    }

                    $trashedCount = $relation->onlyTrashed()->count();

                    if ($trashedCount > 0) {
                        $modelName = class_basename($relatedModel);
                        $key = $modelName . ':' . $relationName;

                        if (!isset($affectedChildren[$key])) {
                            $affectedChildren[$key] = [
                                'model' => $modelName,
                                'relation' => $relationName,
                                'count' => 0,
                            ];
                        }

                        $affectedChildren[$key]['count'] += $trashedCount;
                    }
                } catch (\Exception $e) {
                    // Skip relations that can't be accessed
                    continue;
                }
            }
        }

        return array_values($affectedChildren);
    }

    /**
     * Resolve which relations to consider for a model: explicit config wins,
     * falling back to reflection-based auto-detection so cascade-restore and
     * the affected-children warning always agree on the same relation set.
     */
    protected function resolveRelations(string $modelClass): array
    {
        $relations = config("trashcan.restore_with_relations.{$modelClass}", []);

        return empty($relations) ? $this->autoDetectRelations($modelClass) : $relations;
    }

    /**
     * Abort the request if the model is configured to block permanent deletion
     * while related records still exist. Opt-in via `block_delete_with_children`
     * config, so existing installs see no behavior change unless configured.
     */
    protected function guardAgainstRelatedRecords(string $modelClass, array $ids): void
    {
        if (!in_array($modelClass, config('trashcan.block_delete_with_children', []))) {
            return;
        }

        $relations = $this->resolveRelations($modelClass);

        if (empty($relations)) {
            return;
        }

        $items = $modelClass::onlyTrashed()->whereIn('id', $ids)->get();

        foreach ($items as $item) {
            foreach ($relations as $relationName) {
                if (!method_exists($item, $relationName)) {
                    continue;
                }

                $count = 0;

                try {
                    $relation = $item->{$relationName}();
                    $relatedModel = $relation->getRelated();

                    $count = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($relatedModel))
                        ? $relation->withTrashed()->count()
                        : $relation->count();
                } catch (\Exception $e) {
                    continue;
                }

                if ($count > 0) {
                    abort(422, 'Cannot permanently delete: related records exist.');
                }
            }
        }
    }

//    protected function getOrphanedChildrenForItems(string $modelClass, array $items): array
//    {
//        $orphanedChildren = [];
//
//        if (empty($items)) {
//            return [];
//        }
//
//        // Get relations from config or auto-detect
//        $relations = config("trashcan.restore_with_relations.{$modelClass}", []);
//
//        if (empty($relations)) {
//            $relations = $this->autoDetectRelations($modelClass);
//        }
//
//        if (empty($relations)) {
//            return [];
//        }
//
//        $itemIds = collect($items)->pluck('id')->toArray();
//        $instance = new $modelClass;
//
//        foreach ($items as $item) {
//            $itemId = $item->id;
//            $orphanedChildren[$itemId] = [];
//
//            // Create a fresh instance to access relations
//            $freshItem = $modelClass::withTrashed()->find($itemId);
//
//            if (!$freshItem) {
//                continue;
//            }
//
//            // Temporarily restore to access relations
//            $wasTrashed = $freshItem->trashed();
//            if ($wasTrashed) {
//                $freshItem->restore();
//            }
//
//            foreach ($relations as $relationName) {
//                if (!method_exists($freshItem, $relationName)) {
//                    continue;
//                }
//
//                try {
//                    $relation = $freshItem->{$relationName}();
//                    $relatedModel = $relation->getRelated();
//
//                    // Get the foreign key and local key based on relation type
//                    [$foreignKey, $localKey] = $this->getRelationKeys($relation, $itemId);
//
//                    if (!$foreignKey || !$localKey) {
//                        continue;
//                    }
//
//                    // Find active (non-deleted) children of this deleted parent
//                    // These are the orphaned children - they exist but their parent is deleted
//                    $query = $relatedModel::where($foreignKey, $localKey);
//
//                    // If the related model uses SoftDeletes, only count non-deleted children
//                    if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($relatedModel))) {
//                        $query->whereNull('deleted_at');
//                    }
//
//                    $activeChildrenCount = $query->count();
//
//                    if ($activeChildrenCount > 0) {
//                        $orphanedChildren[$itemId][] = [
//                            'model' => class_basename($relatedModel),
//                            'relation' => $relationName,
//                            'count' => $activeChildrenCount,
//                        ];
//                    }
//                } catch (\Exception $e) {
//                    // Skip relations that can't be accessed
//                    continue;
//                }
//            }
//
//            // Re-trash if it was trashed
//            if ($wasTrashed) {
//                $freshItem->delete();
//            }
//        }
//
//        return $orphanedChildren;
//    }

    protected function autoDetectRelations(string $modelClass): array
    {
        $relations = [];
        $reflection = new \ReflectionClass($modelClass);
        $instance = new $modelClass;

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip non-relation methods
            if ($method->isStatic() || $method->getNumberOfParameters() > 0) {
                continue;
            }

            $methodName = $method->getName();
            
            // Skip common non-relation methods
            if (in_array($methodName, ['getTable', 'getConnection', 'getKeyName', 'getForeignKey'])) {
                continue;
            }

            try {
                $returnType = $method->getReturnType();
                
                // Check if method returns a relation type
                if ($returnType && (
                    str_contains($returnType->getName(), 'Relation') ||
                    str_contains($returnType->getName(), 'HasMany') ||
                    str_contains($returnType->getName(), 'BelongsTo') ||
                    str_contains($returnType->getName(), 'HasOne') ||
                    str_contains($returnType->getName(), 'BelongsToMany') ||
                    str_contains($returnType->getName(), 'MorphMany') ||
                    str_contains($returnType->getName(), 'MorphToMany')
                )) {
                    $relations[] = $methodName;
                    continue;
                }

                // Try calling the method to see if it returns a relation
                $result = $instance->{$methodName}();
                
                if ($result instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                    $relations[] = $methodName;
                }
            } catch (\Exception $e) {
                // Skip methods that throw errors
                continue;
            }
        }

        return $relations;
    }

    protected function getRelationKeys($relation, $parentId): array
    {
        // For HasMany/HasOne: foreign key is on child table, local key is parent's primary key
        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany ||
            $relation instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
            return [
                $relation->getForeignKeyName(), // Foreign key on child table (e.g., 'post_id')
                $parentId // Parent's ID value
            ];
        }
        
        // For BelongsTo: foreign key is on child table pointing to parent, but we query from child's perspective
        // This is less common for orphaned children, but handle it anyway
        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            // The child has a foreign key pointing to parent
            // To find orphaned children, we'd query children where their foreign key = parent id
            // But BelongsTo is typically used from child to parent, not parent to children
            // So we skip this for now
            return [null, null];
        }

        // For BelongsToMany: uses pivot table, more complex
        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
            // This is complex and may not represent true parent-child relationship
            // Skip for now
            return [null, null];
        }

        // For other relation types, try to get foreign key
        try {
            if (method_exists($relation, 'getForeignKeyName')) {
                return [
                    $relation->getForeignKeyName(),
                    $parentId
                ];
            }
        } catch (\Exception $e) {
            // Could not determine foreign key
        }

        return [null, null];
    }
}