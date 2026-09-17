<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\SessionDataModel;

interface SessionPersisterGateway
{
    public function create(SessionDataModel $session): SessionDataModel;

    public function update(SessionDataModel $session): SessionDataModel;

    /** @param SessionDataModel[] $sessions */
    public function updateMany(array $sessions): void;
}
