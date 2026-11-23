<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\CreateRoutineDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\Training\CreateRoutineValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateRoutineValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private CreateRoutineValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateRoutineValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAName(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validate($this->anyCurrentUser(), new CreateRoutineDataInput('Push'));
    }

    public function testItRequiresAName(): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), new CreateRoutineDataInput(''));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateRoutineValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['name' => ['name_required']], $exception->violations);
        }
    }

    /** A hundred characters at most: the workout it starts adds the day to it, within 128. */
    public function testItRejectsANameTooLong(): void
    {
        $this->validator->validate($this->anyCurrentUser(), new CreateRoutineDataInput(str_repeat('a', 100)));

        try {
            $this->validator->validate($this->anyCurrentUser(), new CreateRoutineDataInput(str_repeat('a', 101)));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateRoutineValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['name' => ['name_too_long']], $exception->violations);
        }
    }
}
