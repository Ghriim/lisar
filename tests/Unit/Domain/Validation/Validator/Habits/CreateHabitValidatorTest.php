<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Habits;

use App\Domain\DTO\Input\Habits\CreateHabitDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\Habits\CreateHabitValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** The shapes the habit catalogue accepts, and the incoherent ones it refuses. */
final class CreateHabitValidatorTest extends TestCase
{
    use CurrentUserTrait;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testItAcceptsAManualHabit(): void
    {
        $this->validatorUnderTest()->validate(
            $this->currentAdmin(),
            new CreateHabitDataInput('Lire', HabitSourceRegistry::MANUAL),
            null,
        );

        $this->expectNotToPerformAssertions();
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            $this->validatorUnderTest()->validate($this->anyCurrentUser(), new CreateHabitDataInput(
                'Marcher',
                HabitSourceRegistry::TRACKER,
                HabitTrackerRegistry::STEPS,
                5000,
            ), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateHabitValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItAcceptsATrackerHabit(): void
    {
        $this->validatorUnderTest()->validate($this->currentAdmin(), new CreateHabitDataInput(
            'Marcher',
            HabitSourceRegistry::TRACKER,
            HabitTrackerRegistry::STEPS,
            5000,
        ), null);

        $this->expectNotToPerformAssertions();
    }

    /** A tracker habit that names no tracker and no mark is incoherent — and both are reported at once. */
    public function testItRefusesATrackerHabitWithoutATrackerOrAMark(): void
    {
        try {
            $this->validatorUnderTest()->validate(
                $this->currentAdmin(),
                new CreateHabitDataInput('Marcher', HabitSourceRegistry::TRACKER),
                null,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('tracker_kind_unknown', $exception->violations['trackerKind']);
            self::assertContains('tracker_threshold_required', $exception->violations['trackerThreshold']);
        }
    }

    public function testItRefusesABlankName(): void
    {
        try {
            $this->validatorUnderTest()->validate(
                $this->currentAdmin(),
                new CreateHabitDataInput('', HabitSourceRegistry::MANUAL),
                null,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    private function validatorUnderTest(): CreateHabitValidator
    {
        return new CreateHabitValidator($this->validator);
    }
}
