<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Todo;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * One of the account's TODOs — its default when none is named. A done task leaves the main
 * list, so asking for the completed ones is a flag rather than another endpoint.
 */
final readonly class ListTasksDataInput implements DataInputInterface
{
    public function __construct(
        public bool $isDone = false,
        #[Assert\Positive(message: 'todo_invalid')]
        public ?int $todoId = null,
    ) {
    }
}
