<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

use function in_array;

/**
 * Reconfiguring a common movement. Its own DataInput, like every update: it shares the create's
 * fields today, not its future.
 */
final readonly class UpdateMovementDataInput implements DataInputInterface
{
    /**
     * @param list<int> $secondaryMuscleIds
     * @param list<int> $equipmentIds       none at all: done with the body's own weight
     */
    public function __construct(
        #[Assert\NotBlank(message: 'name_required')]
        #[Assert\Length(max: 128, maxMessage: 'name_too_long')]
        public string $name,

        public int $movementFamilyId,

        public int $primaryMuscleId,

        #[Assert\Length(max: 5000, maxMessage: 'description_too_long')]
        public ?string $description = null,

        #[Assert\Url(message: 'video_url_invalid', requireTld: true)]
        #[Assert\Length(max: 512, maxMessage: 'video_url_too_long')]
        public ?string $videoUrl = null,

        #[Assert\All([new Assert\Type(type: 'int', message: 'muscle_id_invalid')])]
        public array $secondaryMuscleIds = [],

        #[Assert\All([new Assert\Type(type: 'int', message: 'equipment_id_invalid')])]
        public array $equipmentIds = [],

        public bool $tracksReps = false,

        public bool $tracksWeight = false,

        public bool $tracksDuration = false,

        public bool $tracksDistance = false,

        public bool $isUnilateral = false,
    ) {
    }

    /** Trimmed; blank means none. */
    public function getDescription(): ?string
    {
        return self::blankToNull($this->description);
    }

    /** Trimmed; blank means none. */
    public function getVideoUrl(): ?string
    {
        return self::blankToNull($this->videoUrl);
    }

    /** @return list<int> de-duplicated */
    public function getSecondaryMuscleIds(): array
    {
        return self::unique($this->secondaryMuscleIds);
    }

    /** @return list<int> de-duplicated */
    public function getEquipmentIds(): array
    {
        return self::unique($this->equipmentIds);
    }

    private static function blankToNull(?string $value): ?string
    {
        $trimmed = null === $value ? '' : trim($value);

        return '' === $trimmed ? null : $trimmed;
    }

    /**
     * @param list<int> $ids
     *
     * @return list<int>
     */
    private static function unique(array $ids): array
    {
        $unique = [];
        foreach ($ids as $id) {
            if (false === in_array($id, $unique, true)) {
                $unique[] = $id;
            }
        }

        return $unique;
    }
}
