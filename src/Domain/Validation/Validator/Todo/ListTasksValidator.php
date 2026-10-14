<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\ListTasksDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * The filters' shape. No rule beyond it yet: the next one lands here, as a Constraint.
 *
 * @extends AbstractBaseValidator<ListTasksDataInput>
 */
final readonly class ListTasksValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'list_tasks_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, ListTasksDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
