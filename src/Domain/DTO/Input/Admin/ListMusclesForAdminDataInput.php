<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Admin;

use App\Domain\DTO\Input\DataInputInterface;

/**
 * The back-office muscle list's filters, each omitted meaning "any": the muscle's own status, and
 * the group it sits in. They combine.
 */
final readonly class ListMusclesForAdminDataInput implements DataInputInterface
{
    public function __construct(
        public ?bool $isActive = null,

        // A group that does not exist is a filter matching nothing, not an error.
        public ?int $muscleGroupId = null,
    ) {
    }
}
