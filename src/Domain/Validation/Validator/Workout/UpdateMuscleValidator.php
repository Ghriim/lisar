<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\Input\Workout\UpdateMuscleDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\MuscleGroupUsableConstraint;
use App\Domain\Validation\Constraint\Workout\MuscleNameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdateMuscleDataInput>
 */
final readonly class UpdateMuscleValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_muscle_invalid';

    /**
     * @param MuscleDataModel           $muscle       the muscle being changed, still in its current group
     * @param MuscleDataModel|null      $withSameName the muscle already carrying that name in any group, if any
     * @param MuscleGroupDataModel|null $muscleGroup  the group asked for, null when none has that id
     *
     * @throws ValidationException
     */
    public function validate(
        UpdateMuscleDataInput $input,
        MuscleDataModel $muscle,
        ?MuscleDataModel $withSameName,
        ?MuscleGroupDataModel $muscleGroup,
    ): void {
        $violations = $this->getViolations($input);
        $violations = MuscleNameAvailableConstraint::validate($withSameName, $muscle->id, $violations);
        $violations = MuscleGroupUsableConstraint::validate($muscleGroup, $muscle->muscleGroup->id, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
