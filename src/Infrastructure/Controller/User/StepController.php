<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Domain\DTO\Input\Step\SaveStepDayDataInput;
use App\Domain\DTO\Output\Step\StepDayDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\Infrastructure\Security\SecurityUser;
use App\UseCase\Step\GetStepDayUseCase;
use App\UseCase\Step\SaveStepDayUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Two routes on the day in progress: read today's count, and record it.
 *
 * Recording is a `PUT` — there is one count per day, so writing it twice writes the same thing
 * twice, which is what `PUT` means. Nothing here takes a day: the server decides which one is in
 * progress.
 */
#[OA\Tag(name: 'Steps')]
final class StepController extends AbstractController
{
    #[Route('/api/steps/today', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: "Today's step count and goal. The count is null when nothing has been recorded yet.",
        content: new OA\JsonContent(ref: new Model(type: StepDayDataOutput::class)),
    )]
    public function getToday(#[CurrentUser] SecurityUser $securityUser, GetStepDayUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($securityUser->id));
    }

    #[Route('/api/steps/today', methods: Request::METHOD_PUT)]
    #[OA\RequestBody(required: true, content: new Model(type: SaveStepDayDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: "Today's count, recorded or corrected, whatever day the caller thinks it is.",
        content: new OA\JsonContent(ref: new Model(type: StepDayDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Step count out of range.')]
    public function saveToday(
        #[CurrentUser] SecurityUser $securityUser,
        #[MapDataInput] SaveStepDayDataInput $input,
        SaveStepDayUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $input));
    }
}
