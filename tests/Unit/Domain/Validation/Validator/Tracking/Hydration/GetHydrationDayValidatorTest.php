<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Tracking\Hydration;

use App\Domain\Validation\Validator\Tracking\Hydration\GetHydrationDayValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class GetHydrationDayValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new GetHydrationDayValidator())->validate($this->anyCurrentUser());
    }
}
