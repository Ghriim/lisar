<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output;

/**
 * The generic envelope every paginated list answers with, so that no endpoint invents its own
 * shape and a table on either front end can be written once.
 *
 * @template TItem of object
 */
final class PaginatedListDataOutput
{
    /** @var list<TItem> */
    public array $items = [];

    /** How many rows match, ignoring pagination. */
    public int $total = 0;

    public int $page = 1;

    public int $perPage = 25;
}
