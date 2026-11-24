<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Planning;

use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Input\Planning\SaveHabitPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\HabitPlanningDataOutput;
use App\Domain\DTO\Output\Planning\HabitPlanningEntryDataOutput;
use App\Infrastructure\Controller\AbstractBaseController;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Planning\CreateHabitPlanningEntryUseCase;
use App\UseCase\Planning\DeleteHabitPlanningEntryUseCase;
use App\UseCase\Planning\ListHabitPlanningUseCase;
use App\UseCase\Planning\UpdateHabitPlanningEntryUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Habit planning entries: the days a subscribed habit counts, on a rule of dates. Every route works on the entries
 * of the account the access token belongs to: someone else's is answered as not found.
 */
#[OA\Tag(name: 'Planning')]
final class HabitPlanningController extends AbstractBaseController
{
    #[Route('/api/planning/habit', methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'from', in: 'query', required: true, description: 'The first day, "YYYY-MM-DD".', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'to', in: 'query', required: true, description: 'The last day, included; six weeks at most from the first.', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Every entry of the account, the dates they give over those days, each kept, missed or to come, and the days a planned habit was kept unplanned.', content: new OA\JsonContent(ref: new Model(type: HabitPlanningDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Days missing, malformed, or too far apart.')]
    public function listHabitPlanning(
        #[MapDataInput] ListPlanningDataInput $input,
        ListHabitPlanningUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $input));
    }

    #[Route('/api/planning/habit', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: SaveHabitPlanningEntryDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'Planned.', content: new OA\JsonContent(ref: new Model(type: HabitPlanningEntryDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'No such habit kept on this account, or a schedule that does not hold.')]
    public function createHabitPlanningEntry(
        #[MapDataInput] SaveHabitPlanningEntryDataInput $input,
        CreateHabitPlanningEntryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $input), Response::HTTP_CREATED);
    }

    #[Route('/api/planning/habit/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: SaveHabitPlanningEntryDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Replaced whole: the dates to come follow it.', content: new OA\JsonContent(ref: new Model(type: HabitPlanningEntryDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such entry on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'No such habit kept on this account, or a schedule that does not hold.')]
    public function updateHabitPlanningEntry(
        int $id,
        #[MapDataInput] SaveHabitPlanningEntryDataInput $input,
        UpdateHabitPlanningEntryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $input));
    }

    #[Route('/api/planning/habit/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted; the days the habit was kept stay.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such entry on this account.')]
    public function deleteHabitPlanningEntry(
        int $id,
        DeleteHabitPlanningEntryUseCase $useCase,
    ): JsonResponse {
        $useCase->execute($this->getCurrentUser(), $id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
