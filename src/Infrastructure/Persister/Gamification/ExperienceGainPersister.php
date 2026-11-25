<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Gamification;

use App\Domain\DTO\DataModel\Gamification\ExperienceGainDataModel;
use App\Domain\Gateway\Persister\Gamification\ExperienceGainPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * Raises nothing for now. Announcing a level gained will hang on the gains' writes, once they are
 * wired.
 *
 * @extends AbstractBaseMysqlPersister<ExperienceGainDataModel>
 */
final class ExperienceGainPersister extends AbstractBaseMysqlPersister implements ExperienceGainPersisterGateway
{
    public function create(ExperienceGainDataModel $gain): ExperienceGainDataModel
    {
        return $this->persistCreate($gain);
    }
}
