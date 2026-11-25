<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Gamification;

use App\Domain\DTO\Output\Gamification\StatusDataOutput;
use App\Infrastructure\Controller\AbstractBaseController;
use App\UseCase\Gamification\GetStatusUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** The status of the account the access token belongs to: its level and its stats. */
#[OA\Tag(name: 'Gamification')]
final class StatusController extends AbstractBaseController
{
    #[Route('/api/status', methods: Request::METHOD_GET)]
    #[OA\Response(response: Response::HTTP_OK, description: 'The global level, then each stat by group, each with its level and how far it is from the next.', content: new OA\JsonContent(ref: new Model(type: StatusDataOutput::class)))]
    public function getStatus(GetStatusUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($this->getCurrentUser()));
    }
}
