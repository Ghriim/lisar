<?php

declare(strict_types=1);

namespace App\UseCase\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Habits\HabitDataOutput;
use App\Domain\Factory\OutputFactory\Habits\HabitOutputFactory;
use App\Domain\Gateway\Provider\Habits\HabitEntryProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Habits\ListHabitsValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

use function sprintf;

/** The person's kept habits, each with its last seven days. It writes nothing. */
final readonly class ListHabitsUseCase implements UseCaseInterface
{
    public function __construct(
        private ListHabitsValidator $validator,
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
    public function execute(UserDataModel $currentUser): array
    {
        $this->validator->validate($currentUser);

        $today = $this->clock->today();
        $since = $today->modify(sprintf('-%d days', HabitOutputFactory::WINDOW_IN_DAYS - 1));

        $subscriptions = $this->habitSubscriptionProviderGateway->findActiveForOwner($currentUser);
        $entries = $this->habitEntryProviderGateway->findForOwnerSince($currentUser, $since);

        return $this->outputFactory->buildMany($subscriptions, $entries, $today);
    }
}
