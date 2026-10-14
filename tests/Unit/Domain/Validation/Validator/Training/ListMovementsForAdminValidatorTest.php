<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\Admin\ListMovementsForAdminDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\Training\ListMovementsForAdminValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ListMovementsForAdminValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** Every filter is optional: none given is a valid list. */
    public function testItAcceptsNoFilter(): void
    {
        $this->expectNotToPerformAssertions();

        (new ListMovementsForAdminValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()))->validate($this->currentAdmin(), new ListMovementsForAdminDataInput());
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            (new ListMovementsForAdminValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()))->validate($this->anyCurrentUser(), new ListMovementsForAdminDataInput());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ListMovementsForAdminValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }
}
