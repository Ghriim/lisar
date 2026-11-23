<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Planning;

use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Input\Planning\SaveTrainingPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\TrainingPlanningDataOutput;
use App\Domain\DTO\Output\Planning\TrainingPlanningEntryDataOutput;
use App\Infrastructure\Controller\AbstractBaseController;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Planning\CreateTrainingPlanningEntryUseCase;
use App\UseCase\Planning\DeleteTrainingPlanningEntryUseCase;
use App\UseCase\Planning\ListTrainingPlanningUseCase;
use App\UseCase\Planning\UpdateTrainingPlanningEntryUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Training planning entries: routines planned on a rule of dates. Every route works on the entries
 * of the account the access token belongs to: someone else's is answered as not found.
 */
#[OA\Tag(name: 'Planning')]
final class TrainingPlanningController extends AbstractBaseController
{
    #[Route('/api/planning/training', methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'from', in: 'query', required: true, description: 'The first day, "YYYY-MM-DD".', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'to', in: 'query', required: true, description: 'The last day, included; six weeks at most from the first.', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Every entry of the account, and the slots they give over those days, each kept, missed or to come.', content: new OA\JsonContent(ref: new Model(type: TrainingPlanningDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Days missing, malformed, or too far apart.')]
    public function listTrainingPlanning(
        #[MapDataInput] ListPlanningDataInput $input,
        ListTrainingPlanningUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $input));
    }

    #[Route('/api/planning/training', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: SaveTrainingPlanningEntryDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'Planned.', content: new OA\JsonContent(ref: new Model(type: TrainingPlanningEntryDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'No such routine on this account, or a schedule that does not hold.')]
    public function createTrainingPlanningEntry(
        #[MapDataInput] SaveTrainingPlanningEntryDataInput $input,
        CreateTrainingPlanningEntryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $input), Response::HTTP_CREATED);
    }

    #[Route('/api/planning/training/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: SaveTrainingPlanningEntryDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Replaced whole: the dates to come follow it.', content: new OA\JsonContent(ref: new Model(type: TrainingPlanningEntryDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such entry on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'No such routine on this account, or a schedule that does not hold.')]
    public function updateTrainingPlanningEntry(
        int $id,
        #[MapDataInput] SaveTrainingPlanningEntryDataInput $input,
        UpdateTrainingPlanningEntryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $input));
    }

    #[Route('/api/planning/training/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted; the workouts that kept its slots stay.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such entry on this account.')]
    public function deleteTrainingPlanningEntry(
        int $id,
        DeleteTrainingPlanningEntryUseCase $useCase,
    ): JsonResponse {
        $useCase->execute($this->getCurrentUser(), $id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
