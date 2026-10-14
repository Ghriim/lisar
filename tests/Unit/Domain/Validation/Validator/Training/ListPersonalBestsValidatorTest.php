<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\Validation\Validator\Training\ListPersonalBestsValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class ListPersonalBestsValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new ListPersonalBestsValidator())->validate($this->anyCurrentUser());
    }
}
