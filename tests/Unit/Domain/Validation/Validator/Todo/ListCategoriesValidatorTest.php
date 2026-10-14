<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo;

use App\Domain\Validation\Validator\Todo\ListCategoriesValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class ListCategoriesValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new ListCategoriesValidator())->validate($this->anyCurrentUser());
    }
}
