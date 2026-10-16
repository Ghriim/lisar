<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\UpdateTaskDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\CategoryUsableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdateTaskDataInput>
 */
final readonly class UpdateTaskValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_task_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(
        UserDataModel $currentUser,
        TaskDataModel $task,
        UpdateTaskDataInput $input,
        ?CategoryDataModel $category,
        UserDataModel $owner,
    ): void {
        $access = IsOwnerConstraint::validate($currentUser, $task->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = CategoryUsableConstraint::validate($input->categoryId, $category, $owner, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
