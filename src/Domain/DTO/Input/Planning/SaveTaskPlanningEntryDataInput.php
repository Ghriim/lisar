<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Planning;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A task entry, whole: the model its tasks are made from, and when. Creating and changing one
 * send the same, and a change replaces it all. The category must be one the caller may use, and
 * the TODO one of the caller's active ones: that needs the database, and is the validator's.
 */
final readonly class SaveTaskPlanningEntryDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'title_required')]
        // Upper bound imposed by the column, not a business rule.
        #[Assert\Length(max: 255, maxMessage: 'title_too_long')]
        public string $title = '',

        #[Assert\Length(max: 10000, maxMessage: 'description_too_long')]
        public ?string $description = null,

        #[Assert\Positive(message: 'category_invalid')]
        public ?int $categoryId = null,

        #[Assert\Valid]
        public PlanningScheduleDataInput $schedule = new PlanningScheduleDataInput(),

        // The TODO its tasks are made in; the account's default when none is named.
        #[Assert\Positive(message: 'todo_invalid')]
        public ?int $todoId = null,
    ) {
    }
}
