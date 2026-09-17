<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\SessionDataModel;
use App\Domain\Gateway\Persister\SessionPersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<SessionDataModel>
 */
final class SessionPersister extends AbstractBaseMysqlPersister implements SessionPersisterGateway
{
    public function create(SessionDataModel $session): SessionDataModel
    {
        return $this->persistAndStampCreate($session);
    }

    public function update(SessionDataModel $session): SessionDataModel
    {
        return $this->persistAndStampUpdate($session);
    }

    /** @param SessionDataModel[] $sessions */
    public function updateMany(array $sessions): void
    {
        foreach ($sessions as $session) {
            $this->persistAndStampUpdate($session, flush: false);
        }

        $this->entityManager->flush();
    }
}
