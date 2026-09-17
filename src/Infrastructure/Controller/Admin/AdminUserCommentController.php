<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\DTO\Input\Admin\CreateUserCommentDataInput;
use App\Domain\DTO\Output\Admin\UserCommentDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\Infrastructure\Security\SecurityUser;
use App\UseCase\Admin\CreateUserCommentUseCase;
use App\UseCase\Admin\ListUserCommentsUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Internal notes on an account. Back-office only: they are never served to the account itself.
 */
#[OA\Tag(name: 'Admin — user comments')]
final class AdminUserCommentController extends AbstractController
{
    #[Route('/api/admin/users/{id}/comments', requirements: ['id' => '\d+'], methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The note thread of that account, newest first.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: UserCommentDataOutput::class))),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such account.')]
    public function listUserComments(int $id, ListUserCommentsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/users/{id}/comments', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: CreateUserCommentDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'The note was added, attributed to the administrator who is signed in.',
        content: new OA\JsonContent(ref: new Model(type: UserCommentDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid payload.')]
    public function createUserComment(
        int $id,
        #[CurrentUser] SecurityUser $securityUser,
        #[MapDataInput] CreateUserCommentDataInput $input,
        CreateUserCommentUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($id, $securityUser->id, $input), Response::HTTP_CREATED);
    }
}
