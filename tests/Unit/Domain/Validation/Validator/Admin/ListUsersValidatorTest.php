<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Admin;

use App\Domain\DTO\Input\Admin\ListUsersDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\Admin\ListUsersValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ListUsersValidatorTest extends TestCase
{
    private ListUsersValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ListUsersValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsAnEmptyFilter(): void
    {
        $this->validator->validate(new ListUsersDataInput());

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsANonPositivePage(): void
    {
        $this->assertViolatesOn(new ListUsersDataInput(page: 0), 'page', 'page_invalid');
    }

    public function testItRejectsAPageSizeAboveTheCap(): void
    {
        $input = new ListUsersDataInput(perPage: ListUsersDataInput::MAX_PER_PAGE + 1);

        $this->assertViolatesOn($input, 'perPage', 'per_page_invalid');
    }

    public function testItRejectsASearchTermLongerThanAnEmail(): void
    {
        $this->assertViolatesOn(new ListUsersDataInput(search: str_repeat('a', 181)), 'search', 'search_too_long');
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate(new ListUsersDataInput(search: str_repeat('a', 181), page: -1, perPage: 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ListUsersValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey('search', $exception->violations);
            self::assertArrayHasKey('page', $exception->violations);
            self::assertArrayHasKey('perPage', $exception->violations);
        }
    }

    public function testItComputesTheOffsetFromThePage(): void
    {
        self::assertSame(0, (new ListUsersDataInput(page: 1, perPage: 25))->getOffset());
        self::assertSame(25, (new ListUsersDataInput(page: 2, perPage: 25))->getOffset());
        self::assertSame(20, (new ListUsersDataInput(page: 3, perPage: 10))->getOffset());
    }

    private function assertViolatesOn(ListUsersDataInput $input, string $propertyPath, string $errorCode): void
    {
        try {
            $this->validator->validate($input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains($errorCode, $exception->violations[$propertyPath]);
        }
    }
}
