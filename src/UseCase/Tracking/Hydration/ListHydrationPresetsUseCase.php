<?php

declare(strict_types=1);

namespace App\UseCase\Tracking\Hydration;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Tracking\Hydration\HydrationPresetDataOutput;
use App\Domain\Factory\OutputFactory\Tracking\Hydration\HydrationPresetOutputFactory;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationPresetProviderGateway;
use App\Domain\Validation\Validator\Tracking\Hydration\ListHydrationPresetsValidator;
use App\UseCase\UseCaseInterface;

/** The shortcuts, smallest volume first. The same set for everyone. */
final readonly class ListHydrationPresetsUseCase implements UseCaseInterface
{
    public function __construct(
        private ListHydrationPresetsValidator $validator,
        private HydrationPresetProviderGateway $hydrationPresetProviderGateway,
        private HydrationPresetOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<HydrationPresetDataOutput>
     */
    public function execute(UserDataModel $currentUser): array
    {
        $this->validator->validate($currentUser);

        return $this->outputFactory->buildMany(
            $this->hydrationPresetProviderGateway->findAllOrderedByVolume(),
        );
    }
}
