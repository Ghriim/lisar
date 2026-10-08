<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use App\Domain\Registry\Training\SetTypeColourRegistry;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Renaming or recolouring a set type, or making it the default. Same shape as creating one: a create and an update do not
 * share a DataInput, their rules diverge the day one of them gains a field.
 */
final readonly class UpdateSetTypeDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'name_required')]
        #[Assert\Length(max: 128, maxMessage: 'name_too_long')]
        public string $name,

        #[Assert\Choice(choices: SetTypeColourRegistry::ALL, message: 'colour_unknown')]
        public string $colour,

        /** Only ever set to true: the default moves by being given to another type. */
        public bool $isDefaultType = false,

        /** Whether its sets can set a personal best. On unless switched off: a warm-up is the exception. */
        public bool $countsForPersonalBests = true,
    ) {
    }
}
