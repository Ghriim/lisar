<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\SaveHabitPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\HabitPlanningEntryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Planning\HabitPlanningEntryDataModelFactory;
use App\Domain\Factory\OutputFactory\Planning\HabitPlanningOutputFactory;
use App\Domain\Gateway\Persister\Planning\HabitPlanningEntryPersisterGateway;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Gateway\Provider\Planning\HabitPlanningEntryProviderGateway;
use App\Domain\Validation\Validator\Planning\UpdateHabitPlanningEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * A habit entry replaced whole. Nothing of the past is stored, so nothing of it moves: a day kept
 * stays kept — it is the habit's own — and the dates to come follow the new rule.
 */
final readonly class UpdateHabitPlanningEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateHabitPlanningEntryValidator $validator,
        private HabitPlanningEntryProviderGateway $entryProviderGateway,
        private HabitProviderGateway $habitProviderGateway,
        private HabitSubscriptionProviderGateway $habitSubscriptionProviderGateway,
        private HabitPlanningEntryPersisterGateway $entryPersisterGateway,
        private HabitPlanningEntryDataModelFactory $entryFactory,
        private HabitPlanningOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $entryId, SaveHabitPlanningEntryDataInput $input): HabitPlanningEntryDataOutput
    {
        $entry = $this->entryProviderGateway->findOneById($entryId);
        if (null === $entry) {
            throw new DataModelNotFoundException(HabitPlanningEntryDataModel::class);
        }

        $subscription = $entry->subscription->habit->id === $input->habitId ? $entry->subscription : $this->subscriptionTo($currentUser, $input->habitId);

        $this->validator->validate($currentUser, $entry, $input, $subscription);
        if (null === $subscription) {
            // Never reached: the validator refuses a habit the caller does not keep.
            throw new DataModelNotFoundException(HabitSubscriptionDataModel::class);
        }

        $this->entryFactory->apply($entry, $subscription, $input->schedule);
        $this->entryPersisterGateway->update($entry);

        return $this->outputFactory->buildOne($entry);
    }

    /** The caller's subscription to another habit than the one the entry says, if any. */
    private function subscriptionTo(UserDataModel $currentUser, int $habitId): ?HabitSubscriptionDataModel
    {
        $habit = $this->habitProviderGateway->findOneById($habitId);

        return null === $habit ? null : $this->habitSubscriptionProviderGateway->findOneForOwnerAndHabit($currentUser, $habit);
    }
}
