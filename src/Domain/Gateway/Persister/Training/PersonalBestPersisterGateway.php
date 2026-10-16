<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\PersonalBestDataModel;

interface PersonalBestPersisterGateway
{
    /**
     * Swaps a progression for the one rebuilt in its place, in one flush, writing only what
     * differs: a rebuilt row the same as one there now keeps it, the others are inserted, and
     * the rows nothing rebuilt matches are deleted.
     *
     * @param list<PersonalBestDataModel> $previous the rows there now
     * @param list<PersonalBestDataModel> $rebuilt  the rows to take their place
     *
     * @return list<PersonalBestDataModel> the rows it deleted and inserted, none when nothing moved
     */
    public function replace(array $previous, array $rebuilt): array;
}
