<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Fixtures\Training\SetTypeFixtures;

/** What the set type back-office tests share. */
trait SetTypeTestTrait
{
    private function working(): SetTypeDataModel
    {
        return $this->getReference(SetTypeFixtures::WORKING, SetTypeDataModel::class);
    }

    private function dropset(): SetTypeDataModel
    {
        return $this->getReference(SetTypeFixtures::DROPSET, SetTypeDataModel::class);
    }
}
