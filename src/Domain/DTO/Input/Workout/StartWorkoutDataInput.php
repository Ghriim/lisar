<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Workout;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Starting a workout. Nothing is required: it starts now, empty, and is filled as it goes.
 */
final readonly class StartWorkoutDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\Length(max: 128, maxMessage: 'name_too_long')]
        public ?string $name = null,
    ) {
    }

    /** Blank means none. */
    public function getName(): ?string
    {
        return null === $this->name || '' === $this->name ? null : $this->name;
    }
}
