<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\Input\Training\UpdateSetTypeDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\DefaultSetTypeKeptConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeNameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdateSetTypeDataInput>
 */
final readonly class UpdateSetTypeValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_set_type_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UpdateSetTypeDataInput $input, SetTypeDataModel $setType, ?SetTypeDataModel $withSameName): void
    {
        $violations = $this->getViolations($input);
        $violations = SetTypeNameAvailableConstraint::validate($withSameName, $setType->id, $violations);
        $violations = DefaultSetTypeKeptConstraint::validate($setType, $input->isDefaultType, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
