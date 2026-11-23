<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\ReorderRoutineBlocksDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineBlockOrderConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * What `ReorderRoutineBlocksUseCase` checks before it acts: that the routine is the caller's, then the new order.
 *
 * @extends AbstractBaseValidator<ReorderRoutineBlocksDataInput>
 */
final readonly class ReorderRoutineBlocksValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'reorder_routine_blocks_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, ReorderRoutineBlocksDataInput $input, RoutineDataModel $routine): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $routine->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = RoutineBlockOrderConstraint::validate($input->blockIds, $routine, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
