<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A block was removed from a workout, its movements and sets with it. Read before it went. */
final readonly class WorkoutBlockDeletedEvent implements EventInterface
{
    /** @param list<MovementDataModel> $movements the movements it held, each once */
    public function __construct(
        public UserDataModel $owner,
        public array $movements,
    ) {
    }
}
