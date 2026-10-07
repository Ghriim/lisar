<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\DTO\Input\Training\UpdateMuscleDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\MuscleGroupUsableConstraint;
use App\Domain\Validation\Constraint\Training\MuscleNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\UpdateMuscleValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateMuscleValidatorTest extends TestCase
{
    private UpdateMuscleValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateMuscleValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFreeNameInAnActiveGroup(): void
    {
        $this->validator->validate(new UpdateMuscleDataInput('Lats', 1), $this->current(), null, $this->group(1, true));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankName(): void
    {
        try {
            $this->validator->validate(new UpdateMuscleDataInput('', 1), $this->current(), null, $this->group(1, true));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateMuscleValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    public function testItRejectsAMissingGroup(): void
    {
        try {
            $this->validator->validate(new UpdateMuscleDataInput('Lats', 1), $this->current(), null, null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MuscleGroupUsableConstraint::MUSCLE_GROUP_NOT_FOUND, $exception->violations['muscleGroupId']);
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        $other = new MuscleDataModel();
        $other->id = 99;

        try {
            $this->validator->validate(new UpdateMuscleDataInput('Lats', 2), $this->current(), $other, $this->group(2, false));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MuscleNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
            self::assertContains(MuscleGroupUsableConstraint::MUSCLE_GROUP_INACTIVE, $exception->violations['muscleGroupId']);
        }
    }

    public function testAMuscleMayStayInTheInactiveGroupItSitsIn(): void
    {
        $this->validator->validate(new UpdateMuscleDataInput('Lats', 1), $this->current(), $this->current(), $this->group(1, false));

        $this->expectNotToPerformAssertions();
    }

    private function group(int $id, bool $isActive): MuscleGroupDataModel
    {
        $group = new MuscleGroupDataModel();
        $group->id = $id;
        $group->name = 'Back';
        $group->isActive = $isActive;

        return $group;
    }

    /** Named Lats, sitting in group 1. */
    private function current(): MuscleDataModel
    {
        $muscle = new MuscleDataModel();
        $muscle->id = 1;
        $muscle->name = 'Lats';
        $muscle->muscleGroup = $this->group(1, false);

        return $muscle;
    }
}
