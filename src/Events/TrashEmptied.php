<?php

namespace Haybea\Trashcan\Events;

class TrashEmptied extends TrashcanEvent
{
    public function __construct(
        string $modelClass,
        int $count,
        ?array $metadata = null
    ) {
        parent::__construct($modelClass, null, $count, $metadata);
    }
}
