<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\SaveHabitPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\HabitPlanningEntryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Planning\HabitPlanningEntryDataModelFactory;
use App\Domain\Factory\OutputFactory\Planning\HabitPlanningOutputFactory;
use App\Domain\Gateway\Persister\Planning\HabitPlanningEntryPersisterGateway;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Validation\Validator\Planning\CreateHabitPlanningEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Some days of a habit the caller keeps planned on a rule of dates. Its first entry turns the habit
 * from every day to the days its entries ask for.
 */
final readonly class CreateHabitPlanningEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateHabitPlanningEntryValidator $validator,
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
    public function execute(UserDataModel $currentUser, SaveHabitPlanningEntryDataInput $input): HabitPlanningEntryDataOutput
    {
        // A habit not kept, or not offered any more, is refused by the validator, as one not found.
        $habit = $this->habitProviderGateway->findOneById($input->habitId);
        $subscription = null === $habit ? null : $this->habitSubscriptionProviderGateway->findOneForOwnerAndHabit($currentUser, $habit);

        $this->validator->validate($currentUser, $input, $subscription);
        if (null === $subscription) {
            // Never reached: the validator refuses a habit the caller does not keep.
            throw new DataModelNotFoundException(HabitSubscriptionDataModel::class);
        }

        $entry = $this->entryFactory->build($subscription, $input->schedule);
        $this->entryPersisterGateway->create($entry);

        return $this->outputFactory->buildOne($entry);
    }
}
