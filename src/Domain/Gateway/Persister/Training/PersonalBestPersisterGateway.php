<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\PersonalBestDataModel;

interface PersonalBestPersisterGateway
{
    /**
     * Swaps a progression for the one rebuilt in its place, in one flush.
     *
     * @param list<PersonalBestDataModel> $previous the rows there now
     * @param list<PersonalBestDataModel> $rebuilt  the rows to take their place
     */
    public function replace(array $previous, array $rebuilt): void;
}
