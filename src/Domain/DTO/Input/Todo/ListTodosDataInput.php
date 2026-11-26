<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Todo;

use App\Domain\DTO\Input\DataInputInterface;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use Symfony\Component\Validator\Constraints as Assert;

/** The account's TODOs, in one status — or all of them when none is named. */
final readonly class ListTodosDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\Choice(choices: TodoStatusRegistry::ALL, message: 'status_unknown')]
        public ?string $status = null,
    ) {
    }
}
