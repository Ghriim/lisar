<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Todo;

use App\Infrastructure\Controller\AbstractBaseController;
use App\UseCase\Todo\ListTagsUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Tags')]
final class TagController extends AbstractBaseController
{
    #[Route('/api/tags', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The labels this account has already used, alphabetically.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'string')),
    )]
    public function listTags(ListTagsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($this->getCurrentUser()));
    }
}
