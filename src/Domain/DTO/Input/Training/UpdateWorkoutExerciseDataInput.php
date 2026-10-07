<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** The note on a movement as done in one workout. Left out, or blank, clears it. */
final readonly class UpdateWorkoutExerciseDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\Length(max: 5000, maxMessage: 'note_too_long')]
        public ?string $note = null,
    ) {
    }

    /** Blank means none. */
    public function getNote(): ?string
    {
        return null === $this->note || '' === $this->note ? null : $this->note;
    }
}
