<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Training\Admin;

use App\Domain\DTO\Input\Training\CreateMovementFamilyDataInput;
use App\Domain\DTO\Input\Training\UpdateMovementFamilyDataInput;
use App\Domain\DTO\Output\Training\MovementFamilyDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Training\Admin\ActivateMovementFamilyUseCase;
use App\UseCase\Training\Admin\CreateMovementFamilyUseCase;
use App\UseCase\Training\Admin\DeactivateMovementFamilyUseCase;
use App\UseCase\Training\Admin\DeleteMovementFamilyUseCase;
use App\UseCase\Training\Admin\ListMovementFamiliesForAdminUseCase;
use App\UseCase\Training\Admin\UpdateMovementFamilyUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The movement families, maintained by an administrator. Access is gated by the firewall
 * (^/api/admin → ROLE_ADMIN), so no check lives here.
 */
#[OA\Tag(name: 'Admin — workout movements')]
final class AdminMovementFamilyController extends AbstractController
{
    #[Route('/api/admin/workout/movement-families', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Every movement family by name, active or not.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: MovementFamilyDataOutput::class))),
    )]
    public function listMovementFamilies(ListMovementFamiliesForAdminUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute());
    }

    #[Route('/api/admin/workout/movement-families', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateMovementFamilyDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'Created, and active.', content: new OA\JsonContent(ref: new Model(type: MovementFamilyDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used name.')]
    public function createMovementFamily(
        #[MapDataInput] CreateMovementFamilyDataInput $input,
        CreateMovementFamilyUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input), Response::HTTP_CREATED);
    }

    #[Route('/api/admin/workout/movement-families/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateMovementFamilyDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Updated.', content: new OA\JsonContent(ref: new Model(type: MovementFamilyDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such movement family.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used name.')]
    public function updateMovementFamily(
        int $id,
        #[MapDataInput] UpdateMovementFamilyDataInput $input,
        UpdateMovementFamilyUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($id, $input));
    }

    #[Route('/api/admin/workout/movement-families/{id}/deactivate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'No longer offered, nor its movements.', content: new OA\JsonContent(ref: new Model(type: MovementFamilyDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such movement family.')]
    public function deactivateMovementFamily(int $id, DeactivateMovementFamilyUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/workout/movement-families/{id}/activate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Offered again.', content: new OA\JsonContent(ref: new Model(type: MovementFamilyDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such movement family.')]
    public function activateMovementFamily(int $id, ActivateMovementFamilyUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/workout/movement-families/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such movement family.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Movements still sit in it.')]
    public function deleteMovementFamily(int $id, DeleteMovementFamilyUseCase $useCase): JsonResponse
    {
        $useCase->execute($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
