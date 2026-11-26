<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Todo;

use App\Domain\DTO\Input\Todo\ListTodosDataInput;
use App\Domain\DTO\Input\Todo\SaveTodoDataInput;
use App\Domain\DTO\Output\Todo\TodoDataOutput;
use App\Infrastructure\Controller\AbstractBaseController;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Todo\ArchiveTodoUseCase;
use App\UseCase\Todo\CreateTodoUseCase;
use App\UseCase\Todo\DeleteTodoUseCase;
use App\UseCase\Todo\ListTodosUseCase;
use App\UseCase\Todo\ReactivateTodoUseCase;
use App\UseCase\Todo\UpdateTodoUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The account's TODOs, the lists its tasks sit in. Someone else's TODO is answered as not found.
 */
#[OA\Tag(name: 'TODOs')]
final class TodoController extends AbstractBaseController
{
    #[Route('/api/todos', methods: Request::METHOD_GET)]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        description: 'active or archived; every TODO when left out.',
        schema: new OA\Schema(type: 'string', enum: ['active', 'archived']),
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The TODOs, the default first, then by title.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: TodoDataOutput::class))),
    )]
    public function listTodos(#[MapDataInput] ListTodosDataInput $input, ListTodosUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $input));
    }

    #[Route('/api/todos', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: SaveTodoDataInput::class))]
    #[OA\Response(response: Response::HTTP_CREATED, description: 'The TODO, active.', content: new OA\JsonContent(ref: new Model(type: TodoDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'No title, too long, or one of the account\'s TODOs already has it.')]
    public function createTodo(#[MapDataInput] SaveTodoDataInput $input, CreateTodoUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $input), Response::HTTP_CREATED);
    }

    #[Route('/api/todos/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: SaveTodoDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The TODO, renamed.', content: new OA\JsonContent(ref: new Model(type: TodoDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such TODO on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Archived, or the title is refused.')]
    public function updateTodo(int $id, #[MapDataInput] SaveTodoDataInput $input, UpdateTodoUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id, $input));
    }

    #[Route('/api/todos/{id}/archive', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The TODO, archived; its planning entries are gone.', content: new OA\JsonContent(ref: new Model(type: TodoDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such TODO on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The default TODO is never archived.')]
    public function archiveTodo(int $id, ArchiveTodoUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id));
    }

    #[Route('/api/todos/{id}/reactivate', requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_OK, description: 'The TODO, active again.', content: new OA\JsonContent(ref: new Model(type: TodoDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such TODO on this account.')]
    public function reactivateTodo(int $id, ReactivateTodoUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($this->getCurrentUser(), $id));
    }

    #[Route('/api/todos/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted, with its tasks and its planning entries.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such TODO on this account.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The default TODO is never deleted.')]
    public function deleteTodo(int $id, DeleteTodoUseCase $useCase): JsonResponse
    {
        $useCase->execute($this->getCurrentUser(), $id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
