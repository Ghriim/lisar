<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeNotDefaultConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeUnusedConstraint;
use App\Domain\Validation\Validator\Training\DeleteSetTypeValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class DeleteSetTypeValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new DeleteSetTypeValidator())->validate($this->currentAdmin(), $this->setType(false), 0);
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            (new DeleteSetTypeValidator())->validate($this->anyCurrentUser(), $this->setType(false), 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRefusesATypeSetsCarry(): void
    {
        $this->assertRefused(['id' => [SetTypeUnusedConstraint::IN_USE]], fn () => (new DeleteSetTypeValidator())->validate($this->currentAdmin(), $this->setType(false), 4));
    }

    public function testItRefusesTheDefaultType(): void
    {
        $this->assertRefused(['id' => [SetTypeNotDefaultConstraint::IS_THE_DEFAULT]], fn () => (new DeleteSetTypeValidator())->validate($this->currentAdmin(), $this->setType(true), 0));
    }

    public function testItAccumulatesEveryViolation(): void
    {
        $this->assertRefused(['id' => [SetTypeUnusedConstraint::IN_USE, SetTypeNotDefaultConstraint::IS_THE_DEFAULT]], fn () => (new DeleteSetTypeValidator())->validate($this->currentAdmin(), $this->setType(true), 4));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteSetTypeValidator::ERROR_CODE, $exception->errorCode);
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
