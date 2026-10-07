<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Renaming a muscle or moving it to another group. Moving it into an inactive group is refused;
 * staying in the inactive group it already sits in is not a move, and is allowed.
 */
final readonly class UpdateMuscleDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'name_required')]
        #[Assert\Length(max: 128, maxMessage: 'name_too_long')]
        public string $name,

        public int $muscleGroupId,
    ) {
    }
}
