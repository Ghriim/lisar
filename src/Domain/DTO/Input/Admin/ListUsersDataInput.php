<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Admin;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The back-office account list: filters and pagination, all optional.
 */
final readonly class ListUsersDataInput implements DataInputInterface
{
    public const int DEFAULT_PER_PAGE = 25;
    public const int MAX_PER_PAGE = 100;

    public function __construct(
        // Matched against the username and the e-mail.
        #[Assert\Length(max: 180, maxMessage: 'search_too_long')]
        public ?string $search = null,

        public ?bool $isActive = null,

        #[Assert\Positive(message: 'page_invalid')]
        public int $page = 1,

        // Capped so that one call cannot ask for the whole table.
        #[Assert\Range(min: 1, max: self::MAX_PER_PAGE, notInRangeMessage: 'per_page_invalid')]
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
