<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Workout;

use App\Domain\DTO\Input\Workout\UpdateWorkoutDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\Workout\UpdateWorkoutValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateWorkoutValidatorTest extends TestCase
{
    private UpdateWorkoutValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateWorkoutValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFullUpdate(): void
    {
        $this->validator->validate(new UpdateWorkoutDataInput('Push', 'Bonne séance', 4));

        $this->expectNotToPerformAssertions();
    }

    public function testItAcceptsAnEmptyUpdate(): void
    {
        $this->validator->validate(new UpdateWorkoutDataInput());

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAFeelingOutsideOneToFive(): void
    {
        foreach ([0, 6] as $feeling) {
            try {
                $this->validator->validate(new UpdateWorkoutDataInput(feeling: $feeling));
                self::fail('Expected ValidationException');
            } catch (ValidationException $exception) {
                self::assertSame(UpdateWorkoutValidator::ERROR_CODE, $exception->errorCode);
                self::assertSame(['feeling_invalid'], $exception->violations['feeling']);
            }
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate(new UpdateWorkoutDataInput(str_repeat('a', 129), str_repeat('a', 5001), 9));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['name_too_long'], $exception->violations['name']);
            self::assertSame(['note_too_long'], $exception->violations['note']);
            self::assertSame(['feeling_invalid'], $exception->violations['feeling']);
        }
    }
}
