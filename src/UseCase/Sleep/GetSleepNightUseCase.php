<?php

declare(strict_types=1);

namespace App\UseCase\Sleep;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\Sleep\SleepNightDataOutput;
use App\Domain\Factory\OutputFactory\SleepNightOutputFactory;
use App\Domain\Gateway\Provider\SleepNightProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * The night of the day in progress, or nothing.
 *
 * Deliberately not "the last night noted", which is what the weight tracker answers: a weight
 * persists, a night does not. Yesterday's seven hours say nothing about how someone is today,
 * and showing them would be showing a number that has stopped being true.
 *
 * It writes nothing: a night exists from the moment it is noted, and looking at the widget is
 * not noting one.
 */
final readonly class GetSleepNightUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private SleepNightProviderGateway $sleepNightProviderGateway,
        private SleepNightOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId): SleepNightDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $today = $this->clock->today();
        $night = $this->sleepNightProviderGateway->findOneForOwnerAndDay($owner, $today);

        if (null === $night) {
            return $this->outputFactory->buildEmpty($today);
        }

        return $this->outputFactory->buildOne($night);
    }
}
