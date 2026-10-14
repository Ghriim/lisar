<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\User;

use App\Domain\Validation\Validator\User\GetUserValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class GetUserValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new GetUserValidator())->validate($this->anyCurrentUser());
    }
}
