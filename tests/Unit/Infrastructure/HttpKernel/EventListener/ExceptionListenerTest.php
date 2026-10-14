<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\HttpKernel\EventListener;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Infrastructure\HttpKernel\EventListener\ExceptionListener;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

#[AllowMockObjectsWithoutExpectations]
final class ExceptionListenerTest extends TestCase
{
    public function testAPayloadMistakeIsA422(): void
    {
        $response = $this->respond(new ValidationException('start_workout_invalid', ['name' => ['name_too_long']]));

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response?->getStatusCode());
    }

    /** Not the caller's, not an administrator, switched off: not a 422, a 401. */
    public function testACallerNotAllowedIsA401(): void
    {
        $violations = ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]];

        $response = $this->respond(new ValidationException('get_workout_invalid', $violations));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response?->getStatusCode());
        self::assertSame(['violations' => $violations], json_decode((string) $response->getContent(), true));
    }

    private function respond(Throwable $exception): ?Response
    {
        $event = new ExceptionEvent($this->createMock(HttpKernelInterface::class), new Request(), HttpKernelInterface::MAIN_REQUEST, $exception);
        (new ExceptionListener())($event);

        return $event->getResponse();
    }
}
