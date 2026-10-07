<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\Validation\Constraint\Training\DefaultSetTypeKeptConstraint;
use PHPUnit\Framework\TestCase;

final class DefaultSetTypeKeptConstraintTest extends TestCase
{
    public function testASetTypeThatIsNotTheDefaultIsFreeToStayThatWay(): void
    {
        self::assertSame([], DefaultSetTypeKeptConstraint::validate($this->setType(isDefault: false), false));
    }

    public function testAnActiveSetTypeMayBecomeTheDefault(): void
    {
        self::assertSame([], DefaultSetTypeKeptConstraint::validate($this->setType(isDefault: false), true));
    }

    public function testTheDefaultMayStayTheDefault(): void
    {
        self::assertSame([], DefaultSetTypeKeptConstraint::validate($this->setType(isDefault: true), true));
    }

    /** The default is never dropped, only handed over: a set logged without a type has to get one. */
    public function testTheDefaultCannotSimplyBeUnset(): void
    {
        self::assertSame(
            ['isDefaultType' => [DefaultSetTypeKeptConstraint::DEFAULT_REQUIRED]],
            DefaultSetTypeKeptConstraint::validate($this->setType(isDefault: true), false),
        );
    }

    /** No new set may take a retired type, so it cannot be the one they take by default. */
    public function testARetiredSetTypeCannotBecomeTheDefault(): void
    {
        self::assertSame(
            ['isDefaultType' => [DefaultSetTypeKeptConstraint::DEFAULT_INACTIVE]],
            DefaultSetTypeKeptConstraint::validate($this->setType(isDefault: false, isActive: false), true),
        );
    }

    private function setType(bool $isDefault, bool $isActive = true): SetTypeDataModel
    {
        $setType = new SetTypeDataModel();
        $setType->isDefaultType = $isDefault;
        $setType->isActive = $isActive;

        return $setType;
    }
}
