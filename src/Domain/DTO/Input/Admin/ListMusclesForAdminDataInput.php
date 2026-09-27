<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Admin;

use App\Domain\DTO\Input\DataInputInterface;

/**
 * The back-office muscle list's one filter: active, retired, or both. Omitted means both.
 */
final readonly class ListMusclesForAdminDataInput implements DataInputInterface
{
    public function __construct(
        public ?bool $isActive = null,
    ) {
    }
}
