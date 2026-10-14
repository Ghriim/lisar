<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Training;

use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutExerciseDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\ListWorkoutsDataInput;
use App\Domain\DTO\Input\Training\ReorderWorkoutBlocksDataInput;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Domain\DTO\Input\Training\UpdateWorkoutDataInput;
use App\Domain\DTO\Input\Training\UpdateWorkoutExerciseDataInput;
use App\Domain\DTO\Input\Training\UpdateWorkoutSetDataInput;
use App\Domain\DTO\Output\Training\MovementDataOutput;
use App\Domain\DTO\Output\Training\PersonalBestBoardDataOutput;
use App\Domain\DTO\Output\Training\SetTypeDataOutput;
use App\Domain\DTO\Output\Training\WorkoutCopyDataOutput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\DTO\Output\Training\WorkoutPreviousPerformanceDataOutput;
use App\Domain\DTO\Output\Training\WorkoutStatsDataOutput;
use App\Domain\DTO\Output\Training\WorkoutSummaryDataOutput;
use App\Infrastructure\Controller\AbstractBaseController;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Training\AddWorkoutBlockUseCase;
use App\UseCase\Training\AddWorkoutExerciseUseCase;
use App\UseCase\Training\AddWorkoutSetUseCase;
use App\UseCase\Training\CompleteWorkoutSetUseCase;
use App\UseCase\Training\CopyWorkoutUseCase;
use App\UseCase\Training\DeleteWorkoutBlockUseCase;
use App\UseCase\Training\DeleteWorkoutExerciseUseCase;
use App\UseCase\Training\DeleteWorkoutSetUseCase;
use App\UseCase\Training\DeleteWorkoutUseCase;
use App\UseCase\Training\FinishWorkoutUseCase;
use App\UseCase\Training\GetCurrentWorkoutUseCase;
use App\UseCase\Training\GetWorkoutStatsUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use App\UseCase\Training\ListPersonalBestsUseCase;
use App\UseCase\Training\ListWorkoutMovementsUseCase;
use App\UseCase\Training\ListWorkoutPreviousPerformancesUseCase;
use App\UseCase\Training\ListWorkoutSetTypesUseCase;
use App\UseCase\Training\ListWorkoutsUseCase;
use App\UseCase\Training\ReorderWorkoutBlocksUseCase;
use App\UseCase\Training\StartWorkoutUseCase;
use App\UseCase\Training\UncompleteWorkoutSetUseCase;
use App\UseCase\Training\UpdateWorkoutExerciseUseCase;
use App\UseCase\Training\UpdateWorkoutSetUseCase;
use App\UseCase\Training\UpdateWorkoutUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Workouts, logged live. Every route works on the workouts of the account the access token
 * belongs to: someone else's workout is answered as not found. Each change inside a workout — a
 * block, a movement, a set — answers the whole workout, so a screen redraws from one response.
 */
#[OA\Tag(name: 'Workouts')]
final class WorkoutController extends AbstractBaseController
{
    #[Route('/api/workouts', methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Finished workouts, the latest started first, in the paginated envelope: items (summaries), total, page, perPage.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: WorkoutSummaryDataOutput::class))),
                new OA\Property(property: 'total', type: 'integer'),
                new OA\Property(property: 'page', type: 'integer'),
                new OA\Property(property: 'perPage', type: 'integer'),
            ],
        ),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid page or perPage.')]
    public function listWorkouts(
        #[MapDataInput] ListWorkoutsDataInput $input,
        ListWorkoutsUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $input));
    }

    #[Route('/api/workouts', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: StartWorkoutDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'Started now, empty.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Another workout is in progress, or the name is too long.')]
    public function startWorkout(
        #[MapDataInput] StartWorkoutDataInput $input,
        StartWorkoutUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $input), Response::HTTP_CREATED);
    }

    #[Route('/api/workouts/{id}/copy', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'Started now, laid out like that workout, its sets still to do; with the movements left out because no longer offered.', content: new OA\JsonContent(ref: new Model(type: WorkoutCopyDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A workout is in progress.')]
    public function copyWorkout(
        int $id,
        CopyWorkoutUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id), Response::HTTP_CREATED);
    }

    #[Route('/api/workouts/current', methods: Request::METHOD_GET)]
    #[OA\Response(response: Response::HTTP_OK, description: 'The workout in progress, whole.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'No workout in progress.')]
    public function getCurrentWorkout(
        GetCurrentWorkoutUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute($this->getCurrentUser());

        return null === $output ? new JsonResponse(null, Response::HTTP_NO_CONTENT) : new JsonResponse($output);
    }

    #[Route('/api/workouts/movements', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The movements a workout may take on now, by name.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: MovementDataOutput::class))),
    )]
    public function listWorkoutMovements(
        ListWorkoutMovementsUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser()));
    }

    #[Route('/api/workouts/set-types', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The set types a set may take on now, by name.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: SetTypeDataOutput::class))),
    )]
    public function listWorkoutSetTypes(
        ListWorkoutSetTypesUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser()));
    }

    #[Route('/api/workouts/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The workout, whole.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    public function getWorkout(
        int $id,
        GetWorkoutUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id));
    }

    #[Route('/api/workouts/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateWorkoutDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Updated; every field is replaced.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Name or note too long, or feeling out of 1–5.')]
    public function updateWorkout(
        int $id,
        #[MapDataInput] UpdateWorkoutDataInput $input,
        UpdateWorkoutUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $input));
    }

    #[Route('/api/workouts/{id}/finish', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'Finished now; one already finished is returned as it is.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The workout has no set, or a set not ticked as done.')]
    public function finishWorkout(
        int $id,
        FinishWorkoutUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id));
    }

    #[Route('/api/workouts/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted — abandoned, for the one in progress.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    public function deleteWorkout(
        int $id,
        DeleteWorkoutUseCase $useCase,
    ): JsonResponse {
        $useCase->execute($this->getCurrentUser(), $id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/workouts/personal-bests', methods: Request::METHOD_GET)]
    #[OA\Response(response: Response::HTTP_OK, description: 'The account\'s records: those of whole workouts, then movement by movement, each with its progression.', content: new OA\JsonContent(ref: new Model(type: PersonalBestBoardDataOutput::class)))]
    public function listPersonalBests(
        ListPersonalBestsUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser()));
    }

    #[Route('/api/workouts/{id}/stats', requirements: ['id' => '\d+'], methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'What it amounts to: sets, load, time, distance, the share of each muscle, and the personal bests it beat.', content: new OA\JsonContent(ref: new Model(type: WorkoutStatsDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account.')]
    public function getWorkoutStats(
        int $id,
        GetWorkoutStatsUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id));
    }

    #[Route('/api/workouts/{id}/previous-performances', requirements: ['id' => '\d+'], methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'For each movement done before, its sets the last time.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: WorkoutPreviousPerformanceDataOutput::class))),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    public function listWorkoutPreviousPerformances(
        int $id,
        ListWorkoutPreviousPerformancesUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id));
    }

    #[Route('/api/workouts/{id}/blocks', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: AddWorkoutBlockDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'The workout, with the block added at the end.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'No movement, too many, duplicated, or one not offered.')]
    public function addWorkoutBlock(
        int $id,
        #[MapDataInput] AddWorkoutBlockDataInput $input,
        AddWorkoutBlockUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $input), Response::HTTP_CREATED);
    }

    #[Route('/api/workouts/{id}/blocks/order', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: ReorderWorkoutBlocksDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The workout, its blocks in the new order.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The list is not exactly the workout\'s blocks.')]
    public function reorderWorkoutBlocks(
        int $id,
        #[MapDataInput] ReorderWorkoutBlocksDataInput $input,
        ReorderWorkoutBlocksUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $input));
    }

    #[Route('/api/workouts/{id}/blocks/{blockId}', requirements: ['id' => '\d+', 'blockId' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'blockId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The workout, without the block.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    public function deleteWorkoutBlock(
        int $id,
        int $blockId,
        DeleteWorkoutBlockUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $blockId));
    }

    #[Route('/api/workouts/{id}/blocks/{blockId}/exercises', requirements: ['id' => '\d+', 'blockId' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'blockId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: AddWorkoutExerciseDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'The workout, the movement added at the end of the block.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The movement is not offered.')]
    public function addWorkoutExercise(
        int $id,
        int $blockId,
        #[MapDataInput] AddWorkoutExerciseDataInput $input,
        AddWorkoutExerciseUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $blockId, $input), Response::HTTP_CREATED);
    }

    #[Route('/api/workouts/{id}/exercises/{exerciseId}', requirements: ['id' => '\d+', 'exerciseId' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'exerciseId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateWorkoutExerciseDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The workout, with the note.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Note too long.')]
    public function updateWorkoutExercise(
        int $id,
        int $exerciseId,
        #[MapDataInput] UpdateWorkoutExerciseDataInput $input,
        UpdateWorkoutExerciseUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $exerciseId, $input));
    }

    #[Route('/api/workouts/{id}/exercises/{exerciseId}', requirements: ['id' => '\d+', 'exerciseId' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'exerciseId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The workout, without the movement — and without its block, if it was the last one in it.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    public function deleteWorkoutExercise(
        int $id,
        int $exerciseId,
        DeleteWorkoutExerciseUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $exerciseId));
    }

    #[Route('/api/workouts/{id}/exercises/{exerciseId}/sets', requirements: ['id' => '\d+', 'exerciseId' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'exerciseId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: AddWorkoutSetDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'The workout, with the set added.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A measure missing or not tracked, out of bounds, or a set type unknown or retired.')]
    public function addWorkoutSet(
        int $id,
        int $exerciseId,
        #[MapDataInput] AddWorkoutSetDataInput $input,
        AddWorkoutSetUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $exerciseId, $input), Response::HTTP_CREATED);
    }

    #[Route('/api/workouts/{id}/sets/{setId}', requirements: ['id' => '\d+', 'setId' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'setId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateWorkoutSetDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The workout, with the set corrected.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A measure missing or not tracked, out of bounds, or a set type unknown or retired.')]
    public function updateWorkoutSet(
        int $id,
        int $setId,
        #[MapDataInput] UpdateWorkoutSetDataInput $input,
        UpdateWorkoutSetUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $setId, $input));
    }

    #[Route('/api/workouts/{id}/sets/{setId}', requirements: ['id' => '\d+', 'setId' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'setId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The workout, without the set.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    public function deleteWorkoutSet(
        int $id,
        int $setId,
        DeleteWorkoutSetUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $setId));
    }

    #[Route('/api/workouts/{id}/sets/{setId}/complete', requirements: ['id' => '\d+', 'setId' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'setId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The workout, the set ticked as done; one already ticked stays so.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The workout is finished.')]
    public function completeWorkoutSet(
        int $id,
        int $setId,
        CompleteWorkoutSetUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $setId));
    }

    #[Route('/api/workouts/{id}/sets/{setId}/complete', requirements: ['id' => '\d+', 'setId' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'setId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The workout, the set no longer ticked; one not ticked stays so.', content: new OA\JsonContent(ref: new Model(type: WorkoutDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such workout on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The workout is finished.')]
    public function uncompleteWorkoutSet(
        int $id,
        int $setId,
        UncompleteWorkoutSetUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $setId));
    }
}
