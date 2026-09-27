<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Admin;

use App\Domain\DTO\Input\DataInputInterface;

/**
 * The back-office equipment list's filters, each omitted meaning "either": active or retired, with
 * a load or without, with a distance or without. They combine.
 */
final readonly class ListEquipmentsForAdminDataInput implements DataInputInterface
{
    public function __construct(
        public ?bool $isActive = null,

        public ?bool $hasWeight = null,

        public ?bool $hasDistance = null,
    ) {
    }
}
