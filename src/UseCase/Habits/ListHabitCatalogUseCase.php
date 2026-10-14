<?php

declare(strict_types=1);

namespace App\UseCase\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Habits\HabitCatalogItemDataOutput;
use App\Domain\Factory\OutputFactory\Habits\HabitCatalogItemOutputFactory;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Validation\Validator\Habits\ListHabitCatalogValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** The catalogue to subscribe from: every active habit, each flagged with whether it is kept. */
final readonly class ListHabitCatalogUseCase implements UseCaseInterface
{
    public function __construct(
        private ListHabitCatalogValidator $validator,
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
    public function execute(UserDataModel $currentUser): array
    {
        $this->validator->validate($currentUser);

        $subscribedHabitIds = [];
        foreach ($this->habitSubscriptionProviderGateway->findActiveForOwner($currentUser) as $subscription) {
            $subscribedHabitIds[] = $subscription->habit->id ?? 0;
        }

        return $this->outputFactory->buildMany(
            $this->habitProviderGateway->findAllActive(),
            $subscribedHabitIds,
        );
    }
}
