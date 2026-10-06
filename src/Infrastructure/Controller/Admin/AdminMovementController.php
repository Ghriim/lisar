<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\DTO\Input\Admin\ListMovementsForAdminDataInput;
use App\Domain\DTO\Input\Workout\CreateMovementDataInput;
use App\Domain\DTO\Input\Workout\UpdateMovementDataInput;
use App\Domain\DTO\Output\Workout\MovementDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Admin\ActivateMovementUseCase;
use App\UseCase\Admin\CreateMovementUseCase;
use App\UseCase\Admin\DeactivateMovementUseCase;
use App\UseCase\Admin\DeleteMovementUseCase;
use App\UseCase\Admin\ListMovementsForAdminUseCase;
use App\UseCase\Admin\UpdateMovementUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The common movements, maintained by an administrator. What people will create for themselves
 * never shows here. Access is gated by the firewall (^/api/admin → ROLE_ADMIN), so no check lives
 * here.
 */
#[OA\Tag(name: 'Admin — workout movements')]
final class AdminMovementController extends AbstractController
{
    #[Route('/api/admin/workout/movements', methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'isActive', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'movementFamilyId', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'muscleGroupId', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'muscleId', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'equipmentId', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Every common movement by name; the optional filters combine, and a muscle or group matches as primary or secondary.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: MovementDataOutput::class))),
    )]
    public function listMovements(
        #[MapDataInput] ListMovementsForAdminDataInput $input,
        ListMovementsForAdminUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input));
    }

    #[Route('/api/admin/workout/movements', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateMovementDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'Created, and active.', content: new OA\JsonContent(ref: new Model(type: MovementDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid definition: name, family, muscles, equipments or measure.')]
    public function createMovement(
        #[MapDataInput] CreateMovementDataInput $input,
        CreateMovementUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input), Response::HTTP_CREATED);
    }

    #[Route('/api/admin/workout/movements/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateMovementDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Updated.', content: new OA\JsonContent(ref: new Model(type: MovementDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such common movement.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid definition: name, family, muscles, equipments or measure.')]
    public function updateMovement(
        int $id,
        #[MapDataInput] UpdateMovementDataInput $input,
        UpdateMovementUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($id, $input));
    }

    #[Route('/api/admin/workout/movements/{id}/deactivate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'No longer offered.', content: new OA\JsonContent(ref: new Model(type: MovementDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such common movement.')]
    public function deactivateMovement(int $id, DeactivateMovementUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/workout/movements/{id}/activate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Offered again.', content: new OA\JsonContent(ref: new Model(type: MovementDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such common movement.')]
    public function activateMovement(int $id, ActivateMovementUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/workout/movements/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such common movement.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A workout logged it.')]
    public function deleteMovement(int $id, DeleteMovementUseCase $useCase): JsonResponse
    {
        $useCase->execute($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
