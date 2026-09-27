<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Admin;

use App\Domain\DTO\Input\DataInputInterface;

/**
 * The back-office equipment list's one filter: active, retired, or both. Omitted means both.
 */
final readonly class ListEquipmentsForAdminDataInput implements DataInputInterface
{
    public function __construct(
        public ?bool $isActive = null,
    ) {
    }
}
