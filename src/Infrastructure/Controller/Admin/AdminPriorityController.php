<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\DTO\Input\Admin\CreatePriorityDataInput;
use App\Domain\DTO\Input\Admin\UpdatePriorityDataInput;
use App\Domain\DTO\Output\Task\PriorityDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Admin\CreatePriorityUseCase;
use App\UseCase\Admin\DeletePriorityUseCase;
use App\UseCase\Admin\UpdatePriorityUseCase;
use App\UseCase\Task\ListPrioritiesUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The priority reference set. Everyone picks from it; only this surface writes it.
 */
#[OA\Tag(name: 'Admin — priorities')]
final class AdminPriorityController extends AbstractController
{
    #[Route('/api/admin/priorities', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Every priority, lightest weight first.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: PriorityDataOutput::class))),
    )]
    public function listPriorities(ListPrioritiesUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute());
    }

    #[Route('/api/admin/priorities', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreatePriorityDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'The priority. The very first one created is the default, asked for or not.',
        content: new OA\JsonContent(ref: new Model(type: PriorityDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid payload, or a label already used.')]
    public function createPriority(
        #[MapDataInput] CreatePriorityDataInput $input,
        CreatePriorityUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input), Response::HTTP_CREATED);
    }

    #[Route('/api/admin/priorities/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdatePriorityDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The priority. Setting isDefault takes the default from whichever had it.',
        content: new OA\JsonContent(ref: new Model(type: PriorityDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such priority.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid payload, a label already used, or the default being unset.')]
    public function updatePriority(
        int $id,
        #[MapDataInput] UpdatePriorityDataInput $input,
        UpdatePriorityUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($id, $input));
    }

    #[Route('/api/admin/priorities/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such priority.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Still in use, or the default one.')]
    public function deletePriority(int $id, DeletePriorityUseCase $useCase): JsonResponse
    {
        $useCase->execute($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
