<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Task;

use App\Domain\DTO\Input\DataInputInterface;

/**
 * The account's own list. A done task leaves the main list, so asking for the completed ones is
 * a flag rather than another endpoint.
 */
final readonly class ListTasksDataInput implements DataInputInterface
{
    public function __construct(public bool $isDone = false)
    {
    }
}
