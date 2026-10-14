<?php

declare(strict_types=1);

namespace App\UseCase\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Habits\HabitCatalogItemDataOutput;
use App\Domain\Factory\OutputFactory\Habits\HabitCatalogItemOutputFactory;
use App\Domain\Gateway\Persister\Habits\HabitSubscriptionPersisterGateway;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Validation\Validator\Habits\UnsubscribeHabitValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Dropping a habit. The subscription is deactivated, not deleted: the days already kept stay, so
 * resuming later picks the run back up rather than starting it over.
 */
final readonly class UnsubscribeHabitUseCase implements UseCaseInterface
{
    public function __construct(
        private UnsubscribeHabitValidator $validator,
        private HabitProviderGateway $habitProviderGateway,
        private HabitSubscriptionProviderGateway $habitSubscriptionProviderGateway,
        private HabitSubscriptionPersisterGateway $habitSubscriptionPersisterGateway,
        private HabitCatalogItemOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(UserDataModel $currentUser, int $habitId): HabitCatalogItemDataOutput
    {
        $habit = $this->habitProviderGateway->findOneById($habitId);
        if (null === $habit) {
            throw new DataModelNotFoundException(HabitDataModel::class);
        }

        $subscription = $this->habitSubscriptionProviderGateway->findOneForOwnerAndHabit($currentUser, $habit);
        if (null === $subscription || false === $subscription->isActive) {
            throw new DataModelNotFoundException(HabitSubscriptionDataModel::class);
        }

        $this->validator->validate($currentUser);

        $subscription->isActive = false;

        $this->habitSubscriptionPersisterGateway->update($subscription);

        return $this->outputFactory->buildOne($habit, false);
    }
}
