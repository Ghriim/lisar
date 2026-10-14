<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\Validation\Validator\Training\GetCurrentWorkoutValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class GetCurrentWorkoutValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new GetCurrentWorkoutValidator())->validate($this->anyCurrentUser());
    }
}
