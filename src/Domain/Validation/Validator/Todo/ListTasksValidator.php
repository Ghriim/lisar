<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\ListTasksDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Todo\TodoUsableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * The filters' shape, and the TODO asked for: one of the caller's, and active — an archived
 * TODO's tasks show nowhere.
 *
 * @extends AbstractBaseValidator<ListTasksDataInput>
 */
final readonly class ListTasksValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'list_tasks_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, ListTasksDataInput $input, ?TodoDataModel $todo): void
    {
        $violations = $this->getViolations($input);
        $violations = TodoUsableConstraint::validate($todo, $currentUser, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
