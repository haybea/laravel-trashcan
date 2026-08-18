<?php

namespace Haybea\Trashcan\Events;

class BulkRestored extends TrashcanEvent
{
    public function __construct(
        string $modelClass,
        public array $ids,
        ?array $metadata = null
    ) {
        parent::__construct($modelClass, null, count($ids), $metadata);
    }
}
