<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Validator\Todo\ReopenTaskValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class ReopenTaskValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsTheCallersOwnTaskThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new ReopenTaskValidator())->validate($this->currentUser(1), $this->taskOf($this->currentUser(1)));
    }

    public function testItRefusesSomeoneElsesTask(): void
    {
        try {
            (new ReopenTaskValidator())->validate($this->currentUser(1), $this->taskOf($this->currentUser(2)));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ReopenTaskValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }
}
