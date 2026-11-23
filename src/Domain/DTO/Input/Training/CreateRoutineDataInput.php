<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** A new routine, empty: its blocks, movements and sets are added on its page. */
final readonly class CreateRoutineDataInput implements DataInputInterface
{
    public const int MAX_NAME_LENGTH = 100;

    public function __construct(
        // Shorter than a workout's 128: the workout a routine starts adds the day to its name.
        #[Assert\NotBlank(message: 'name_required')]
        #[Assert\Length(max: self::MAX_NAME_LENGTH, maxMessage: 'name_too_long')]
        public string $name = '',
    ) {
    }
}
