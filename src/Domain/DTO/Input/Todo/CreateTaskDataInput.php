<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Todo;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\Input\DataInputInterface;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creating a task, or a subtask when parentId is set: a subtask carries the very same fields.
 */
final readonly class CreateTaskDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'title_required')]
        // Upper bound imposed by the column, not a business rule.
        #[Assert\Length(max: 255, maxMessage: 'title_too_long')]
        public string $title,

        #[Assert\Length(max: 10000, maxMessage: 'description_too_long')]
        public ?string $description = null,

        // A calendar day, "YYYY-MM-DD". No time of day: scheduling is another domain.
        #[Assert\Date(message: 'due_date_invalid')]
        public ?string $dueDate = null,

        #[Assert\Choice(choices: TaskPriorityRegistry::ALL, message: 'priority_unknown')]
        public ?string $priority = null,

        #[Assert\Positive(message: 'category_invalid')]
        public ?int $categoryId = null,

        #[Assert\Positive(message: 'parent_task_invalid')]
        public ?int $parentId = null,
    ) {
    }

    public function getDueDate(): ?DateTimeImmutable
    {
        return DateDataTransformer::dayStringToDate($this->dueDate);
    }
}
