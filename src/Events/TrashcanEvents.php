<?php

namespace Haybea\Trashcan\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class TrashcanEvent
{
    use Dispatchable, SerializesModels;
    public function __construct(public string $modelClass, public int|string|null $modelId = null, public int $count = 1, public ?array $metadata = null) {}
}

class ItemRestored extends TrashcanEvent {}
class ItemForceDeleted extends TrashcanEvent {}
class BulkRestored extends TrashcanEvent { public function __construct(string $modelClass, public array $ids, ?array $metadata = null) { parent::__construct($modelClass, null, count($ids), $metadata); } }
class BulkForceDeleted extends TrashcanEvent { public function __construct(string $modelClass, public array $ids, ?array $metadata = null) { parent::__construct($modelClass, null, count($ids), $metadata); } }
class TrashEmptied extends TrashcanEvent { public function __construct(string $modelClass, int $count, ?array $metadata = null) { parent::__construct($modelClass, null, $count, $metadata); } }
class TrashExported extends TrashcanEvent { public function __construct(string $modelClass, int $count, public string $format, ?array $metadata = null) { parent::__construct($modelClass, null, $count, $metadata); } }