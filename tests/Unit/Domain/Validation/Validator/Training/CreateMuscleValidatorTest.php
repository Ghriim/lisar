<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\DTO\Input\Training\CreateMuscleDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\MuscleGroupUsableConstraint;
use App\Domain\Validation\Constraint\Training\MuscleNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\CreateMuscleValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateMuscleValidatorTest extends TestCase
{
    use CurrentUserTrait;

    private CreateMuscleValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateMuscleValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFreeNameInAnActiveGroup(): void
    {
        $this->validator->validate($this->currentAdmin(), new CreateMuscleDataInput('Lats', 1), null, $this->group(1, true));

        $this->expectNotToPerformAssertions();
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), new CreateMuscleDataInput('Lats', 1), null, $this->group(1, true));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMuscleValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsABlankName(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreateMuscleDataInput('', 1), null, $this->group(1, true));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMuscleValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    public function testItRejectsAMissingGroup(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreateMuscleDataInput('Lats', 1), null, null);
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
            $this->validator->validate($this->currentAdmin(), new CreateMuscleDataInput('Lats', 2), $other, $this->group(2, false));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MuscleNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
            self::assertContains(MuscleGroupUsableConstraint::MUSCLE_GROUP_INACTIVE, $exception->violations['muscleGroupId']);
        }
    }

    private function group(int $id, bool $isActive): MuscleGroupDataModel
    {
        $group = new MuscleGroupDataModel();
        $group->id = $id;
        $group->name = 'Back';
        $group->isActive = $isActive;

        return $group;
    }
}
