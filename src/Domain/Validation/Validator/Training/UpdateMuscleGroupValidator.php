<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateMuscleGroupDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\MuscleGroupNameAvailableConstraint;
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
    public function validate(UserDataModel $currentUser, UpdateMuscleGroupDataInput $input, MuscleGroupDataModel $muscleGroup, ?MuscleGroupDataModel $withSameName): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = MuscleGroupNameAvailableConstraint::validate($withSameName, $muscleGroup->id, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
