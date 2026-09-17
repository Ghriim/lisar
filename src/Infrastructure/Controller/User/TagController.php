<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Infrastructure\Security\SecurityUser;
use App\UseCase\Task\ListTagsUseCase;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag(name: 'Tags')]
final class TagController extends AbstractController
{
    #[Route('/api/tags', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The labels this account has already used, alphabetically.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'string')),
    )]
    public function listTags(#[CurrentUser] SecurityUser $securityUser, ListTagsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($securityUser->id));
    }
}
