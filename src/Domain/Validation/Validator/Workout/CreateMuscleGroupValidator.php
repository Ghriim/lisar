<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\Input\Workout\CreateMuscleGroupDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\MuscleGroupNameAvailableConstraint;
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
    public function validate(CreateMuscleGroupDataInput $input, ?MuscleGroupDataModel $withSameName): void
    {
        $violations = $this->getViolations($input);
        $violations = MuscleGroupNameAvailableConstraint::validate($withSameName, null, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
