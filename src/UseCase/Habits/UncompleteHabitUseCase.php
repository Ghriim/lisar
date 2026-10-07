<?php

declare(strict_types=1);

namespace App\UseCase\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Habits\HabitDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Habits\HabitOutputFactory;
use App\Domain\Gateway\Persister\Habits\HabitEntryPersisterGateway;
use App\Domain\Gateway\Provider\Habits\HabitEntryProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Tracking\DayClock;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

use function sprintf;

/**
 * Un-ticking a manual habit for the day in progress — the undo of CompleteHabitUseCase, for the
 * mis-tap. Idempotent: a day that was not kept stays not kept.
 *
 * Only the day in progress, only a manual habit: a tracker habit is un-kept by its tracker, not by
 * hand.
 */
final readonly class UncompleteHabitUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'uncomplete_habit_invalid';
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
        if (null === $subscription || false === $subscription->isActive) {
            throw new DataModelNotFoundException(HabitSubscriptionDataModel::class);
        }

        if (HabitSourceRegistry::MANUAL !== $habit->sourceKind) {
            throw new ValidationException(self::ERROR_CODE, ['habit' => [self::NOT_MANUAL]]);
        }

        $today = $this->clock->today();

        $entry = $this->habitEntryProviderGateway->findOneForSubscriptionAndDay($subscription, $today);
        // Only touch a row that was actually kept; an absent or already-unkept day needs nothing.
        if (null !== $entry && true === $entry->isCompleted) {
            $entry->isCompleted = false;
            $entry->completedAt = null;

            $this->habitEntryPersisterGateway->update($entry);
        }

        $since = $today->modify(sprintf('-%d days', HabitOutputFactory::WINDOW_IN_DAYS - 1));
        $entries = $this->habitEntryProviderGateway->findForSubscriptionSince($subscription, $since);

        return $this->outputFactory->buildOne($subscription, $entries, $today);
    }
}
