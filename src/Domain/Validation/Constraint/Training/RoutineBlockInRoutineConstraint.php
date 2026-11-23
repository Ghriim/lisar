<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;

/**
 * The block named in the route is in the routine named with it. One of another routine — someone
 * else's, or another of the caller's — is answered like one that does not exist: its id says
 * nothing.
 */
final readonly class RoutineBlockInRoutineConstraint
{
    public const string NOT_FOUND = 'routine_block_not_found';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(RoutineBlockDataModel $block, RoutineDataModel $routine, array $violations = []): array
    {
        // The same row is the same instance within a request: the identity map sees to it.
        if ($routine !== $block->routine) {
            $violations['blockId'][] = self::NOT_FOUND;
        }

        return $violations;
    }
}
