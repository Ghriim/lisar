<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateMuscleGroupDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\MuscleGroupNameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreateMuscleGroupDataInput>
 */
final readonly class CreateMuscleGroupValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_muscle_group_invalid';

    /**
     * @param MuscleGroupDataModel|null $withSameName the row already carrying that name, ignoring case, if any
     *
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, CreateMuscleGroupDataInput $input, ?MuscleGroupDataModel $withSameName): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = MuscleGroupNameAvailableConstraint::validate($withSameName, null, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
