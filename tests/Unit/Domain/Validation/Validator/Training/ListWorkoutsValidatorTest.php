<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\ListWorkoutsDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\Training\ListWorkoutsValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ListWorkoutsValidatorTest extends TestCase
{
    private ListWorkoutsValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ListWorkoutsValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsTheDefaults(): void
    {
        $this->validator->validate(new ListWorkoutsDataInput());

        $this->expectNotToPerformAssertions();
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate(new ListWorkoutsDataInput(page: 0, perPage: ListWorkoutsDataInput::MAX_PER_PAGE + 1));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ListWorkoutsValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['page_invalid'], $exception->violations['page']);
            self::assertSame(['per_page_invalid'], $exception->violations['perPage']);
        }
    }
}
