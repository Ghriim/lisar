<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Admin;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\DTO\Input\Admin\UpdatePriorityDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Task\DefaultPriorityKeptConstraint;
use App\Domain\Validation\Constraint\Task\PriorityLabelAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdatePriorityDataInput>
 */
final readonly class UpdatePriorityValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_priority_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(
        UpdatePriorityDataInput $input,
        PriorityDataModel $priority,
        ?PriorityDataModel $withSameLabel,
    ): void {
        $violations = $this->getViolations($input);
        $violations = PriorityLabelAvailableConstraint::validate($withSameLabel, $priority->id, $violations);
        $violations = DefaultPriorityKeptConstraint::validate($priority, $input->isDefault, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
