<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Fixtures\Training\EquipmentFixtures;

/** What the equipment back-office tests share. */
trait EquipmentTestTrait
{
    private function barbell(): EquipmentDataModel
    {
        return $this->getReference(EquipmentFixtures::BARBELL, EquipmentDataModel::class);
    }
}
