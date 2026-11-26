<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A TODO was renamed, archived or made active again. */
final readonly class TodoUpdatedEvent implements EventInterface
{
    public function __construct(
        public TodoDataModel $todo,
        /** Whether this write is the one archiving it: its planning goes with it. */
        public bool $archives,
    ) {
    }
}
