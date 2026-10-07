<?php

declare(strict_types=1);

namespace App\UseCase\Tracking\Hydration\Admin;

use App\Domain\DTO\DataModel\Tracking\HydrationPresetDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\UpdateHydrationPresetDataInput;
use App\Domain\DTO\Output\Tracking\Hydration\HydrationPresetDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Tracking\Hydration\HydrationPresetOutputFactory;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationPresetPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationPresetProviderGateway;
use App\Domain\Validation\Validator\Tracking\Hydration\UpdateHydrationPresetValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Correcting a shortcut changes what the next tap logs, and nothing about what was logged
 * before: an entry copied its volume rather than pointing here.
 */
final readonly class UpdateHydrationPresetUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateHydrationPresetValidator $validator,
        private HydrationPresetProviderGateway $hydrationPresetProviderGateway,
        private HydrationPresetPersisterGateway $hydrationPresetPersisterGateway,
        private HydrationPresetOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id, UpdateHydrationPresetDataInput $input): HydrationPresetDataOutput
    {
        $preset = $this->hydrationPresetProviderGateway->findOneById($id);
        if (null === $preset) {
            throw new DataModelNotFoundException(HydrationPresetDataModel::class);
        }

        $this->validator->validate($input);

        $preset->icon = $input->icon;
        $preset->volumeInMillilitres = $input->volumeInMillilitres;

        $this->hydrationPresetPersisterGateway->update($preset);

        return $this->outputFactory->buildOne($preset);
    }
}
