<?php

declare(strict_types=1);

namespace App\UseCase\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\Habits\HabitEntryDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Habits\HabitDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Habits\HabitOutputFactory;
use App\Domain\Gateway\Persister\Habits\HabitEntryPersisterGateway;
use App\Domain\Gateway\Provider\Habits\HabitEntryProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Habits\CompleteHabitValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

use function sprintf;

/**
 * Ticking a manual habit for the day in progress. Idempotent: the count of a day is either kept or
 * not, so ticking twice keeps it once — the moment it was first kept is not moved by a second tap.
 *
 * A tracker habit is not the person's to tick: it is kept by its tracker, and asking to tick one
 * is refused.
 */
final readonly class CompleteHabitUseCase implements UseCaseInterface
{
    public function __construct(
        private CompleteHabitValidator $validator,
        private HabitProviderGateway $habitProviderGateway,
        private HabitSubscriptionProviderGateway $habitSubscriptionProviderGateway,
        private HabitEntryProviderGateway $habitEntryProviderGateway,
        private HabitEntryPersisterGateway $habitEntryPersisterGateway,
        private HabitOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $habitId): HabitDataOutput
    {
        $habit = $this->habitProviderGateway->findOneById($habitId);
        if (null === $habit || false === $habit->isActive) {
            throw new DataModelNotFoundException(HabitDataModel::class);
        }

        $subscription = $this->habitSubscriptionProviderGateway->findOneForOwnerAndHabit($currentUser, $habit);
        // Not subscribed is answered as not found: a habit one does not keep is none of this route's business.
        if (null === $subscription || false === $subscription->isActive) {
            throw new DataModelNotFoundException(HabitSubscriptionDataModel::class);
        }

        $this->validator->validate($currentUser, $habit);

        $today = $this->clock->today();

        $entry = $this->habitEntryProviderGateway->findOneForSubscriptionAndDay($subscription, $today);
        $isNew = null === $entry;

        if (null === $entry) {
            $entry = new HabitEntryDataModel();
            $entry->subscription = $subscription;
            $entry->day = $today;
        }

        // Stamp the moment only on the transition into kept, so a second tap does not move it.
        if (false === $entry->isCompleted) {
            $entry->completedAt = $this->clock->now();
        }
        $entry->isCompleted = true;
        $entry->source = HabitSourceRegistry::MANUAL;

        if (true === $isNew) {
            $this->habitEntryPersisterGateway->create($entry);
        } else {
            $this->habitEntryPersisterGateway->update($entry);
        }

        $since = $today->modify(sprintf('-%d days', HabitOutputFactory::WINDOW_IN_DAYS - 1));
        $entries = $this->habitEntryProviderGateway->findForSubscriptionSince($subscription, $since);

        return $this->outputFactory->buildOne($subscription, $entries, $today);
    }
}
