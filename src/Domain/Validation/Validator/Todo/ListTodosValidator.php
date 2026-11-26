<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\ListTodosDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * The filter's shape. No rule beyond it yet: the next one lands here, as a Constraint.
 *
 * @extends AbstractBaseValidator<ListTodosDataInput>
 */
final readonly class ListTodosValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'list_todos_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, ListTodosDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
