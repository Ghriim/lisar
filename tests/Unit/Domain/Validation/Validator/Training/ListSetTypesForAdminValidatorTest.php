<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\Admin\ListSetTypesForAdminDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\Training\ListSetTypesForAdminValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ListSetTypesForAdminValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** Every filter is optional: none given is a valid list. */
    public function testItAcceptsNoFilter(): void
    {
        $this->expectNotToPerformAssertions();

        (new ListSetTypesForAdminValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()))->validate($this->currentAdmin(), new ListSetTypesForAdminDataInput());
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            (new ListSetTypesForAdminValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()))->validate($this->anyCurrentUser(), new ListSetTypesForAdminDataInput());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ListSetTypesForAdminValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }
}
