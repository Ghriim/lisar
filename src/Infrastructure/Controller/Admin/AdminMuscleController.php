<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\DTO\Input\Admin\ListMusclesForAdminDataInput;
use App\Domain\DTO\Input\Workout\CreateMuscleDataInput;
use App\Domain\DTO\Input\Workout\UpdateMuscleDataInput;
use App\Domain\DTO\Output\Workout\MuscleDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Admin\ActivateMuscleUseCase;
use App\UseCase\Admin\CreateMuscleUseCase;
use App\UseCase\Admin\DeactivateMuscleUseCase;
use App\UseCase\Admin\DeleteMuscleUseCase;
use App\UseCase\Admin\ListMusclesForAdminUseCase;
use App\UseCase\Admin\UpdateMuscleUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The muscles movements target, each in one group, maintained by an administrator. Access is
 * gated by the firewall (^/api/admin → ROLE_ADMIN), so no check lives here.
 */
#[OA\Tag(name: 'Admin — workout muscles')]
final class AdminMuscleController extends AbstractController
{
    #[Route('/api/admin/workout/muscles', methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'isActive', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'muscleGroupId', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: "Every muscle by group then name; the optional isActive (the muscle's own flag) and muscleGroupId filters combine.",
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: MuscleDataOutput::class))),
    )]
    public function listMuscles(
        #[MapDataInput] ListMusclesForAdminDataInput $input,
        ListMusclesForAdminUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input));
    }

    #[Route('/api/admin/workout/muscles', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateMuscleDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'Created, and active.', content: new OA\JsonContent(ref: new Model(type: MuscleDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used name, or a group that is missing or inactive.')]
    public function createMuscle(
        #[MapDataInput] CreateMuscleDataInput $input,
        CreateMuscleUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input), Response::HTTP_CREATED);
    }

    #[Route('/api/admin/workout/muscles/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateMuscleDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Updated.', content: new OA\JsonContent(ref: new Model(type: MuscleDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such muscle.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used name, or a group that is missing or inactive.')]
    public function updateMuscle(
        int $id,
        #[MapDataInput] UpdateMuscleDataInput $input,
        UpdateMuscleUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($id, $input));
    }

    #[Route('/api/admin/workout/muscles/{id}/deactivate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'No longer offered to new movements.', content: new OA\JsonContent(ref: new Model(type: MuscleDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such muscle.')]
    public function deactivateMuscle(int $id, DeactivateMuscleUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/workout/muscles/{id}/activate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Offered to new movements again.', content: new OA\JsonContent(ref: new Model(type: MuscleDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such muscle.')]
    public function activateMuscle(int $id, ActivateMuscleUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/workout/muscles/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such muscle.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A movement targets it.')]
    public function deleteMuscle(int $id, DeleteMuscleUseCase $useCase): JsonResponse
    {
        $useCase->execute($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
