<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\HydrationPresetDataModel;
use App\Domain\Gateway\Persister\HydrationPresetPersisterGateway;
use App\Domain\Gateway\Provider\HydrationPresetProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a shortcut is always allowed — unlike a priority or a common category.
 *
 * An entry copies the volume it logged rather than pointing at the shortcut, so removing one
 * removes a button and nothing else. Nobody's past changes. That is the whole reason the copy
 * was chosen over a relation, and it is worth remembering before anyone "normalises" it.
 */
final readonly class DeleteHydrationPresetUseCase implements UseCaseInterface
{
    public function __construct(
        private HydrationPresetProviderGateway $hydrationPresetProviderGateway,
        private HydrationPresetPersisterGateway $hydrationPresetPersisterGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $id): void
    {
        $preset = $this->hydrationPresetProviderGateway->findOneById($id);
        if (null === $preset) {
            throw new DataModelNotFoundException(HydrationPresetDataModel::class);
        }

        $this->hydrationPresetPersisterGateway->delete($preset);
    }
}
