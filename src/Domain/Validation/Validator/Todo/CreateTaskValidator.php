<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\PriorityDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\CreateTaskDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Todo\CategoryUsableConstraint;
use App\Domain\Validation\Constraint\Todo\ParentTaskUsableConstraint;
use App\Domain\Validation\Constraint\Todo\PriorityExistsConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreateTaskDataInput>
 */
final readonly class CreateTaskValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_task_invalid';

    /**
     * @param PriorityDataModel|null $priority the priority the caller asked for, already loaded
     * @param CategoryDataModel|null $category the category the caller asked for, already loaded
     * @param TaskDataModel|null     $parent   the parent task the caller asked for, already loaded
     *
     * @throws ValidationException
     */
    public function validate(
        CreateTaskDataInput $input,
        ?PriorityDataModel $priority,
        ?CategoryDataModel $category,
        ?TaskDataModel $parent,
        UserDataModel $owner,
    ): void {
        $violations = $this->getViolations($input);
        $violations = PriorityExistsConstraint::validate($input->priorityId, $priority, $violations);
        $violations = CategoryUsableConstraint::validate($input->categoryId, $category, $owner, $violations);
        $violations = ParentTaskUsableConstraint::validate($input->parentId, $parent, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
