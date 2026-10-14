<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\PriorityDataModel;
use App\Domain\DTO\Input\Todo\Admin\UpdatePriorityDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Todo\DefaultPriorityKeptConstraint;
use App\Domain\Validation\Validator\Todo\Admin\UpdatePriorityValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdatePriorityValidatorTest extends TestCase
{
    use CurrentUserTrait;

    private UpdatePriorityValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdatePriorityValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsARename(): void
    {
        $this->validator->validate(
            $this->currentAdmin(),
            new UpdatePriorityDataInput('Critique', 5, '#ff00aa'),
            $this->buildPriority(7, false),
            null,
        );

        $this->expectNotToPerformAssertions();
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            $this->validator->validate(
                $this->anyCurrentUser(),
                new UpdatePriorityDataInput('Critique', 5, '#ff00aa'),
                $this->buildPriority(7, false),
                null,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdatePriorityValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testAPriorityMayKeepItsOwnLabel(): void
    {
        $priority = $this->buildPriority(7, false);

        $this->validator->validate($this->currentAdmin(), new UpdatePriorityDataInput('High', 5, '#ff00aa'), $priority, $priority);

        $this->expectNotToPerformAssertions();
    }

    public function testItRefusesToUnsetTheDefault(): void
    {
        try {
            $this->validator->validate(
                $this->currentAdmin(),
                new UpdatePriorityDataInput('High', 5, '#ff00aa', false),
                $this->buildPriority(7, true),
                null,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdatePriorityValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(
                DefaultPriorityKeptConstraint::DEFAULT_PRIORITY_REQUIRED,
                $exception->violations['isDefault'],
            );
        }
    }

    private function buildPriority(int $id, bool $isDefault): PriorityDataModel
    {
        $priority = new PriorityDataModel();
        $priority->id = $id;
        $priority->label = 'High';
        $priority->isDefault = $isDefault;

        return $priority;
    }
}
