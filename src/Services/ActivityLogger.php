<?php

namespace Haybea\Trashcan\Services;

use Illuminate\Support\Facades\Log;
use Haybea\Trashcan\Models\TrashcanActivity;

class ActivityLogger
{
    public function log(string $action, string $modelClass, int|string|null $modelId = null, int $count = 1, ?array $metadata = null): ?TrashcanActivity
    {
        if (!config('trashcan.logging.enabled', true)) return null;
        $user = auth()->user();
        $request = request();

        $channel = config('trashcan.logging.channel');
        ($channel ? Log::channel($channel) : Log::getFacadeRoot())->info("Trashcan: {$action}", [
            'model' => $modelClass, 'model_id' => $modelId, 'count' => $count, 'user_id' => $user?->id, 'ip' => $request->ip()
        ]);

        if (config('trashcan.logging.database', true)) {
            return TrashcanActivity::create([
                'action' => $action, 'model_class' => $modelClass, 'model_id' => $modelId, 'count' => $count,
                'metadata' => $metadata, 'user_id' => $user?->id, 'user_name' => $user?->name,
                'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
            ]);
        }
        return null;
    }

    public function logRestored(string $mc, $id, ?array $m = null) { return $this->log(TrashcanActivity::ACTION_RESTORED, $mc, $id, 1, $m); }
    public function logForceDeleted(string $mc, $id, ?array $m = null) { return $this->log(TrashcanActivity::ACTION_FORCE_DELETED, $mc, $id, 1, $m); }
    public function logBulkRestored(string $mc, array $ids, ?array $m = null) { return $this->log(TrashcanActivity::ACTION_BULK_RESTORED, $mc, null, count($ids), array_merge($m ?? [], ['ids' => $ids])); }
    public function logBulkDeleted(string $mc, array $ids, ?array $m = null) { return $this->log(TrashcanActivity::ACTION_BULK_DELETED, $mc, null, count($ids), array_merge($m ?? [], ['ids' => $ids])); }
    public function logEmptied(string $mc, int $count, ?array $m = null) { return $this->log(TrashcanActivity::ACTION_EMPTIED, $mc, null, $count, $m); }
    public function logExported(string $mc, int $count, string $fmt, ?array $m = null) { return $this->log(TrashcanActivity::ACTION_EXPORTED, $mc, null, $count, array_merge($m ?? [], ['format' => $fmt])); }
}