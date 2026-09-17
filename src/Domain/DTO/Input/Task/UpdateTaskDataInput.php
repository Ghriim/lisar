<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Task;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\Input\DataInputInterface;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

use function in_array;

/**
 * Editing a task. Deliberately without parentId: a subtask cannot be moved under another parent
 * nor promoted to a task of its own.
 */
final readonly class UpdateTaskDataInput implements DataInputInterface
{
    /**
     * @param list<string> $tags the whole set, not an addition: what is absent is removed
     */
    public function __construct(
        #[Assert\NotBlank(message: 'title_required')]
        #[Assert\Length(max: 255, maxMessage: 'title_too_long')]
        public string $title,

        #[Assert\Length(max: 10000, maxMessage: 'description_too_long')]
        public ?string $description = null,

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
    ) {
    }

    public function getDueDate(): ?DateTimeImmutable
    {
        return DateDataTransformer::dayStringToDate($this->dueDate);
    }

    /**
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
