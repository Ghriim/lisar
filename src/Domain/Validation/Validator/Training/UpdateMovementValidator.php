<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\Input\Training\UpdateMovementDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\MovementEquipmentsConstraint;
use App\Domain\Validation\Constraint\Training\MovementFamilyUsableConstraint;
use App\Domain\Validation\Constraint\Training\MovementMeasureConstraint;
use App\Domain\Validation\Constraint\Training\MovementMusclesConstraint;
use App\Domain\Validation\Constraint\Training\MovementNameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdateMovementDataInput>
 */
final readonly class UpdateMovementValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_movement_invalid';

    /**
     * @param MovementDataModel            $movement         the movement being changed, as it is before the change
     * @param MovementDataModel|null       $withSameName     the common movement already carrying that name, if any
     * @param MovementFamilyDataModel|null $movementFamily   the family asked for, null when none has that id
     * @param MuscleDataModel|null         $primaryMuscle    the primary muscle asked for, null when none has that id
     * @param list<MuscleDataModel>        $secondaryMuscles the secondary muscles found among the ids asked for
     * @param list<EquipmentDataModel>     $equipments       the equipments found among the ids asked for
     *
     * @throws ValidationException
     */
    public function validate(
        UpdateMovementDataInput $input,
        MovementDataModel $movement,
        ?MovementDataModel $withSameName,
        ?MovementFamilyDataModel $movementFamily,
        ?MuscleDataModel $primaryMuscle,
        array $secondaryMuscles,
        array $equipments,
    ): void {
        $violations = $this->getViolations($input);
        $violations = MovementNameAvailableConstraint::validate($withSameName, $movement->id, $violations);
        $violations = MovementFamilyUsableConstraint::validate($movementFamily, $movement->movementFamily->id, $violations);
        $violations = MovementMusclesConstraint::validate(
            $input->primaryMuscleId,
            $primaryMuscle,
            $input->getSecondaryMuscleIds(),
            $secondaryMuscles,
            $movement,
            $violations,
        );
        $violations = MovementEquipmentsConstraint::validate($input->getEquipmentIds(), $equipments, $movement, $violations);
        $violations = MovementMeasureConstraint::validate(
            $input->tracksReps,
            $input->tracksDuration,
            $input->tracksDistance,
            $violations,
        );

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
