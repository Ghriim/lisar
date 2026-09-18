<?php

declare(strict_types=1);

namespace App\UseCase\Hydration;

use App\Domain\DTO\Output\Hydration\HydrationPresetDataOutput;
use App\Domain\Factory\OutputFactory\HydrationPresetOutputFactory;
use App\Domain\Gateway\Provider\HydrationPresetProviderGateway;
use App\UseCase\UseCaseInterface;

/** The shortcuts, smallest volume first. The same set for everyone. */
final readonly class ListHydrationPresetsUseCase implements UseCaseInterface
{
    public function __construct(
        private HydrationPresetProviderGateway $hydrationPresetProviderGateway,
        private HydrationPresetOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<HydrationPresetDataOutput>
     */
    public function execute(): array
    {
        return $this->outputFactory->buildMany(
            $this->hydrationPresetProviderGateway->findAllOrderedByVolume(),
        );
    }
}
