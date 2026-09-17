<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Task;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\Input\DataInputInterface;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

use function in_array;

/**
 * Creating a task, or a subtask when parentId is set: a subtask carries the very same fields.
 */
final readonly class CreateTaskDataInput implements DataInputInterface
{
    /**
     * @param list<string> $tags
     */
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

        #[Assert\Positive(message: 'priority_invalid')]
        public ?int $priorityId = null,

        #[Assert\Positive(message: 'category_invalid')]
        public ?int $categoryId = null,

        #[Assert\All([
            new Assert\NotBlank(message: 'tag_required'),
            new Assert\Length(max: 32, maxMessage: 'tag_too_long'),
        ])]
        public array $tags = [],

        #[Assert\Positive(message: 'parent_task_invalid')]
        public ?int $parentId = null,
    ) {
    }

    public function getDueDate(): ?DateTimeImmutable
    {
        return DateDataTransformer::dayStringToDate($this->dueDate);
    }

    /**
     * Trimmed, de-duplicated, empties dropped — what the person typed, tidied up.
     *
     * @return list<string>
     */
    public function getTagLabels(): array
    {
        $labels = [];
        foreach ($this->tags as $tag) {
            $label = trim($tag);
            if ('' !== $label && false === in_array($label, $labels, true)) {
                $labels[] = $label;
            }
        }

        return $labels;
    }
}
