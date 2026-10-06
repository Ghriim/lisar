<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Workout;

use App\Domain\DTO\Input\Workout\UpdateWorkoutExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\Workout\UpdateWorkoutExerciseValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateWorkoutExerciseValidatorTest extends TestCase
{
    private UpdateWorkoutExerciseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateWorkoutExerciseValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsANote(): void
    {
        $this->validator->validate(new UpdateWorkoutExerciseDataInput('Coudes serrés'));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsANoteTooLong(): void
    {
        try {
            $this->validator->validate(new UpdateWorkoutExerciseDataInput(str_repeat('a', 5001)));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateWorkoutExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['note_too_long'], $exception->violations['note']);
        }
    }
}
