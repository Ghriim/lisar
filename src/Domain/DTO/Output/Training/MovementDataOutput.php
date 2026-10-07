<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * A common movement, with what it targets and what it is done with. The family's status travels
 * with it, and so does each muscle's and equipment's: a movement can hold something retired since,
 * and the back-office has to be able to show it.
 */
final class MovementDataOutput
{
    public int $id;

    public string $name;

    public ?string $description = null;

    public ?string $videoUrl = null;

    #[Map(source: 'movementFamily.id')]
    public int $movementFamilyId;

    #[Map(source: 'movementFamily.name')]
    public string $movementFamilyName;

    #[Map(source: 'movementFamily.isActive')]
    public bool $movementFamilyIsActive;

    #[Map(if: false)]
    public MuscleDataOutput $primaryMuscle;

    /**
     * By name.
     *
     * @var list<MuscleDataOutput>
     */
    #[Map(if: false)]
    public array $secondaryMuscles = [];

    /**
     * By name; empty for a bodyweight movement.
     *
     * @var list<EquipmentDataOutput>
     */
    #[Map(if: false)]
    public array $equipments = [];

    public bool $tracksReps;

    public bool $tracksWeight;

    public bool $tracksDuration;

    public bool $tracksDistance;

    public bool $isUnilateral;

    public bool $isActive;
}
