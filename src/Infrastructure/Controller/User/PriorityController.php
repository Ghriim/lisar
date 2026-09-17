<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Domain\DTO\Output\Task\PriorityDataOutput;
use App\UseCase\Task\ListPrioritiesUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Priorities')]
final class PriorityController extends AbstractController
{
    #[Route('/api/priorities', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Every priority, lightest weight first.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: PriorityDataOutput::class))),
    )]
    public function listPriorities(ListPrioritiesUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute());
    }
}
