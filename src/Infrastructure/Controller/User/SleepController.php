<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Domain\DTO\Input\Sleep\SaveSleepNightDataInput;
use App\Domain\DTO\Output\Sleep\SleepNightDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\Infrastructure\Security\SecurityUser;
use App\UseCase\Sleep\GetSleepNightUseCase;
use App\UseCase\Sleep\SaveSleepNightUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * One resource, two verbs: the night of the day in progress, read and written.
 *
 * Writing is a `PUT` — there is one night per day, so noting it twice writes the same thing
 * twice. Nothing here takes a day, and nothing takes a date: the server decides which day is in
 * progress, and the two times say the rest.
 */
#[OA\Tag(name: 'Sleep')]
final class SleepController extends AbstractController
{
    #[Route('/api/sleep/today', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The night of the day in progress. Everything but the day is null when none was noted.',
        content: new OA\JsonContent(ref: new Model(type: SleepNightDataOutput::class)),
    )]
    public function getToday(#[CurrentUser] SecurityUser $securityUser, GetSleepNightUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($securityUser->id));
    }

    #[Route('/api/sleep/today', methods: Request::METHOD_PUT)]
    #[OA\RequestBody(required: true, content: new Model(type: SaveSleepNightDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The night, noted or corrected, whatever day the caller thinks it is.',
        content: new OA\JsonContent(ref: new Model(type: SleepNightDataOutput::class)),
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'A malformed time, a mood outside 1-5, or a night of an implausible length.',
    )]
    public function saveToday(
        #[CurrentUser] SecurityUser $securityUser,
        #[MapDataInput] SaveSleepNightDataInput $input,
        SaveSleepNightUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $input));
    }
}
