<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Admin;

use App\Domain\DTO\Input\Admin\CreateUserCommentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\Admin\CreateUserCommentValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateUserCommentValidatorTest extends TestCase
{
    private CreateUserCommentValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateUserCommentValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsANote(): void
    {
        $this->validator->validate(new CreateUserCommentDataInput('Called support back.'));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankNote(): void
    {
        $this->assertViolatesOn(new CreateUserCommentDataInput(''), 'body_required');
    }

    public function testItRejectsANoteLongerThanTheColumnIsMeantToHold(): void
    {
        $this->assertViolatesOn(new CreateUserCommentDataInput(str_repeat('a', 2001)), 'body_too_long');
    }

    private function assertViolatesOn(CreateUserCommentDataInput $input, string $errorCode): void
    {
        try {
            $this->validator->validate($input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateUserCommentValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains($errorCode, $exception->violations['body']);
        }
    }
}
