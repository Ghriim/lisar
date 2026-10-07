<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\Input\Training\CreateMovementDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\MovementEquipmentsConstraint;
use App\Domain\Validation\Constraint\Training\MovementFamilyUsableConstraint;
use App\Domain\Validation\Constraint\Training\MovementMeasureConstraint;
use App\Domain\Validation\Constraint\Training\MovementMusclesConstraint;
use App\Domain\Validation\Constraint\Training\MovementNameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreateMovementDataInput>
 */
final readonly class CreateMovementValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_movement_invalid';

    /**
     * @param MovementDataModel|null       $withSameName     the common movement already carrying that name, if any
     * @param MovementFamilyDataModel|null $movementFamily   the family asked for, null when none has that id
     * @param MuscleDataModel|null         $primaryMuscle    the primary muscle asked for, null when none has that id
     * @param list<MuscleDataModel>        $secondaryMuscles the secondary muscles found among the ids asked for
     * @param list<EquipmentDataModel>     $equipments       the equipments found among the ids asked for
     *
     * @throws ValidationException
     */
    public function validate(
        CreateMovementDataInput $input,
        ?MovementDataModel $withSameName,
        ?MovementFamilyDataModel $movementFamily,
        ?MuscleDataModel $primaryMuscle,
        array $secondaryMuscles,
        array $equipments,
    ): void {
        $violations = $this->getViolations($input);
        $violations = MovementNameAvailableConstraint::validate($withSameName, null, $violations);
        $violations = MovementFamilyUsableConstraint::validate($movementFamily, null, $violations);
        $violations = MovementMusclesConstraint::validate(
            $input->primaryMuscleId,
            $primaryMuscle,
            $input->getSecondaryMuscleIds(),
            $secondaryMuscles,
            null,
            $violations,
        );
        $violations = MovementEquipmentsConstraint::validate($input->getEquipmentIds(), $equipments, null, $violations);
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
