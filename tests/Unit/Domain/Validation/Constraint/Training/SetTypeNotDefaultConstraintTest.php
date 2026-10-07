<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\Validation\Constraint\Training\SetTypeNotDefaultConstraint;
use PHPUnit\Framework\TestCase;

final class SetTypeNotDefaultConstraintTest extends TestCase
{
    public function testAnyOtherSetTypeMayBeRetired(): void
    {
        self::assertSame([], SetTypeNotDefaultConstraint::validate($this->setType(false)));
    }

    public function testTheDefaultMayNot(): void
    {
        self::assertSame(['id' => [SetTypeNotDefaultConstraint::IS_THE_DEFAULT]], SetTypeNotDefaultConstraint::validate($this->setType(true)));
    }

    public function testItAddsToTheViolationsAlreadyThere(): void
    {
        self::assertSame(
            ['id' => ['set_type_in_use', SetTypeNotDefaultConstraint::IS_THE_DEFAULT]],
            SetTypeNotDefaultConstraint::validate($this->setType(true), ['id' => ['set_type_in_use']]),
        );
    }

    private function setType(bool $isDefault): SetTypeDataModel
    {
        $setType = new SetTypeDataModel();
        $setType->isDefaultType = $isDefault;

        return $setType;
    }
}
