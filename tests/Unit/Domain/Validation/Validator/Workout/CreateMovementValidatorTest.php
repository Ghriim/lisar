<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\DataModel\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\Input\Workout\CreateMovementDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\MovementEquipmentsConstraint;
use App\Domain\Validation\Constraint\Workout\MovementFamilyUsableConstraint;
use App\Domain\Validation\Constraint\Workout\MovementMeasureConstraint;
use App\Domain\Validation\Constraint\Workout\MovementMusclesConstraint;
use App\Domain\Validation\Constraint\Workout\MovementNameAvailableConstraint;
use App\Domain\Validation\Validator\Workout\CreateMovementValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateMovementValidatorTest extends TestCase
{
    private CreateMovementValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateMovementValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAWellFormedMovement(): void
    {
        $this->validator->validate(
            $this->input(description: 'Lie on the bench.', videoUrl: 'https://example.com/bench-press'),
            null,
            $this->family(1, true),
            $this->muscle(1),
            [$this->muscle(2)],
            [$this->equipment(1)],
        );

        $this->expectNotToPerformAssertions();
    }

    public function testItAcceptsABodyweightMovementWithNoSecondaryMuscle(): void
    {
        $this->validator->validate(
            $this->input(secondaryMuscleIds: [], equipmentIds: []),
            null,
            $this->family(1, true),
            $this->muscle(1),
            [],
            [],
        );

        $this->expectNotToPerformAssertions();
    }

    public function testItAcceptsDuplicatedIdsAsOne(): void
    {
        $this->validator->validate(
            $this->input(secondaryMuscleIds: [2, 2], equipmentIds: [1, 1]),
            null,
            $this->family(1, true),
            $this->muscle(1),
            [$this->muscle(2)],
            [$this->equipment(1)],
        );

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsTheShapeOfTheInput(): void
    {
        try {
            $this->validator->validate(
                $this->input(name: '', description: str_repeat('a', 5001), videoUrl: 'not a url'),
                null,
                $this->family(1, true),
                $this->muscle(1),
                [$this->muscle(2)],
                [$this->equipment(1)],
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMovementValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
            self::assertContains('description_too_long', $exception->violations['description']);
            self::assertContains('video_url_invalid', $exception->violations['videoUrl']);
        }
    }

    public function testItRejectsATooLongNameAndVideoUrl(): void
    {
        try {
            $this->validator->validate(
                $this->input(name: str_repeat('a', 129), videoUrl: 'https://example.com/'.str_repeat('a', 512)),
                null,
                $this->family(1, true),
                $this->muscle(1),
                [$this->muscle(2)],
                [$this->equipment(1)],
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('name_too_long', $exception->violations['name']);
            self::assertContains('video_url_too_long', $exception->violations['videoUrl']);
        }
    }

    public function testItRejectsAMissingFamilyAndMissingMuscles(): void
    {
        try {
            $this->validator->validate(
                $this->input(),
                null,
                null,
                null,
                [],
                [],
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MovementFamilyUsableConstraint::MOVEMENT_FAMILY_NOT_FOUND, $exception->violations['movementFamilyId']);
            self::assertContains(MovementMusclesConstraint::PRIMARY_MUSCLE_NOT_FOUND, $exception->violations['primaryMuscleId']);
            self::assertContains(MovementMusclesConstraint::SECONDARY_MUSCLE_NOT_FOUND, $exception->violations['secondaryMuscleIds']);
            self::assertContains(MovementEquipmentsConstraint::EQUIPMENT_NOT_FOUND, $exception->violations['equipmentIds']);
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        $other = new MovementDataModel();
        $other->id = 99;

        try {
            $this->validator->validate(
                $this->input(primaryMuscleId: 5, secondaryMuscleIds: [5, 6], equipmentIds: [7], tracksReps: false, tracksWeight: true),
                $other,
                $this->family(5, false),
                $this->muscle(5, false),
                [$this->muscle(5, false), $this->muscle(6, true, false)],
                [$this->equipment(7, false)],
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMovementValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MovementNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
            self::assertContains(MovementFamilyUsableConstraint::MOVEMENT_FAMILY_INACTIVE, $exception->violations['movementFamilyId']);
            self::assertContains(MovementMusclesConstraint::PRIMARY_MUSCLE_UNAVAILABLE, $exception->violations['primaryMuscleId']);
            self::assertContains(MovementMusclesConstraint::SECONDARY_MUSCLE_UNAVAILABLE, $exception->violations['secondaryMuscleIds']);
            self::assertContains(MovementMusclesConstraint::PRIMARY_MUSCLE_ALSO_SECONDARY, $exception->violations['secondaryMuscleIds']);
            self::assertContains(MovementEquipmentsConstraint::EQUIPMENT_INACTIVE, $exception->violations['equipmentIds']);
            self::assertContains(MovementMeasureConstraint::MEASURE_REQUIRED, $exception->violations['measure']);
        }
    }

    /**
     * @param list<int> $secondaryMuscleIds
     * @param list<int> $equipmentIds
     */
    private function input(
        string $name = 'Bench press (barbell)',
        int $movementFamilyId = 1,
        int $primaryMuscleId = 1,
        ?string $description = null,
        ?string $videoUrl = null,
        array $secondaryMuscleIds = [2],
        array $equipmentIds = [1],
        bool $tracksReps = true,
        bool $tracksWeight = true,
    ): CreateMovementDataInput {
        return new CreateMovementDataInput(
            $name,
            $movementFamilyId,
            $primaryMuscleId,
            $description,
            $videoUrl,
            $secondaryMuscleIds,
            $equipmentIds,
            $tracksReps,
            $tracksWeight,
        );
    }

    private function family(int $id, bool $isActive): MovementFamilyDataModel
    {
        $family = new MovementFamilyDataModel();
        $family->id = $id;
        $family->name = 'Bench press';
        $family->isActive = $isActive;

        return $family;
    }

    private function muscle(int $id, bool $isActive = true, bool $isGroupActive = true): MuscleDataModel
    {
        $group = new MuscleGroupDataModel();
        $group->id = 100 + $id;
        $group->name = 'Group '.$id;
        $group->isActive = $isGroupActive;

        $muscle = new MuscleDataModel();
        $muscle->id = $id;
        $muscle->name = 'Muscle '.$id;
        $muscle->muscleGroup = $group;
        $muscle->isActive = $isActive;

        return $muscle;
    }

    private function equipment(int $id, bool $isActive = true): EquipmentDataModel
    {
        $equipment = new EquipmentDataModel();
        $equipment->id = $id;
        $equipment->name = 'Equipment '.$id;
        $equipment->isActive = $isActive;

        return $equipment;
    }
}
