<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Training;

use App\Domain\DTO\Input\Training\AddRoutineBlockDataInput;
use App\Domain\DTO\Input\Training\AddRoutineExerciseDataInput;
use App\Domain\DTO\Input\Training\AddRoutineSetDataInput;
use App\Domain\DTO\Input\Training\CreateRoutineDataInput;
use App\Domain\DTO\Input\Training\ReorderRoutineBlocksDataInput;
use App\Domain\DTO\Input\Training\UpdateRoutineDataInput;
use App\Domain\DTO\Input\Training\UpdateRoutineExerciseDataInput;
use App\Domain\DTO\Input\Training\UpdateRoutineSetDataInput;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\DTO\Output\Training\RoutineSummaryDataOutput;
use App\Domain\DTO\Output\Training\WorkoutCopyDataOutput;
use App\Infrastructure\Controller\AbstractBaseController;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Training\AddRoutineBlockUseCase;
use App\UseCase\Training\AddRoutineExerciseUseCase;
use App\UseCase\Training\AddRoutineSetUseCase;
use App\UseCase\Training\CopyRoutineUseCase;
use App\UseCase\Training\CreateRoutineUseCase;
use App\UseCase\Training\DeleteRoutineBlockUseCase;
use App\UseCase\Training\DeleteRoutineExerciseUseCase;
use App\UseCase\Training\DeleteRoutineSetUseCase;
use App\UseCase\Training\DeleteRoutineUseCase;
use App\UseCase\Training\GetRoutineUseCase;
use App\UseCase\Training\ListRoutinesUseCase;
use App\UseCase\Training\ReorderRoutineBlocksUseCase;
use App\UseCase\Training\StartWorkoutFromRoutineUseCase;
use App\UseCase\Training\UpdateRoutineExerciseUseCase;
use App\UseCase\Training\UpdateRoutineSetUseCase;
use App\UseCase\Training\UpdateRoutineUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Routines, the blueprints of workouts. Every route works on the routines of the account the
 * access token belongs to: someone else's routine is answered as not found. Each change inside a
 * routine — a block, a movement, a set — answers the whole routine, each set with the load it would
 * give today, so a screen redraws from one response.
 */
#[OA\Tag(name: 'Routines')]
final class RoutineController extends AbstractBaseController
{
    #[Route('/api/routines', methods: Request::METHOD_GET)]
    #[OA\Response(response: Response::HTTP_OK, description: 'The routines of the account, by name. Not paginated.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: RoutineSummaryDataOutput::class))))]
    public function listRoutines(
        ListRoutinesUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser()));
    }

    #[Route('/api/routines', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateRoutineDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'Created, empty.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'No name, or one too long.')]
    public function createRoutine(
        #[MapDataInput] CreateRoutineDataInput $input,
        CreateRoutineUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $input), Response::HTTP_CREATED);
    }

    #[Route('/api/routines/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The routine, whole, each set with the load it would give today.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account.')]
    public function getRoutine(
        int $id,
        GetRoutineUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id));
    }

    #[Route('/api/routines/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateRoutineDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The routine, renamed.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'No name, or one too long.')]
    public function updateRoutine(
        int $id,
        #[MapDataInput] UpdateRoutineDataInput $input,
        UpdateRoutineUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $input));
    }

    #[Route('/api/routines/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted; the workouts it started stay.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account.')]
    public function deleteRoutine(
        int $id,
        DeleteRoutineUseCase $useCase,
    ): JsonResponse {
        $useCase->execute($this->getCurrentUser(), $id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/routines/{id}/copy', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'A new routine, « Copie de … », holding everything that one holds.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account.')]
    public function copyRoutine(
        int $id,
        CopyRoutineUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id), Response::HTTP_CREATED);
    }

    #[Route('/api/routines/{id}/start', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'A workout started now, laid out like the routine, each load worked out from the estimated 1RMs of the day; with the movements left out because no longer offered.', content: new OA\JsonContent(ref: new Model(type: WorkoutCopyDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A workout is in progress, or the routine gives no set.')]
    public function startWorkoutFromRoutine(
        int $id,
        StartWorkoutFromRoutineUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id), Response::HTTP_CREATED);
    }

    #[Route('/api/routines/{id}/blocks', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: AddRoutineBlockDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'The routine, with the block added at the end.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'No movement, too many, duplicated, one not offered, or one tracking a duration or a distance.')]
    public function addRoutineBlock(
        int $id,
        #[MapDataInput] AddRoutineBlockDataInput $input,
        AddRoutineBlockUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $input), Response::HTTP_CREATED);
    }

    #[Route('/api/routines/{id}/blocks/order', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: ReorderRoutineBlocksDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The routine, its blocks in the new order.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The list is not exactly the routine\'s blocks.')]
    public function reorderRoutineBlocks(
        int $id,
        #[MapDataInput] ReorderRoutineBlocksDataInput $input,
        ReorderRoutineBlocksUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $input));
    }

    #[Route('/api/routines/{id}/blocks/{blockId}', requirements: ['id' => '\d+', 'blockId' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'blockId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The routine, without the block.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account, or nothing with that id in it.')]
    public function deleteRoutineBlock(
        int $id,
        int $blockId,
        DeleteRoutineBlockUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $blockId));
    }

    #[Route('/api/routines/{id}/blocks/{blockId}/exercises', requirements: ['id' => '\d+', 'blockId' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'blockId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: AddRoutineExerciseDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'The routine, the movement added at the end of the block.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The movement is not offered, or tracks a duration or a distance.')]
    public function addRoutineExercise(
        int $id,
        int $blockId,
        #[MapDataInput] AddRoutineExerciseDataInput $input,
        AddRoutineExerciseUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $blockId, $input), Response::HTTP_CREATED);
    }

    #[Route('/api/routines/{id}/exercises/{exerciseId}', requirements: ['id' => '\d+', 'exerciseId' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'exerciseId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateRoutineExerciseDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The routine, with the note and the rest.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Note too long, or a rest out of bounds.')]
    public function updateRoutineExercise(
        int $id,
        int $exerciseId,
        #[MapDataInput] UpdateRoutineExerciseDataInput $input,
        UpdateRoutineExerciseUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $exerciseId, $input));
    }

    #[Route('/api/routines/{id}/exercises/{exerciseId}', requirements: ['id' => '\d+', 'exerciseId' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'exerciseId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The routine, without the movement — nor its block, when it was the last of it.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account, or nothing with that id in it.')]
    public function deleteRoutineExercise(
        int $id,
        int $exerciseId,
        DeleteRoutineExerciseUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $exerciseId));
    }

    #[Route('/api/routines/{id}/exercises/{exerciseId}/sets', requirements: ['id' => '\d+', 'exerciseId' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'exerciseId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: AddRoutineSetDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'The routine, with the set added after the movement\'s others.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Reps or a share missing, a share on a movement without a load, or a value out of bounds.')]
    public function addRoutineSet(
        int $id,
        int $exerciseId,
        #[MapDataInput] AddRoutineSetDataInput $input,
        AddRoutineSetUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $exerciseId, $input), Response::HTTP_CREATED);
    }

    #[Route('/api/routines/{id}/sets/{setId}', requirements: ['id' => '\d+', 'setId' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'setId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateRoutineSetDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The routine, with the set corrected.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account, or nothing with that id in it.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Reps or a share missing, a share on a movement without a load, or a value out of bounds.')]
    public function updateRoutineSet(
        int $id,
        int $setId,
        #[MapDataInput] UpdateRoutineSetDataInput $input,
        UpdateRoutineSetUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $setId, $input));
    }

    #[Route('/api/routines/{id}/sets/{setId}', requirements: ['id' => '\d+', 'setId' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'setId', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The routine, without the set.', content: new OA\JsonContent(ref: new Model(type: RoutineDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such routine on this account, or nothing with that id in it.')]
    public function deleteRoutineSet(
        int $id,
        int $setId,
        DeleteRoutineSetUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $setId));
    }
}
