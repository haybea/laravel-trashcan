<?php

namespace Haybea\Trashcan\Services;

use Haybea\Trashcan\Models\TrashcanActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * Get the current authenticated user based on config guard.
     */
    protected function getUser()
    {
        $guard = config('trashcan.guard');

        return $guard ? Auth::guard($guard)->user() : Auth::user();
    }

    /**
     * Get the display name for the user.
     */
    protected function getUserName($user): ?string
    {
        if (! $user) {
            return null;
        }

        $attribute = config('trashcan.user_name_attribute', 'name');

        return $user->{$attribute} ?? $user->name ?? $user->email ?? null;
    }

    public function log(
        string $action,
        string $modelClass,
        int|string|null $modelId = null,
        int $count = 1,
        ?array $metadata = null
    ): ?TrashcanActivity {
        if (! config('trashcan.logging.enabled', true)) {
            return null;
        }

        $user = $this->getUser();
        $request = request();

        // Log to file/channel
        $channel = config('trashcan.logging.channel');
        $logger = $channel ? Log::channel($channel) : Log::getFacadeRoot();

        $logger->info("Trashcan: {$action}", [
            'model' => $modelClass,
            'model_id' => $modelId,
            'count' => $count,
            'user_id' => $user?->id,
            'user_name' => $this->getUserName($user),
            'ip' => $request->ip(),
        ]);

        // Store in database
        if (config('trashcan.logging.database', true)) {
            return TrashcanActivity::create([
                'action' => $action,
                'model_class' => $modelClass,
                'model_id' => $modelId,
                'count' => $count,
                'metadata' => $metadata,
                'user_id' => $user?->id,
                'user_name' => $this->getUserName($user),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return null;
    }

    public function logRestored(string $modelClass, int|string $modelId, ?array $metadata = null): ?TrashcanActivity
    {
        return $this->log(TrashcanActivity::ACTION_RESTORED, $modelClass, $modelId, 1, $metadata);
    }

    public function logForceDeleted(string $modelClass, int|string $modelId, ?array $metadata = null): ?TrashcanActivity
    {
        return $this->log(TrashcanActivity::ACTION_FORCE_DELETED, $modelClass, $modelId, 1, $metadata);
    }

    public function logBulkRestored(string $modelClass, array $ids, ?array $metadata = null): ?TrashcanActivity
    {
        return $this->log(
            TrashcanActivity::ACTION_BULK_RESTORED,
            $modelClass,
            null,
            count($ids),
            array_merge($metadata ?? [], ['ids' => $ids])
        );
    }

    public function logBulkDeleted(string $modelClass, array $ids, ?array $metadata = null): ?TrashcanActivity
    {
        return $this->log(
            TrashcanActivity::ACTION_BULK_DELETED,
            $modelClass,
            null,
            count($ids),
            array_merge($metadata ?? [], ['ids' => $ids])
        );
    }

    public function logEmptied(string $modelClass, int $count, ?array $metadata = null): ?TrashcanActivity
    {
        return $this->log(TrashcanActivity::ACTION_EMPTIED, $modelClass, null, $count, $metadata);
    }

    public function logExported(string $modelClass, int $count, string $format, ?array $metadata = null): ?TrashcanActivity
    {
        return $this->log(
            TrashcanActivity::ACTION_EXPORTED,
            $modelClass,
            null,
            $count,
            array_merge($metadata ?? [], ['format' => $format])
        );
    }
}
