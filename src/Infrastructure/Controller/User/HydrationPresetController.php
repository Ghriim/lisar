<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Domain\DTO\Output\Hydration\HydrationPresetDataOutput;
use App\UseCase\Hydration\ListHydrationPresetsUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Hydration')]
final class HydrationPresetController extends AbstractController
{
    #[Route('/api/hydration/presets', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The shortcuts, smallest volume first.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: HydrationPresetDataOutput::class))),
    )]
    public function listPresets(ListHydrationPresetsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute());
    }
}
