<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Admin;

use App\Domain\DTO\Input\DataInputInterface;

/**
 * The back-office movement list's filters, each omitted meaning "any". They combine. A muscle or a
 * group matches a movement through its primary muscle or any secondary one; an id matching nothing
 * is a filter matching nothing, not an error.
 */
final readonly class ListMovementsForAdminDataInput implements DataInputInterface
{
    public function __construct(
        public ?bool $isActive = null,
        public ?int $movementFamilyId = null,
        public ?int $muscleGroupId = null,
        public ?int $muscleId = null,
        public ?int $equipmentId = null,
    ) {
    }
}
