<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Tracking\Hydration;

use App\Domain\Validation\Validator\Tracking\Hydration\ListHydrationPresetsValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class ListHydrationPresetsValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new ListHydrationPresetsValidator())->validate($this->anyCurrentUser());
    }
}
