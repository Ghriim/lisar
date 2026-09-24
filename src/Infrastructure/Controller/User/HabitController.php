<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Domain\DTO\Output\Habit\HabitCatalogItemDataOutput;
use App\Domain\DTO\Output\Habit\HabitDataOutput;
use App\Infrastructure\Security\SecurityUser;
use App\UseCase\Habit\CompleteHabitUseCase;
use App\UseCase\Habit\ListHabitCatalogUseCase;
use App\UseCase\Habit\ListHabitsUseCase;
use App\UseCase\Habit\SubscribeHabitUseCase;
use App\UseCase\Habit\UncompleteHabitUseCase;
use App\UseCase\Habit\UnsubscribeHabitUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * The person's habits: the ones they keep, ticking a manual one for today, and the catalogue they
 * subscribe from. No route names a day — keeping lands on the day the server counts as in progress.
 */
#[OA\Tag(name: 'Habits')]
final class HabitController extends AbstractController
{
    #[Route('/api/habits', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The kept habits, each with its last seven days.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: HabitDataOutput::class))),
    )]
    public function listHabits(#[CurrentUser] SecurityUser $securityUser, ListHabitsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($securityUser->id));
    }

    #[Route('/api/habits/catalog', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The catalogue to subscribe from, each habit flagged as kept or not.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: HabitCatalogItemDataOutput::class))),
    )]
    public function listCatalog(#[CurrentUser] SecurityUser $securityUser, ListHabitCatalogUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($securityUser->id));
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
        #[CurrentUser] SecurityUser $securityUser,
        CompleteHabitUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $id));
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
        #[CurrentUser] SecurityUser $securityUser,
        UncompleteHabitUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $id));
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
        #[CurrentUser] SecurityUser $securityUser,
        SubscribeHabitUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $id));
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
        #[CurrentUser] SecurityUser $securityUser,
        UnsubscribeHabitUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $id));
    }
}
