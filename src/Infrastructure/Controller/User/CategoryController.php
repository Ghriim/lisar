<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Domain\DTO\Input\Task\CreateCategoryDataInput;
use App\Domain\DTO\Input\Task\UpdateCategoryDataInput;
use App\Domain\DTO\Output\Task\CategoryDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\Infrastructure\Security\SecurityUser;
use App\UseCase\Task\CreateCategoryUseCase;
use App\UseCase\Task\DeleteCategoryUseCase;
use App\UseCase\Task\ListCategoriesUseCase;
use App\UseCase\Task\UpdateCategoryUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag(name: 'Categories')]
final class CategoryController extends AbstractController
{
    #[Route('/api/categories', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The reference categories plus the ones this account created.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: CategoryDataOutput::class))),
    )]
    public function listCategories(#[CurrentUser] SecurityUser $securityUser, ListCategoriesUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($securityUser->id));
    }

    #[Route('/api/categories', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateCategoryDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'A category of this account\'s own.',
        content: new OA\JsonContent(ref: new Model(type: CategoryDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Blank or already-used label.')]
    public function createCategory(
        #[CurrentUser] SecurityUser $securityUser,
        #[MapDataInput] CreateCategoryDataInput $input,
        CreateCategoryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $input), Response::HTTP_CREATED);
    }

    #[Route('/api/categories/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateCategoryDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The category, renamed.',
        content: new OA\JsonContent(ref: new Model(type: CategoryDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such category for this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A reference category, or a label already in use.')]
    public function updateCategory(
        int $id,
        #[CurrentUser] SecurityUser $securityUser,
        #[MapDataInput] UpdateCategoryDataInput $input,
        UpdateCategoryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $id, $input));
    }

    #[Route('/api/categories/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such category for this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A reference category, or still in use.')]
    public function deleteCategory(
        int $id,
        #[CurrentUser] SecurityUser $securityUser,
        DeleteCategoryUseCase $useCase,
    ): JsonResponse {
        $useCase->execute($securityUser->id, $id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
