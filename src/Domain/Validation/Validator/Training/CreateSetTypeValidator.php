<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\Input\Training\CreateSetTypeDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\SetTypeNameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreateSetTypeDataInput>
 */
final readonly class CreateSetTypeValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_set_type_invalid';

    /**
     * @param SetTypeDataModel|null $withSameName the row already carrying that name, ignoring case, if any
     *
     * @throws ValidationException
     */
    public function validate(CreateSetTypeDataInput $input, ?SetTypeDataModel $withSameName): void
    {
        $violations = $this->getViolations($input);
        $violations = SetTypeNameAvailableConstraint::validate($withSameName, null, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
