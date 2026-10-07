<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\ListWorkoutsDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * The pagination bounds.
 *
 * @extends AbstractBaseValidator<ListWorkoutsDataInput>
 */
final readonly class ListWorkoutsValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'list_workouts_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(ListWorkoutsDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
