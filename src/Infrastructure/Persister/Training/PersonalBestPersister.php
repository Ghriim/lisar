<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\PersonalBestDataModel;
use App\Domain\Gateway\Persister\Training\PersonalBestPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<PersonalBestDataModel>
 */
final class PersonalBestPersister extends AbstractBaseMysqlPersister implements PersonalBestPersisterGateway
{
    /**
     * @param list<PersonalBestDataModel> $previous
     * @param list<PersonalBestDataModel> $rebuilt
     */
    public function replace(array $previous, array $rebuilt): void
    {
        // The set's own list follows, so a workout read in this request answers its new records.
        foreach ($previous as $row) {
            $row->set?->personalBests->removeElement($row);
            $this->persistDelete($row, flush: false);
        }

        foreach ($rebuilt as $row) {
            $row->set?->personalBests->add($row);
            $this->persistAndStampCreate($row, flush: false);
        }

        $this->entityManager->flush();
    }
}
