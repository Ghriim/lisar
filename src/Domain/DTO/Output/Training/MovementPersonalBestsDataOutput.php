<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

/** A movement and its records, which may be none. */
final class MovementPersonalBestsDataOutput
{
    public int $movementId;

    public string $movementName;

    public int $movementFamilyId;

    public string $movementFamilyName;

    /** Retired since: listed only because it holds records. */
    public bool $isOffered;

    /**
     * In PersonalBestKindRegistry's order, then by tier.
     *
     * @var list<PersonalBestRecordDataOutput>
     */
    public array $records = [];
}
