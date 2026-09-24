<?php

declare(strict_types=1);

namespace App\UseCase\Habit;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\Habit\HabitDataOutput;
use App\Domain\Factory\OutputFactory\HabitOutputFactory;
use App\Domain\Gateway\Provider\HabitEntryProviderGateway;
use App\Domain\Gateway\Provider\HabitSubscriptionProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

use function sprintf;

/** The person's kept habits, each with its last seven days. It writes nothing. */
final readonly class ListHabitsUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private HabitSubscriptionProviderGateway $habitSubscriptionProviderGateway,
        private HabitEntryProviderGateway $habitEntryProviderGateway,
        private HabitOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @return list<HabitDataOutput>
     *
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId): array
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $today = $this->clock->today();
        $since = $today->modify(sprintf('-%d days', HabitOutputFactory::WINDOW_IN_DAYS - 1));

        $subscriptions = $this->habitSubscriptionProviderGateway->findActiveForOwner($owner);
        $entries = $this->habitEntryProviderGateway->findForOwnerSince($owner, $since);

        return $this->outputFactory->buildMany($subscriptions, $entries, $today);
    }
}
