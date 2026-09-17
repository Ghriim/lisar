<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Domain\DTO\Input\Task\CreateTaskDataInput;
use App\Domain\DTO\Input\Task\ListTasksDataInput;
use App\Domain\DTO\Input\Task\UpdateTaskDataInput;
use App\Domain\DTO\Output\Task\TaskDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\Infrastructure\Security\SecurityUser;
use App\UseCase\Task\CompleteTaskUseCase;
use App\UseCase\Task\CreateTaskUseCase;
use App\UseCase\Task\DeleteTaskUseCase;
use App\UseCase\Task\GetTaskUseCase;
use App\UseCase\Task\ListTasksUseCase;
use App\UseCase\Task\ReopenTaskUseCase;
use App\UseCase\Task\UpdateTaskUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * The todo list. Every route works on the tasks of the account the access token belongs to:
 * someone else's task is answered as not found.
 */
#[OA\Tag(name: 'Tasks')]
final class TaskController extends AbstractController
{
    #[Route('/api/tasks', methods: Request::METHOD_GET)]
    #[OA\Parameter(
        name: 'isDone',
        in: 'query',
        description: 'False (the default) for the list, true for the completed view.',
        schema: new OA\Schema(type: 'boolean', default: false),
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Root tasks with their subtasks nested, by category then by priority.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: TaskDataOutput::class))),
    )]
    public function listTasks(
        #[CurrentUser] SecurityUser $securityUser,
        #[MapDataInput] ListTasksDataInput $input,
        ListTasksUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $input));
    }

    #[Route('/api/tasks', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateTaskDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'The task was created. Set parentId to create a subtask.',
        content: new OA\JsonContent(ref: new Model(type: TaskDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid payload.')]
    public function createTask(
        #[CurrentUser] SecurityUser $securityUser,
        #[MapDataInput] CreateTaskDataInput $input,
        CreateTaskUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $input), Response::HTTP_CREATED);
    }

    #[Route('/api/tasks/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The task, with its subtasks.', content: new OA\JsonContent(ref: new Model(type: TaskDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such task on this account.')]
    public function getTask(int $id, #[CurrentUser] SecurityUser $securityUser, GetTaskUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($securityUser->id, $id));
    }

    #[Route('/api/tasks/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateTaskDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The task as it now stands.', content: new OA\JsonContent(ref: new Model(type: TaskDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such task on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid payload.')]
    public function updateTask(
        int $id,
        #[CurrentUser] SecurityUser $securityUser,
        #[MapDataInput] UpdateTaskDataInput $input,
        UpdateTaskUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($securityUser->id, $id, $input));
    }

    #[Route('/api/tasks/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted, with its subtasks.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such task on this account.')]
    public function deleteTask(int $id, #[CurrentUser] SecurityUser $securityUser, DeleteTaskUseCase $useCase): JsonResponse
    {
        $useCase->execute($securityUser->id, $id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/tasks/{id}/complete', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The task, ticked off.', content: new OA\JsonContent(ref: new Model(type: TaskDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such task on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'A subtask is still open.')]
    public function completeTask(int $id, #[CurrentUser] SecurityUser $securityUser, CompleteTaskUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($securityUser->id, $id));
    }

    #[Route('/api/tasks/{id}/reopen', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The task, back on the list.', content: new OA\JsonContent(ref: new Model(type: TaskDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such task on this account.')]
    public function reopenTask(int $id, #[CurrentUser] SecurityUser $securityUser, ReopenTaskUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($securityUser->id, $id));
    }
}
