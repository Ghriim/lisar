<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Planning;

use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\TaskPlanningDataOutput;
use App\Domain\DTO\Output\Planning\TaskPlanningEntryDataOutput;
use App\Infrastructure\Controller\AbstractBaseController;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Planning\CreateTaskPlanningEntryUseCase;
use App\UseCase\Planning\DeleteTaskPlanningEntryUseCase;
use App\UseCase\Planning\ListTaskPlanningUseCase;
use App\UseCase\Planning\UpdateTaskPlanningEntryUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Task planning entries: a task model planned on a rule of dates, each date making its own task.
 * Every route works on the entries of the account the access token belongs to: someone else's is
 * answered as not found.
 */
#[OA\Tag(name: 'Planning')]
final class TaskPlanningController extends AbstractBaseController
{
    #[Route('/api/planning/task', methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'from', in: 'query', required: true, description: 'The first day, "YYYY-MM-DD".', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'to', in: 'query', required: true, description: 'The last day, included; six weeks at most from the first.', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Every entry of the account, and the dates they give over those days, each open, done or to come.', content: new OA\JsonContent(ref: new Model(type: TaskPlanningDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Days missing, malformed, or too far apart.')]
    public function listTaskPlanning(
        #[MapDataInput] ListPlanningDataInput $input,
        ListTaskPlanningUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $input));
    }

    #[Route('/api/planning/task', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: SaveTaskPlanningEntryDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'Planned; a single date\'s task, or today\'s, is made at once.', content: new OA\JsonContent(ref: new Model(type: TaskPlanningEntryDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A model or a schedule that does not hold, or no such category for this account.')]
    public function createTaskPlanningEntry(
        #[MapDataInput] SaveTaskPlanningEntryDataInput $input,
        CreateTaskPlanningEntryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $input), Response::HTTP_CREATED);
    }

    #[Route('/api/planning/task/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: SaveTaskPlanningEntryDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Replaced whole: the dates to come follow it, the tasks already fallen stay.', content: new OA\JsonContent(ref: new Model(type: TaskPlanningEntryDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such entry on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A model or a schedule that does not hold, or no such category for this account.')]
    public function updateTaskPlanningEntry(
        int $id,
        #[MapDataInput] SaveTaskPlanningEntryDataInput $input,
        UpdateTaskPlanningEntryUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $input));
    }

    #[Route('/api/planning/task/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted with its tasks to come; the tasks already fallen stay.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such entry on this account.')]
    public function deleteTaskPlanningEntry(
        int $id,
        DeleteTaskPlanningEntryUseCase $useCase,
    ): JsonResponse {
        $useCase->execute($this->getCurrentUser(), $id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
