<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

/** One record — a kind, and its tier if it has one — with how it got where it is. */
final class PersonalBestRecordDataOutput
{
    public string $kind;

    public ?float $tier = null;

    /** The record itself: the latest time it was beaten. */
    public PersonalBestDataOutput $current;

    /**
     * Every time it was beaten, oldest first; the last is `current`.
     *
     * @var list<PersonalBestDataOutput>
     */
    public array $progression = [];
}
