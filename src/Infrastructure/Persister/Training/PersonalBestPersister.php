<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\PersonalBestDataModel;
use App\Domain\Gateway\Persister\Training\PersonalBestPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\Common\Collections\AbstractLazyCollection;

use function array_merge;
use function array_pop;
use function array_values;
use function implode;

/**
 * @extends AbstractBaseMysqlPersister<PersonalBestDataModel>
 */
final class PersonalBestPersister extends AbstractBaseMysqlPersister implements PersonalBestPersisterGateway
{
    /**
     * @param list<PersonalBestDataModel> $previous
     * @param list<PersonalBestDataModel> $rebuilt
     *
     * @return list<PersonalBestDataModel>
     */
    public function replace(array $previous, array $rebuilt): array
    {
        // A rebuilt row the same as one there now keeps that one: a progression rebuilt unchanged
        // writes nothing.
        $standing = [];
        foreach ($previous as $row) {
            $standing[self::sameness($row)][] = $row;
        }

        $created = [];
        foreach ($rebuilt as $row) {
            $sameness = self::sameness($row);
            if ([] !== ($standing[$sameness] ?? [])) {
                array_pop($standing[$sameness]);

                continue;
            }

            $created[] = $row;
        }
        $removed = array_merge(...array_values($standing));

        if ([] === $created && [] === $removed) {
            return [];
        }

        // The set's own list follows, so a workout read in this request answers its new records.
        // One not loaded yet is left alone: it will be read as the flush leaves it.
        foreach ($removed as $row) {
            $records = $row->set?->personalBests;
            if (null !== $records && (false === $records instanceof AbstractLazyCollection || true === $records->isInitialized())) {
                $records->removeElement($row);
            }
            $this->persistDelete($row, flush: false);
        }

        foreach ($created as $row) {
            $row->set?->personalBests->add($row);
            $this->persistCreate($row, flush: false);
        }

        $this->entityManager->flush();

        return [...$removed, ...$created];
    }

    /** What makes two rows the same step of the same record, at the precision of their columns. */
    private static function sameness(PersonalBestDataModel $row): string
    {
        return implode('|', [
            $row->movement?->id,
            $row->kind,
            $row->tier,
            $row->value,
            $row->workout->id,
            $row->set?->id,
            $row->achievedAt->format('Y-m-d H:i:s'),
        ]);
    }
}
