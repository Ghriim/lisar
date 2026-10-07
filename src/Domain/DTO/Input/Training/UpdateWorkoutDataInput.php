<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * What a person says about a workout: its name, a note, how they felt. The whole of it, every
 * time — a field left out is cleared. The moments it started and finished are not part of it:
 * they are never rewritten.
 */
final readonly class UpdateWorkoutDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\Length(max: 128, maxMessage: 'name_too_long')]
        public ?string $name = null,

        #[Assert\Length(max: 5000, maxMessage: 'note_too_long')]
        public ?string $note = null,

        #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'feeling_invalid')]
        public ?int $feeling = null,
    ) {
    }

    /** Blank means none. */
    public function getName(): ?string
    {
        return null === $this->name || '' === $this->name ? null : $this->name;
    }

    /** Blank means none. */
    public function getNote(): ?string
    {
        return null === $this->note || '' === $this->note ? null : $this->note;
    }
}
