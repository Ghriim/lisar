<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\DTO\Input\Hydration\CreateHydrationPresetDataInput;
use App\Domain\DTO\Input\Hydration\UpdateHydrationPresetDataInput;
use App\Domain\DTO\Output\Hydration\HydrationPresetDataOutput;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Admin\CreateHydrationPresetUseCase;
use App\UseCase\Admin\DeleteHydrationPresetUseCase;
use App\UseCase\Admin\UpdateHydrationPresetUseCase;
use App\UseCase\Hydration\ListHydrationPresetsUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The quantities people log in one tap. An icon and a volume — no label: the icon and the volume
 * say it, and a word would have to be translated by each front end anyway.
 */
#[OA\Tag(name: 'Admin — hydration')]
final class AdminHydrationPresetController extends AbstractController
{
    #[Route('/api/admin/hydration/presets', methods: Request::METHOD_GET)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The shortcuts, smallest volume first.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: HydrationPresetDataOutput::class))),
    )]
    public function listPresets(ListHydrationPresetsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute());
    }

    #[Route('/api/admin/hydration/presets', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: CreateHydrationPresetDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'The shortcut, offered to everyone from now on.',
        content: new OA\JsonContent(ref: new Model(type: HydrationPresetDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Unknown icon, or volume out of range.')]
    public function createPreset(
        #[MapDataInput] CreateHydrationPresetDataInput $input,
        CreateHydrationPresetUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($input), Response::HTTP_CREATED);
    }

    #[Route('/api/admin/hydration/presets/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateHydrationPresetDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The shortcut. What was already logged with it does not change.',
        content: new OA\JsonContent(ref: new Model(type: HydrationPresetDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such shortcut.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Unknown icon, or volume out of range.')]
    public function updatePreset(
        int $id,
        #[MapDataInput] UpdateHydrationPresetDataInput $input,
        UpdateHydrationPresetUseCase $useCase,
    ): JsonResponse {
        return new JsonResponse($useCase->execute($id, $input));
    }

    #[Route('/api/admin/hydration/presets/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Deleted. Nobody’s past changes.')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such shortcut.')]
    public function deletePreset(int $id, DeleteHydrationPresetUseCase $useCase): JsonResponse
    {
        $useCase->execute($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
