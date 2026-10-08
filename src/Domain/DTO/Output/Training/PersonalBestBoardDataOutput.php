<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

/** Everything an account holds as records: those of whole workouts, then movement by movement. */
final class PersonalBestBoardDataOutput
{
    /**
     * The records of whole workouts, across movements.
     *
     * @var list<PersonalBestRecordDataOutput>
     */
    public array $sessions = [];

    /**
     * Every movement on offer, with its records or none, and the retired ones that hold some — by
     * family, then by name.
     *
     * @var list<MovementPersonalBestsDataOutput>
     */
    public array $movements = [];
}
