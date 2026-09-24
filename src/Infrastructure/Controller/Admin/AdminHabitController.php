<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\DTO\Input\Admin\ListHabitsForAdminDataInput;
use App\Domain\DTO\Input\Habit\CreateHabitDataInput;
use App\Domain\DTO\Input\Habit\UpdateHabitDataInput;
use App\Domain\DTO\Output\Habit\HabitAdminDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Admin\ActivateHabitUseCase;
use App\UseCase\Admin\CreateHabitUseCase;
use App\UseCase\Admin\DeactivateHabitUseCase;
use App\UseCase\Admin\ListHabitsForAdminUseCase;
use App\UseCase\Admin\UpdateHabitUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The habit catalogue, maintained by an administrator. Access is gated by the firewall
 * (^/api/admin → ROLE_ADMIN), so no check lives here.
 *
 * Removing a habit deactivates it rather than deleting it — the days people kept it survive — so
 * there is no DELETE: an activate and a deactivate route stand in for it.
 */
#[OA\Tag(name: 'Admin — habits')]
final class AdminHabitController extends AbstractController
{
    #[Route('/api/admin/habits', methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'isActive', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The catalogue by name; the optional isActive filter narrows it to the offered or the retired.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: HabitAdminDataOutput::class))),
    )]
    public function listHabits(
        #[MapDataInput] ListHabitsForAdminDataInput $input,
        ListHabitsForAdminUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input->isActive));
    }

    #[Route('/api/admin/habits', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateHabitDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'The habit, offered to everyone to subscribe to.',
        content: new OA\JsonContent(ref: new Model(type: HabitAdminDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid definition.')]
    public function createHabit(
        #[MapDataInput] CreateHabitDataInput $input,
        CreateHabitUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input), Response::HTTP_CREATED);
    }

    #[Route('/api/admin/habits/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateHabitDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The habit. Days already kept do not change.',
        content: new OA\JsonContent(ref: new Model(type: HabitAdminDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such habit.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid definition.')]
    public function updateHabit(
        int $id,
        #[MapDataInput] UpdateHabitDataInput $input,
        UpdateHabitUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($id, $input));
    }

    #[Route('/api/admin/habits/{id}/deactivate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The habit, retired from the catalogue with its history kept.',
        content: new OA\JsonContent(ref: new Model(type: HabitAdminDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such habit.')]
    public function deactivateHabit(int $id, DeactivateHabitUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/habits/{id}/activate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The habit, offered again.',
        content: new OA\JsonContent(ref: new Model(type: HabitAdminDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such habit.')]
    public function activateHabit(int $id, ActivateHabitUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }
}
