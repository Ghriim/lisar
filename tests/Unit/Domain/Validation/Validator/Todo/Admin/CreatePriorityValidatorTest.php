<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\PriorityDataModel;
use App\Domain\DTO\Input\Todo\Admin\CreatePriorityDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Todo\PriorityLabelAvailableConstraint;
use App\Domain\Validation\Validator\Todo\Admin\CreatePriorityValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreatePriorityValidatorTest extends TestCase
{
    use CurrentUserTrait;

    private CreatePriorityValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreatePriorityValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsAPriority(): void
    {
        $this->validator->validate($this->currentAdmin(), new CreatePriorityDataInput('Critique', 5, '#ff00aa'), null);

        $this->expectNotToPerformAssertions();
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), new CreatePriorityDataInput('Critique', 5, '#ff00aa'), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreatePriorityValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsAColourThatIsNotAHexTriplet(): void
    {
        $this->assertViolatesOn(new CreatePriorityDataInput('Critique', 5, 'rouge'), 'colour', 'colour_invalid');
        $this->assertViolatesOn(new CreatePriorityDataInput('Critique', 5, '#abc'), 'colour', 'colour_invalid');
    }

    public function testItRejectsAWeightOutOfRange(): void
    {
        $this->assertViolatesOn(new CreatePriorityDataInput('Critique', -1, '#ff00aa'), 'weight', 'weight_invalid');
    }

    public function testItRejectsALabelAlreadyCarried(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreatePriorityDataInput('High'), new PriorityDataModel());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                PriorityLabelAvailableConstraint::LABEL_ALREADY_USED,
                $exception->violations['label'],
            );
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreatePriorityDataInput('', 99999, 'nope'), new PriorityDataModel());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreatePriorityValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey('label', $exception->violations);
            self::assertArrayHasKey('weight', $exception->violations);
            self::assertArrayHasKey('colour', $exception->violations);
        }
    }

    private function assertViolatesOn(CreatePriorityDataInput $input, string $field, string $errorCode): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), $input, null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains($errorCode, $exception->violations[$field]);
        }
    }
}
