<?php

namespace Haybea\Trashcan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrashcanActivity extends Model
{
    protected $table = 'trashcan_activities';
    protected $fillable = ['action', 'model_class', 'model_id', 'count', 'metadata', 'user_id', 'user_name', 'ip_address', 'user_agent'];
    protected $casts = ['metadata' => 'array', 'count' => 'integer'];

    const ACTION_RESTORED = 'restored';
    const ACTION_FORCE_DELETED = 'force_deleted';
    const ACTION_BULK_RESTORED = 'bulk_restored';
    const ACTION_BULK_DELETED = 'bulk_deleted';
    const ACTION_EMPTIED = 'emptied';
    const ACTION_EXPORTED = 'exported';

    public function user(): BelongsTo { return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User')); }
    public function getModelNameAttribute(): string { return class_basename($this->model_class); }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_RESTORED => 'Restored', self::ACTION_FORCE_DELETED => 'Permanently Deleted',
            self::ACTION_BULK_RESTORED => 'Bulk Restored', self::ACTION_BULK_DELETED => 'Bulk Deleted',
            self::ACTION_EMPTIED => 'Emptied Trash', self::ACTION_EXPORTED => 'Exported',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_RESTORED, self::ACTION_BULK_RESTORED => 'success',
            self::ACTION_FORCE_DELETED, self::ACTION_BULK_DELETED, self::ACTION_EMPTIED => 'danger',
            self::ACTION_EXPORTED => 'info', default => 'secondary',
        };
    }
}