<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Habits;

use App\Domain\Validation\Validator\Habits\UnsubscribeHabitValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class UnsubscribeHabitValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new UnsubscribeHabitValidator())->validate($this->anyCurrentUser());
    }
}
