<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\DataModel\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\MuscleDataModel;

/**
 * "Common" is the movements without an owner — the only ones the back-office sees. The counts are
 * over every movement, a person's own included: they guard deletions, and a muscle someone's own
 * movement targets is in use all the same.
 */
interface MovementProviderGateway
{
    /** A common movement, with its family, its muscles and their groups, and its equipments. */
    public function findOneCommonById(int $id): ?MovementDataModel;

    /** Ignoring case: the column's collation does the comparing. */
    public function findOneCommonByName(string $name): ?MovementDataModel;

    /**
     * Every common movement, by name, everything the output reads joined and selected. Each filter
     * narrows it when it is not null, and they combine; a muscle or a group matches a movement
     * whether it is its primary muscle or a secondary one.
     *
     * @return list<MovementDataModel>
     */
    public function findAllCommonForAdminList(
        ?bool $isActive,
        ?int $movementFamilyId,
        ?int $muscleGroupId,
        ?int $muscleId,
        ?int $equipmentId,
    ): array;

    /**
     * A movement a workout may take on now: active, in an active family, and common — a person's
     * own movements will join them the day they exist.
     */
    public function findOneOfferedById(int $id): ?MovementDataModel;

    /**
     * Every movement a workout may take on now, by name, everything the output reads joined and
     * selected.
     *
     * @return list<MovementDataModel>
     */
    public function findAllOffered(): array;

    public function countForMovementFamily(MovementFamilyDataModel $movementFamily): int;

    /** As the primary muscle or a secondary one. */
    public function countForMuscle(MuscleDataModel $muscle): int;

    public function countForEquipment(EquipmentDataModel $equipment): int;
}
