<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo;

use App\Domain\Validation\Validator\Todo\ListTagsValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class ListTagsValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new ListTagsValidator())->validate($this->anyCurrentUser());
    }
}
