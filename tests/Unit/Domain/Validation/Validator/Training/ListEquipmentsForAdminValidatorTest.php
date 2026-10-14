<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\Admin\ListEquipmentsForAdminDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\Training\ListEquipmentsForAdminValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ListEquipmentsForAdminValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** Every filter is optional: none given is a valid list. */
    public function testItAcceptsNoFilter(): void
    {
        $this->expectNotToPerformAssertions();

        (new ListEquipmentsForAdminValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()))->validate($this->currentAdmin(), new ListEquipmentsForAdminDataInput());
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            (new ListEquipmentsForAdminValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()))->validate($this->anyCurrentUser(), new ListEquipmentsForAdminDataInput());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ListEquipmentsForAdminValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }
}
