<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\TagDataModel;
use App\Domain\Gateway\Persister\TagPersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<TagDataModel>
 */
final class TagPersister extends AbstractBaseMysqlPersister implements TagPersisterGateway
{
    public function create(TagDataModel $tag): TagDataModel
    {
        return $this->persistAndStampCreate($tag);
    }

    /** @param TagDataModel[] $tags */
    public function createMany(array $tags): void
    {
        foreach ($tags as $tag) {
            $this->persistAndStampCreate($tag, flush: false);
        }

        $this->entityManager->flush();
    }
}
