<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeNotDefaultConstraint;
use App\Domain\Validation\Validator\Training\DeactivateSetTypeValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class DeactivateSetTypeValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new DeactivateSetTypeValidator())->validate($this->currentAdmin(), $this->setType(false));
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            (new DeactivateSetTypeValidator())->validate($this->anyCurrentUser(), $this->setType(false));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeactivateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRefusesTheDefaultType(): void
    {
        $this->assertRefused(['id' => [SetTypeNotDefaultConstraint::IS_THE_DEFAULT]], fn () => (new DeactivateSetTypeValidator())->validate($this->currentAdmin(), $this->setType(true)));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeactivateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }

    private function setType(bool $isDefault): SetTypeDataModel
    {
        $setType = new SetTypeDataModel();
        $setType->isDefaultType = $isDefault;

        return $setType;
    }
}
