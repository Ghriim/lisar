<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Habits;

use App\Domain\DTO\Output\Habits\HabitCatalogItemDataOutput;
use App\Domain\DTO\Output\Habits\HabitDataOutput;
use App\Infrastructure\Controller\AbstractBaseController;
use App\UseCase\Habits\CompleteHabitUseCase;
use App\UseCase\Habits\ListHabitCatalogUseCase;
use App\UseCase\Habits\ListHabitsUseCase;
use App\UseCase\Habits\SubscribeHabitUseCase;
use App\UseCase\Habits\UncompleteHabitUseCase;
use App\UseCase\Habits\UnsubscribeHabitUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The person's habits: the ones they keep, ticking a manual one for today, and the catalogue they
 * subscribe from. No route names a day — keeping lands on the day the server counts as in progress.
 */
#[OA\Tag(name: 'Habits')]
final class HabitController extends AbstractBaseController
{
    #[Route('/api/habits', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The kept habits, each with its last seven days.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: HabitDataOutput::class))),
    )]
    public function listHabits(ListHabitsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($this->getCurrentUser()));
    }

    #[Route('/api/habits/catalog', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The catalogue to subscribe from, each habit flagged as kept or not.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: HabitCatalogItemDataOutput::class))),
    )]
    public function listCatalog(ListHabitCatalogUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($this->getCurrentUser()));
    }

    #[Route('/api/habits/{id}/complete', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The habit, kept for today, with its refreshed week.',
        content: new OA\JsonContent(ref: new Model(type: HabitDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such habit, or not subscribed to it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A tracker habit is not ticked by hand.')]
    public function completeHabit(
        int $id,
        CompleteHabitUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id));
    }

    #[Route('/api/habits/{id}/complete', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The habit, un-kept for today (the undo of a mis-tap), with its refreshed week.',
        content: new OA\JsonContent(ref: new Model(type: HabitDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such habit, or not subscribed to it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A tracker habit is not un-ticked by hand.')]
    public function uncompleteHabit(
        int $id,
        UncompleteHabitUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id));
    }

    #[Route('/api/habits/{id}/subscribe', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The catalogue habit, now kept.',
        content: new OA\JsonContent(ref: new Model(type: HabitCatalogItemDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such habit.')]
    public function subscribe(
        int $id,
        SubscribeHabitUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id));
    }

    #[Route('/api/habits/{id}/subscribe', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The catalogue habit, no longer kept.',
        content: new OA\JsonContent(ref: new Model(type: HabitCatalogItemDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such habit, or not subscribed to it.')]
    public function unsubscribe(
        int $id,
        UnsubscribeHabitUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id));
    }
}
