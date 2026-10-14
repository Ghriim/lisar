<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\Admin\ListMusclesForAdminDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\Training\ListMusclesForAdminValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ListMusclesForAdminValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** Every filter is optional: none given is a valid list. */
    public function testItAcceptsNoFilter(): void
    {
        $this->expectNotToPerformAssertions();

        (new ListMusclesForAdminValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()))->validate($this->currentAdmin(), new ListMusclesForAdminDataInput());
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            (new ListMusclesForAdminValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()))->validate($this->anyCurrentUser(), new ListMusclesForAdminDataInput());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ListMusclesForAdminValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }
}
