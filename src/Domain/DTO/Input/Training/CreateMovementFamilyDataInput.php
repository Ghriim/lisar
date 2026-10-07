<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A movement family an administrator adds. Its name must be free, ignoring case —
 * MovementFamilyNameAvailableConstraint.
 */
final readonly class CreateMovementFamilyDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'name_required')]
        #[Assert\Length(max: 128, maxMessage: 'name_too_long')]
        public string $name,
    ) {
    }
}
