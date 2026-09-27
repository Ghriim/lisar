<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\DTO\DataModel\MuscleGroupDataModel;

interface MuscleProviderGateway
{
    /** Its group joined and selected. */
    public function findOneById(int $id): ?MuscleDataModel;

    /** Ignoring case, across every group: the column's collation does the comparing. */
    public function findOneByName(string $name): ?MuscleDataModel;

    /**
     * Every muscle for the back-office, by group name then by name, group joined and selected.
     * `$isActive` narrows on the muscle's own flag, not its group's; `$muscleGroupId` to one group.
     *
     * @return list<MuscleDataModel>
     */
    public function findAllForAdminList(?bool $isActive, ?int $muscleGroupId): array;

    /** Every muscle in the group, active or not. */
    public function countForMuscleGroup(MuscleGroupDataModel $muscleGroup): int;
}
