<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\Validation\Constraint\Workout\MuscleNameAvailableConstraint;
use PHPUnit\Framework\TestCase;

final class MuscleNameAvailableConstraintTest extends TestCase
{
    public function testAFreeNameIsAccepted(): void
    {
        self::assertSame([], MuscleNameAvailableConstraint::validate(null));
    }

    public function testANameAnotherRowCarriesIsRefused(): void
    {
        self::assertSame(
            ['name' => [MuscleNameAvailableConstraint::NAME_ALREADY_USED]],
            MuscleNameAvailableConstraint::validate($this->row(7)),
        );
    }

    public function testTheRowBeingRenamedMayKeepItsOwnName(): void
    {
        self::assertSame([], MuscleNameAvailableConstraint::validate($this->row(7), 7));
    }

    public function testARenameOntoAnotherRowIsRefused(): void
    {
        self::assertArrayHasKey('name', MuscleNameAvailableConstraint::validate($this->row(7), 8));
    }

    private function row(int $id): MuscleDataModel
    {
        $row = new MuscleDataModel();
        $row->id = $id;
        $row->name = 'Lats';

        return $row;
    }
}
