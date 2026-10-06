<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Workout;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** A page of the workout history. */
final readonly class ListWorkoutsDataInput implements DataInputInterface
{
    public const int DEFAULT_PER_PAGE = 25;
    public const int MAX_PER_PAGE = 100;

    public function __construct(
        #[Assert\Positive(message: 'page_invalid')]
        public int $page = 1,

        // Capped so that one call cannot ask for the whole history.
        #[Assert\Range(min: 1, max: self::MAX_PER_PAGE, notInRangeMessage: 'per_page_invalid')]
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
