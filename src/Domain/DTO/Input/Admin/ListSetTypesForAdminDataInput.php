<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Admin;

use App\Domain\DTO\Input\DataInputInterface;

/** The back-office set type list's filter: active or retired, omitted meaning either. */
final readonly class ListSetTypesForAdminDataInput implements DataInputInterface
{
    public function __construct(
        public ?bool $isActive = null,
    ) {
    }
}
