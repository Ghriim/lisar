<?php

declare(strict_types=1);

namespace App\UseCase\Tracking\Hydration\Admin;

use App\Domain\DTO\DataModel\Tracking\HydrationPresetDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationPresetDataInput;
use App\Domain\DTO\Output\Tracking\Hydration\HydrationPresetDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Tracking\Hydration\HydrationPresetOutputFactory;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationPresetPersisterGateway;
use App\Domain\Validation\Validator\Tracking\Hydration\CreateHydrationPresetValidator;
use App\UseCase\UseCaseInterface;

final readonly class CreateHydrationPresetUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateHydrationPresetValidator $validator,
        private HydrationPresetPersisterGateway $hydrationPresetPersisterGateway,
        private HydrationPresetOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(CreateHydrationPresetDataInput $input): HydrationPresetDataOutput
    {
        $this->validator->validate($input);

        $preset = new HydrationPresetDataModel();
        $preset->icon = $input->icon;
        $preset->volumeInMillilitres = $input->volumeInMillilitres;

        $this->hydrationPresetPersisterGateway->create($preset);

        return $this->outputFactory->buildOne($preset);
    }
}
