<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Training\Admin;

use App\Domain\DTO\Input\Training\Admin\ListEquipmentsForAdminDataInput;
use App\Domain\DTO\Input\Training\CreateEquipmentDataInput;
use App\Domain\DTO\Input\Training\UpdateEquipmentDataInput;
use App\Domain\DTO\Output\Training\EquipmentDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Training\Admin\ActivateEquipmentUseCase;
use App\UseCase\Training\Admin\CreateEquipmentUseCase;
use App\UseCase\Training\Admin\DeactivateEquipmentUseCase;
use App\UseCase\Training\Admin\DeleteEquipmentUseCase;
use App\UseCase\Training\Admin\ListEquipmentsForAdminUseCase;
use App\UseCase\Training\Admin\UpdateEquipmentUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The equipments movements are done with, maintained by an administrator. Access is gated by the
 * firewall (^/api/admin → ROLE_ADMIN), so no check lives here.
 */
#[OA\Tag(name: 'Admin — workout equipments')]
final class AdminEquipmentController extends AbstractController
{
    #[Route('/api/admin/workout/equipments', methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'isActive', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'hasWeight', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'hasDistance', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Every equipment by name; the optional isActive, hasWeight and hasDistance filters combine.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: EquipmentDataOutput::class))),
    )]
    public function listEquipments(
        #[MapDataInput] ListEquipmentsForAdminDataInput $input,
        ListEquipmentsForAdminUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input));
    }

    #[Route('/api/admin/workout/equipments', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateEquipmentDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'Created, and active.', content: new OA\JsonContent(ref: new Model(type: EquipmentDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used name.')]
    public function createEquipment(
        #[MapDataInput] CreateEquipmentDataInput $input,
        CreateEquipmentUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input), Response::HTTP_CREATED);
    }

    #[Route('/api/admin/workout/equipments/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateEquipmentDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Updated.', content: new OA\JsonContent(ref: new Model(type: EquipmentDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such equipment.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used name.')]
    public function updateEquipment(
        int $id,
        #[MapDataInput] UpdateEquipmentDataInput $input,
        UpdateEquipmentUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($id, $input));
    }

    #[Route('/api/admin/workout/equipments/{id}/deactivate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'No longer offered to new movements.', content: new OA\JsonContent(ref: new Model(type: EquipmentDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such equipment.')]
    public function deactivateEquipment(int $id, DeactivateEquipmentUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/workout/equipments/{id}/activate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Offered to new movements again.', content: new OA\JsonContent(ref: new Model(type: EquipmentDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such equipment.')]
    public function activateEquipment(int $id, ActivateEquipmentUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/workout/equipments/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such equipment.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A movement is done with it.')]
    public function deleteEquipment(int $id, DeleteEquipmentUseCase $useCase): JsonResponse
    {
        $useCase->execute($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
