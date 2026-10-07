<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Training\Admin;

use App\Domain\DTO\Input\Training\Admin\ListSetTypesForAdminDataInput;
use App\Domain\DTO\Input\Training\CreateSetTypeDataInput;
use App\Domain\DTO\Input\Training\UpdateSetTypeDataInput;
use App\Domain\DTO\Output\Training\SetTypeDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Training\Admin\ActivateSetTypeUseCase;
use App\UseCase\Training\Admin\CreateSetTypeUseCase;
use App\UseCase\Training\Admin\DeactivateSetTypeUseCase;
use App\UseCase\Training\Admin\DeleteSetTypeUseCase;
use App\UseCase\Training\Admin\ListSetTypesForAdminUseCase;
use App\UseCase\Training\Admin\UpdateSetTypeUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The kinds of set a workout can log — warm-up, dropset… — maintained by an administrator. Access
 * is gated by the firewall (^/api/admin → ROLE_ADMIN), so no check lives here.
 */
#[OA\Tag(name: 'Admin — workout set types')]
final class AdminSetTypeController extends AbstractController
{
    #[Route('/api/admin/workout/set-types', methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'isActive', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Every set type by name; the optional isActive filter narrows it.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: SetTypeDataOutput::class))),
    )]
    public function listSetTypes(
        #[MapDataInput] ListSetTypesForAdminDataInput $input,
        ListSetTypesForAdminUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input));
    }

    #[Route('/api/admin/workout/set-types', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateSetTypeDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'Created, and active.', content: new OA\JsonContent(ref: new Model(type: SetTypeDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used name, or unknown colour.')]
    public function createSetType(
        #[MapDataInput] CreateSetTypeDataInput $input,
        CreateSetTypeUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input), Response::HTTP_CREATED);
    }

    #[Route('/api/admin/workout/set-types/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateSetTypeDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Updated.', content: new OA\JsonContent(ref: new Model(type: SetTypeDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such set type.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used name, or unknown colour.')]
    public function updateSetType(
        int $id,
        #[MapDataInput] UpdateSetTypeDataInput $input,
        UpdateSetTypeUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($id, $input));
    }

    #[Route('/api/admin/workout/set-types/{id}/deactivate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'No longer offered to new sets.', content: new OA\JsonContent(ref: new Model(type: SetTypeDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such set type.')]
    public function deactivateSetType(int $id, DeactivateSetTypeUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/workout/set-types/{id}/activate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Offered to new sets again.', content: new OA\JsonContent(ref: new Model(type: SetTypeDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such set type.')]
    public function activateSetType(int $id, ActivateSetTypeUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/workout/set-types/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such set type.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A logged set carries it.')]
    public function deleteSetType(int $id, DeleteSetTypeUseCase $useCase): JsonResponse
    {
        $useCase->execute($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
