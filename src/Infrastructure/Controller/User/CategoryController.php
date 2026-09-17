<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Domain\DTO\Output\Task\CategoryDataOutput;
use App\Infrastructure\Security\SecurityUser;
use App\UseCase\Task\ListCategoriesUseCase;
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
}
