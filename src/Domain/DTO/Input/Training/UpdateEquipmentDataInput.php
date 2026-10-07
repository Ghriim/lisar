<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Reconfiguring an equipment. Same shape as creating one: a create and an update do not share a
 * DataInput, their rules diverge the day one of them gains a field.
 */
final readonly class UpdateEquipmentDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'name_required')]
        #[Assert\Length(max: 128, maxMessage: 'name_too_long')]
        public string $name,

        // Whether a movement done with it tracks a load.
        public bool $hasWeight = false,

        // Whether a movement done with it tracks a distance — the rower, the bike, the treadmill.
        public bool $hasDistance = false,
    ) {
    }
}
