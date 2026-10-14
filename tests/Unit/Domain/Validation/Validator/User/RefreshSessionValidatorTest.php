<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\User;

use App\Domain\Validation\Validator\User\RefreshSessionValidator;
use PHPUnit\Framework\TestCase;

final class RefreshSessionValidatorTest extends TestCase
{
    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new RefreshSessionValidator())->validate();
    }
}
