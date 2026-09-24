<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Step;

use App\Domain\DTO\Input\Step\SaveStepDayDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\Step\SaveStepDayValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** The one shape the step domain accepts, and the counts it refuses. */
final class SaveStepDayValidatorTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testItAcceptsAPlausibleCount(): void
    {
        (new SaveStepDayValidator($this->validator))->validate(new SaveStepDayDataInput(8432));

        $this->expectNotToPerformAssertions();
    }

    /** The bounds themselves are allowed: they are the filter's edge, not outside it. */
    public function testItAcceptsTheBounds(): void
    {
        $validator = new SaveStepDayValidator($this->validator);

        // Zero is a real answer: a day one did not walk.
        $validator->validate(new SaveStepDayDataInput(0));
        $validator->validate(new SaveStepDayDataInput(200000));

        $this->expectNotToPerformAssertions();
    }

    public function testItRefusesANegativeCount(): void
    {
        $this->assertRefusesCount(-1);
    }

    /** The case the upper bound exists for: a pasted sensor reading nobody walks in a day. */
    public function testItRefusesACountNobodyWalks(): void
    {
        $this->assertRefusesCount(200001);
    }

    private function assertRefusesCount(int $countInSteps): void
    {
        try {
            (new SaveStepDayValidator($this->validator))->validate(new SaveStepDayDataInput($countInSteps));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(SaveStepDayValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('count_invalid', $exception->violations['countInSteps']);
        }
    }
}
