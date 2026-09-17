<?php

declare(strict_types=1);

namespace App\Infrastructure\HttpKernel\EventListener;

use App\Domain\Exception\AccountDeactivatedException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\ValidationException;
use App\Infrastructure\Exception\DataInputMappingException;
use App\Infrastructure\Exception\DataModelNotFoundException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * The only place in the app that turns an exception into an HTTP response. A new error shape
 * means a new branch here, never a try/catch in a controller.
 */
#[AsEventListener]
final readonly class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = match (true) {
            $exception instanceof ValidationException => new JsonResponse(
                ['violations' => $exception->violations],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ),
            $exception instanceof DataInputMappingException => new JsonResponse(
                ['violations' => $exception->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ),
            $exception instanceof DataModelNotFoundException => new JsonResponse(
                ['message' => $exception->getMessage()],
                Response::HTTP_NOT_FOUND,
            ),
            $exception instanceof InvalidCredentialsException => new JsonResponse(
                ['message' => $exception->getMessage()],
                Response::HTTP_UNAUTHORIZED,
            ),
            $exception instanceof AccountDeactivatedException => new JsonResponse(
                ['message' => $exception->getMessage()],
                Response::HTTP_FORBIDDEN,
            ),
            default => null,
        };

        if (null === $response) {
            return;
        }

        $event->setResponse($response);
    }
}
