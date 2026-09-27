<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Workout;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A muscle an administrator adds to a group. That the name is free across every group and that the
 * group exists and is active are database rules, in the Workout constraints.
 */
final readonly class CreateMuscleDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'name_required')]
        #[Assert\Length(max: 128, maxMessage: 'name_too_long')]
        public string $name,

        public int $muscleGroupId,
    ) {
    }
}
