<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Planning;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** The days a planning is read over, both included: a week, mostly. How long it may run is PlanningRangeConstraint's. */
final readonly class ListPlanningDataInput implements DataInputInterface
{
    public function __construct(
        // Calendar days, "YYYY-MM-DD".
        #[Assert\NotBlank(message: 'from_required')]
        #[Assert\Date(message: 'from_invalid')]
        public string $from = '',

        #[Assert\NotBlank(message: 'to_required')]
        #[Assert\Date(message: 'to_invalid')]
        public string $to = '',
    ) {
    }
}
