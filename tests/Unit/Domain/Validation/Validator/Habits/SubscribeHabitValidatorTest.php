<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Habits;

use App\Domain\Validation\Validator\Habits\SubscribeHabitValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class SubscribeHabitValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new SubscribeHabitValidator())->validate($this->anyCurrentUser());
    }
}
