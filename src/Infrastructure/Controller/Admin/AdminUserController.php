<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\DTO\Input\Admin\ListUsersDataInput;
use App\Domain\DTO\Output\User\UserDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\Infrastructure\Security\SecurityUser;
use App\UseCase\Admin\ActivateUserUseCase;
use App\UseCase\Admin\DeactivateUserUseCase;
use App\UseCase\Admin\ListUsersUseCase;
use App\UseCase\User\GetUserUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * The back-office account surface. It serves account metadata and nothing else: a user's own
 * content is never exposed here.
 */
#[OA\Tag(name: 'Admin — users')]
final class AdminUserController extends AbstractController
{
    #[Route('/api/admin/users', methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'search', in: 'query', description: 'Matched against the username and the e-mail.', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'isActive', in: 'query', schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'perPage', in: 'query', schema: new OA\Schema(type: 'integer', default: ListUsersDataInput::DEFAULT_PER_PAGE))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'A page of accounts, newest first.',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: UserDataOutput::class))),
            new OA\Property(property: 'total', type: 'integer'),
            new OA\Property(property: 'page', type: 'integer'),
            new OA\Property(property: 'perPage', type: 'integer'),
        ]),
    )]
    #[OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Not an administrator.')]
    public function listUsers(#[MapDataInput] ListUsersDataInput $input, ListUsersUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($input));
    }

    #[Route('/api/admin/users/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, content: new OA\JsonContent(ref: new Model(type: UserDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such account.')]
    // Not getUser(): AbstractController already has that name, with another meaning.
    public function getUserAccount(int $id, GetUserUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }

    #[Route('/api/admin/users/{id}/deactivate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The account is deactivated and its live sessions are dropped.',
        content: new OA\JsonContent(ref: new Model(type: UserDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'An administrator cannot deactivate their own account.')]
    public function deactivateUser(
        int $id,
        #[CurrentUser] SecurityUser $securityUser,
        DeactivateUserUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($id, $securityUser->id));
    }

    #[Route('/api/admin/users/{id}/activate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, content: new OA\JsonContent(ref: new Model(type: UserDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such account.')]
    public function activateUser(int $id, ActivateUserUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id));
    }
}
