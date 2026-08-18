<?php

namespace Haybea\Trashcan\Events;

class TrashExported extends TrashcanEvent
{
    public function __construct(
        string $modelClass,
        int $count,
        public string $format,
        ?array $metadata = null
    ) {
        parent::__construct($modelClass, null, $count, $metadata);
    }
}
