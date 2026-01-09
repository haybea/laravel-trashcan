<?php

namespace Haybea\Trashcan\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class TrashcanEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $modelClass,
        public int|string|null $modelId = null,
        public int $count = 1,
        public ?array $metadata = null
    ) {}
}