<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Tracking\Step;

use App\Domain\Validation\Validator\Tracking\Step\GetStepDayValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class GetStepDayValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new GetStepDayValidator())->validate($this->anyCurrentUser());
    }
}
