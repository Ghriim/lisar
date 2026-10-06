<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Workout;

use App\Domain\DTO\Input\DataInputInterface;
use App\Domain\Registry\Workout\SetTypeColourRegistry;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A set type an administrator adds. Its name must be free, ignoring case: that rule needs the
 * database, so it lives in SetTypeNameAvailableConstraint. The colour has no default: nothing is
 * preselected, the administrator picks one, like a habit's icon.
 */
final readonly class CreateSetTypeDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'name_required')]
        #[Assert\Length(max: 128, maxMessage: 'name_too_long')]
        public string $name,

        #[Assert\Choice(choices: SetTypeColourRegistry::ALL, message: 'colour_unknown')]
        public string $colour,
    ) {
    }
}
