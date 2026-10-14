<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Domain\DTO\Input\User\RegisterUserDataInput;
use App\Domain\DTO\Output\User\UserDataOutput;
use App\Infrastructure\Controller\AbstractBaseController;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\User\GetUserUseCase;
use App\UseCase\User\RegisterUserUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Users')]
final class UserController extends AbstractBaseController
{
    #[Route('/api/users/register', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: RegisterUserDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'The account was created.',
        content: new OA\JsonContent(ref: new Model(type: UserDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid sign-up payload.')]
    public function registerUser(#[MapDataInput] RegisterUserDataInput $input, RegisterUserUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($input), Response::HTTP_CREATED);
    }

    #[Route('/api/users/me', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The account the access token belongs to.',
        content: new OA\JsonContent(ref: new Model(type: UserDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Missing or expired access token.')]
    public function getOwnAccount(GetUserUseCase $useCase): JsonResponse
    {
        $currentUser = $this->getCurrentUser();

        // A signed-in account was loaded from the database: it has an id.
        return new JsonResponse($useCase->execute($currentUser, (int) $currentUser->id));
    }
}
