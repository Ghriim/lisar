<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\PriorityDataModel;
use App\Domain\DTO\Input\Todo\Admin\CreatePriorityDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Todo\PriorityLabelAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreatePriorityDataInput>
 */
final readonly class CreatePriorityValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_priority_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(CreatePriorityDataInput $input, ?PriorityDataModel $withSameLabel): void
    {
        $violations = $this->getViolations($input);
        $violations = PriorityLabelAvailableConstraint::validate($withSameLabel, null, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
