<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\DTO\Input\Workout\CreateMuscleGroupDataInput;
use App\Domain\DTO\Input\Workout\UpdateMuscleGroupDataInput;
use App\Domain\DTO\Output\Workout\MuscleGroupDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Admin\ActivateMuscleGroupUseCase;
use App\UseCase\Admin\CreateMuscleGroupUseCase;
use App\UseCase\Admin\DeactivateMuscleGroupUseCase;
use App\UseCase\Admin\DeleteMuscleGroupUseCase;
use App\UseCase\Admin\ListMuscleGroupsForAdminUseCase;
use App\UseCase\Admin\UpdateMuscleGroupUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The muscle groups, maintained by an administrator. Access is gated by the firewall
 * (^/api/admin → ROLE_ADMIN), so no check lives here.
 */
#[OA\Tag(name: 'Admin — workout muscles')]
final class AdminMuscleGroupController extends AbstractController
{
    #[Route('/api/admin/workout/muscle-groups', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Every muscle group by name, active or not.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: MuscleGroupDataOutput::class))),
    )]
    public function listMuscleGroups(ListMuscleGroupsForAdminUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute());
    }

    #[Route('/api/admin/workout/muscle-groups', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateMuscleGroupDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'Created, and active.', content: new OA\JsonContent(ref: new Model(type: MuscleGroupDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used name.')]
    public function createMuscleGroup(
        #[MapDataInput] CreateMuscleGroupDataInput $input,
        CreateMuscleGroupUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input), Response::HTTP_CREATED);
    }

    #[Route('/api/admin/workout/muscle-groups/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateMuscleGroupDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Updated.', content: new OA\JsonContent(ref: new Model(type: MuscleGroupDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such muscle group.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used name.')]
    public function updateMuscleGroup(
        int $id,
        #[MapDataInput] UpdateMuscleGroupDataInput $input,
        UpdateMuscleGroupUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($id, $input));
    }

    #[Route('/api/admin/workout/muscle-groups/{id}/deactivate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'No longer offered to new movements.', content: new OA\JsonContent(ref: new Model(type: MuscleGroupDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such muscle group.')]
    public function deactivateMuscleGroup(int $id, DeactivateMuscleGroupUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/workout/muscle-groups/{id}/activate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Offered to new movements again.', content: new OA\JsonContent(ref: new Model(type: MuscleGroupDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such muscle group.')]
    public function activateMuscleGroup(int $id, ActivateMuscleGroupUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/workout/muscle-groups/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such muscle group.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Muscles still sit in it.')]
    public function deleteMuscleGroup(int $id, DeleteMuscleGroupUseCase $useCase): JsonResponse
    {
        $useCase->execute($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
