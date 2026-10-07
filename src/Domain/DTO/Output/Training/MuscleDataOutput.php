<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * A muscle, with the group it sits in. The group's own status travels with it: a muscle is offered
 * to new movements only when both are active, and the back-office has to be able to say why one
 * is not.
 */
final class MuscleDataOutput
{
    public int $id;

    public string $name;

    public bool $isActive;

    #[Map(source: 'muscleGroup.id')]
    public int $muscleGroupId;

    #[Map(source: 'muscleGroup.name')]
    public string $muscleGroupName;

    #[Map(source: 'muscleGroup.isActive')]
    public bool $muscleGroupIsActive;
}
