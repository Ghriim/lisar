<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Domain\DTO\Input\Hydration\CreateHydrationEntryDataInput;
use App\Domain\DTO\Input\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\DTO\Output\Hydration\HydrationDayDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\Infrastructure\Security\SecurityUser;
use App\UseCase\Hydration\CreateHydrationEntryUseCase;
use App\UseCase\Hydration\DeleteHydrationEntryUseCase;
use App\UseCase\Hydration\GetHydrationDayUseCase;
use App\UseCase\Hydration\UpdateHydrationEntryUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * The day in progress, and what is logged on it. Every route answers with the whole day: the
 * widget shows a total against a goal, and one round trip refreshes both.
 */
#[OA\Tag(name: 'Hydration')]
final class HydrationController extends AbstractController
{
    #[Route('/api/hydration/today', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The day in progress, its goal, and everything logged on it.',
        content: new OA\JsonContent(ref: new Model(type: HydrationDayDataOutput::class)),
    )]
    public function getToday(#[CurrentUser] SecurityUser $securityUser, GetHydrationDayUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($securityUser->id));
    }

    #[Route('/api/hydration/entries', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateHydrationEntryDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Logged on the day in progress, whatever day the caller thinks it is.',
        content: new OA\JsonContent(ref: new Model(type: HydrationDayDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Volume out of range.')]
    public function createEntry(
        #[CurrentUser] SecurityUser $securityUser,
        #[MapDataInput] CreateHydrationEntryDataInput $input,
        CreateHydrationEntryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $input), Response::HTTP_CREATED);
    }

    #[Route('/api/hydration/entries/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateHydrationEntryDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The day, with the entry corrected.',
        content: new OA\JsonContent(ref: new Model(type: HydrationDayDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such entry on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Volume out of range, or an entry from a past day.')]
    public function updateEntry(
        int $id,
        #[CurrentUser] SecurityUser $securityUser,
        #[MapDataInput] UpdateHydrationEntryDataInput $input,
        UpdateHydrationEntryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $id, $input));
    }

    #[Route('/api/hydration/entries/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The day, without the entry.',
        content: new OA\JsonContent(ref: new Model(type: HydrationDayDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such entry on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'An entry from a past day.')]
    public function deleteEntry(
        int $id,
        #[CurrentUser] SecurityUser $securityUser,
        DeleteHydrationEntryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $id));
    }
}
