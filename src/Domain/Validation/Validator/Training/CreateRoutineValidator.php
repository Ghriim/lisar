<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateRoutineDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * What `CreateRoutineUseCase` checks before it acts: the name.
 *
 * @extends AbstractBaseValidator<CreateRoutineDataInput>
 */
final readonly class CreateRoutineValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_routine_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, CreateRoutineDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
