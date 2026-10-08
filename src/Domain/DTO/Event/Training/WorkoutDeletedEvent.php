<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\EventInterface;
use DateTimeImmutable;

/** A workout was abandoned or deleted, with everything in it. Read before it went. */
final readonly class WorkoutDeletedEvent implements EventInterface
{
    /** @param list<MovementDataModel> $movements the movements it held, each once */
    public function __construct(
        public UserDataModel $owner,
        public array $movements,
        /** Null for one abandoned in progress. */
        public ?DateTimeImmutable $finishedAt,
    ) {
    }
}
