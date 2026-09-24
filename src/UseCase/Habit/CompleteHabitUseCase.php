<?php

declare(strict_types=1);

namespace App\UseCase\Habit;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\DTO\DataModel\HabitEntryDataModel;
use App\Domain\DTO\DataModel\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\Habit\HabitDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\HabitOutputFactory;
use App\Domain\Gateway\Persister\HabitEntryPersisterGateway;
use App\Domain\Gateway\Provider\HabitEntryProviderGateway;
use App\Domain\Gateway\Provider\HabitProviderGateway;
use App\Domain\Gateway\Provider\HabitSubscriptionProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Registry\Habit\HabitSourceRegistry;
use App\Domain\Tracking\DayClock;
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
    public const string ERROR_CODE = 'complete_habit_invalid';
    public const string NOT_MANUAL = 'habit_not_manual';

    public function __construct(
        private UserProviderGateway $userProviderGateway,
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
    public function execute(int $ownerId, int $habitId): HabitDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $habit = $this->habitProviderGateway->findOneById($habitId);
        if (null === $habit || false === $habit->isActive) {
            throw new DataModelNotFoundException(HabitDataModel::class);
        }

        $subscription = $this->habitSubscriptionProviderGateway->findOneForOwnerAndHabit($owner, $habit);
        // Not subscribed is answered as not found: a habit one does not keep is none of this route's business.
        if (null === $subscription || false === $subscription->isActive) {
            throw new DataModelNotFoundException(HabitSubscriptionDataModel::class);
        }

        if (HabitSourceRegistry::MANUAL !== $habit->sourceKind) {
            throw new ValidationException(self::ERROR_CODE, ['habit' => [self::NOT_MANUAL]]);
        }

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
