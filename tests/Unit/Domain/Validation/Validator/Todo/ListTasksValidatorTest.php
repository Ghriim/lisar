<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo;

use App\Domain\DTO\Input\Todo\ListTasksDataInput;
use App\Domain\Validation\Validator\Todo\ListTasksValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ListTasksValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** Every filter is optional: none given is a valid list. */
    public function testItAcceptsNoFilter(): void
    {
        $this->expectNotToPerformAssertions();

        (new ListTasksValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()))->validate($this->anyCurrentUser(), new ListTasksDataInput());
    }
}
