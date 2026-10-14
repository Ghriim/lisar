<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Todo;

use App\Domain\DTO\Output\Todo\PriorityDataOutput;
use App\Infrastructure\Controller\AbstractBaseController;
use App\UseCase\Todo\ListPrioritiesUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Priorities')]
final class PriorityController extends AbstractBaseController
{
    #[Route('/api/priorities', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Every priority, lightest weight first.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: PriorityDataOutput::class))),
    )]
    public function listPriorities(ListPrioritiesUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($this->getCurrentUser()));
    }
}
