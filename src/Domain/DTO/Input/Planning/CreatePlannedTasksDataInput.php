<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Planning;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** The day the task entries make their tasks for: today, unless a day is replayed by hand. */
final readonly class CreatePlannedTasksDataInput implements DataInputInterface
{
    public function __construct(
        // A calendar day, "YYYY-MM-DD". None: today.
        #[Assert\Date(message: 'day_invalid')]
        public ?string $day = null,
    ) {
    }
}
