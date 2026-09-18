<?php

declare(strict_types=1);

namespace App\UseCase\Weight;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\Weight\WeightDataOutput;
use App\Domain\Factory\OutputFactory\WeightOutputFactory;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Gateway\Provider\WeightEntryProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * The last known weight — not today's. Someone who skipped three days still sees where they
 * stand; showing nothing until they step on the scale would tell them the least on the day they
 * most want to know.
 */
final readonly class GetLatestWeightUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private WeightEntryProviderGateway $weightEntryProviderGateway,
        private WeightOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId): WeightDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $entry = $this->weightEntryProviderGateway->findLatestForOwner($owner);

        if (null === $entry) {
            return $this->outputFactory->buildEmpty();
        }

        return $this->outputFactory->buildOne($entry);
    }
}
