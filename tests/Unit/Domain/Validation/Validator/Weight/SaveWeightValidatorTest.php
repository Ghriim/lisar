<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Weight;

use App\Domain\DTO\Input\Weight\SaveWeightDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\Weight\SaveWeightValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** The one shape the weight domain accepts, and the typos it refuses. */
final class SaveWeightValidatorTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testItAcceptsAPlausibleWeight(): void
    {
        (new SaveWeightValidator($this->validator))->validate(new SaveWeightDataInput(72.4));

        $this->expectNotToPerformAssertions();
    }

    /** The bounds themselves are allowed: they are the filter's edge, not outside it. */
    public function testItAcceptsTheBounds(): void
    {
        $validator = new SaveWeightValidator($this->validator);

        $validator->validate(new SaveWeightDataInput(20.0));
        $validator->validate(new SaveWeightDataInput(400.0));

        $this->expectNotToPerformAssertions();
    }

    public function testItRefusesAWeightNobodyHas(): void
    {
        $this->assertRefusesWeight(0.0);
    }

    /** The case the bound exists for: a missed decimal point, 72.4 typed as 724. */
    public function testItRefusesAMissedDecimalPoint(): void
    {
        $this->assertRefusesWeight(724.0);
    }

    private function assertRefusesWeight(float $weightInKilograms): void
    {
        try {
            (new SaveWeightValidator($this->validator))->validate(new SaveWeightDataInput($weightInKilograms));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(SaveWeightValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('weight_invalid', $exception->violations['weightInKilograms']);
        }
    }
}
