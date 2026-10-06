<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\SetTypeDataModel;
use App\Domain\Validation\Constraint\Workout\SetTypeNameAvailableConstraint;
use PHPUnit\Framework\TestCase;

final class SetTypeNameAvailableConstraintTest extends TestCase
{
    public function testAFreeNameIsAccepted(): void
    {
        self::assertSame([], SetTypeNameAvailableConstraint::validate(null));
    }

    public function testANameAnotherRowCarriesIsRefused(): void
    {
        self::assertSame(
            ['name' => [SetTypeNameAvailableConstraint::NAME_ALREADY_USED]],
            SetTypeNameAvailableConstraint::validate($this->row(7)),
        );
    }

    public function testTheRowBeingRenamedMayKeepItsOwnName(): void
    {
        self::assertSame([], SetTypeNameAvailableConstraint::validate($this->row(7), 7));
    }

    public function testARenameOntoAnotherRowIsRefused(): void
    {
        self::assertArrayHasKey('name', SetTypeNameAvailableConstraint::validate($this->row(7), 8));
    }

    private function row(int $id): SetTypeDataModel
    {
        $row = new SetTypeDataModel();
        $row->id = $id;
        $row->name = 'Dropset';

        return $row;
    }
}
