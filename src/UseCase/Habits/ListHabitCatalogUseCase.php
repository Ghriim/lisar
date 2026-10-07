<?php

declare(strict_types=1);

namespace App\UseCase\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Habits\HabitCatalogItemDataOutput;
use App\Domain\Factory\OutputFactory\Habits\HabitCatalogItemOutputFactory;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** The catalogue to subscribe from: every active habit, each flagged with whether it is kept. */
final readonly class ListHabitCatalogUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private HabitProviderGateway $habitProviderGateway,
        private HabitSubscriptionProviderGateway $habitSubscriptionProviderGateway,
        private HabitCatalogItemOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<HabitCatalogItemDataOutput>
     *
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId): array
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $subscribedHabitIds = [];
        foreach ($this->habitSubscriptionProviderGateway->findActiveForOwner($owner) as $subscription) {
            $subscribedHabitIds[] = $subscription->habit->id ?? 0;
        }

        return $this->outputFactory->buildMany(
            $this->habitProviderGateway->findAllActive(),
            $subscribedHabitIds,
        );
    }
}
