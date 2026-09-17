<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\DTO\Input\Admin\CreateReferenceCategoryDataInput;
use App\Domain\DTO\Input\Admin\UpdateReferenceCategoryDataInput;
use App\Domain\DTO\Output\Task\CategoryDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Admin\CreateReferenceCategoryUseCase;
use App\UseCase\Admin\DeleteReferenceCategoryUseCase;
use App\UseCase\Admin\ListReferenceCategoriesUseCase;
use App\UseCase\Admin\UpdateReferenceCategoryUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The common categories. What people create for themselves never appears here.
 */
#[OA\Tag(name: 'Admin — categories')]
final class AdminCategoryController extends AbstractController
{
    #[Route('/api/admin/categories', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The common categories, alphabetically.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: CategoryDataOutput::class))),
    )]
    public function listReferenceCategories(ListReferenceCategoriesUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute());
    }

    #[Route('/api/admin/categories', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateReferenceCategoryDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'The category, visible to everyone.',
        content: new OA\JsonContent(ref: new Model(type: CategoryDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used label.')]
    public function createReferenceCategory(
        #[MapDataInput] CreateReferenceCategoryDataInput $input,
        CreateReferenceCategoryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input), Response::HTTP_CREATED);
    }

    #[Route('/api/admin/categories/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateReferenceCategoryDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The category, renamed for everyone.',
        content: new OA\JsonContent(ref: new Model(type: CategoryDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such common category.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used label.')]
    public function updateReferenceCategory(
        int $id,
        #[MapDataInput] UpdateReferenceCategoryDataInput $input,
        UpdateReferenceCategoryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($id, $input));
    }

    #[Route('/api/admin/categories/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such common category.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Tasks still sit in it.')]
    public function deleteReferenceCategory(int $id, DeleteReferenceCategoryUseCase $useCase): JsonResponse
    {
        $useCase->execute($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
