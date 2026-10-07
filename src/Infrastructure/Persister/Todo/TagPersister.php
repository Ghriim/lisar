<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Todo;

use App\Domain\DTO\DataModel\Todo\TagDataModel;
use App\Domain\Gateway\Persister\Todo\TagPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

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
