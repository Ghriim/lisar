<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A set type was renamed, recoloured, made the default, or made to count for records or not. */
final readonly class SetTypeUpdatedEvent implements EventInterface
{
    public function __construct(
        public SetTypeDataModel $setType,
        /** Whether this write switched what its sets count for: the records hang on it. */
        public bool $countsForPersonalBestsChanged,
    ) {
    }
}
