<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;

/** What the muscle back-office tests share. */
trait MuscleTestTrait
{
    private function group(string $reference): MuscleGroupDataModel
    {
        return $this->getReference($reference, MuscleGroupDataModel::class);
    }

    private function muscle(string $reference): MuscleDataModel
    {
        return $this->getReference($reference, MuscleDataModel::class);
    }
}
