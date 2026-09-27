<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\Input\Workout\UpdateMuscleGroupDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\MuscleGroupNameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdateMuscleGroupDataInput>
 */
final readonly class UpdateMuscleGroupValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_muscle_group_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UpdateMuscleGroupDataInput $input, MuscleGroupDataModel $muscleGroup, ?MuscleGroupDataModel $withSameName): void
    {
        $violations = $this->getViolations($input);
        $violations = MuscleGroupNameAvailableConstraint::validate($withSameName, $muscleGroup->id, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
