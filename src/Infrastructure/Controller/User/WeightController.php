<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Domain\DTO\Input\Weight\SaveWeightDataInput;
use App\Domain\DTO\Output\Weight\WeightDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\Infrastructure\Security\SecurityUser;
use App\UseCase\Weight\GetLatestWeightUseCase;
use App\UseCase\Weight\SaveWeightUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Two routes, because there are two things to do: see the last weight, and record today's.
 *
 * Recording is a `PUT` on the day in progress — there is one weight per day, so writing it twice
 * is writing the same thing twice, which is what `PUT` means. Nothing here takes a day: the
 * server decides which one is in progress.
 */
#[OA\Tag(name: 'Weight')]
final class WeightController extends AbstractController
{
    #[Route('/api/weight/latest', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The last known weight with the day it belongs to. Every field is null when none was ever recorded.',
        content: new OA\JsonContent(ref: new Model(type: WeightDataOutput::class)),
    )]
    public function getLatest(#[CurrentUser] SecurityUser $securityUser, GetLatestWeightUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($securityUser->id));
    }

    #[Route('/api/weight/today', methods: Request::METHOD_PUT)]
    #[OA\RequestBody(required: true, content: new Model(type: SaveWeightDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: "Today's weight, recorded or corrected, whatever day the caller thinks it is.",
        content: new OA\JsonContent(ref: new Model(type: WeightDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Weight out of range.')]
    public function saveToday(
        #[CurrentUser] SecurityUser $securityUser,
        #[MapDataInput] SaveWeightDataInput $input,
        SaveWeightUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $input));
    }
}
