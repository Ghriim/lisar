<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\HydrationPresetDataModel;
use App\Domain\DTO\Input\Hydration\CreateHydrationPresetDataInput;
use App\Domain\DTO\Output\Hydration\HydrationPresetDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\HydrationPresetOutputFactory;
use App\Domain\Gateway\Persister\HydrationPresetPersisterGateway;
use App\Domain\Validation\Validator\Hydration\CreateHydrationPresetValidator;
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
