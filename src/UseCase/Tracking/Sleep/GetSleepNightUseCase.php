<?php

declare(strict_types=1);

namespace App\UseCase\Tracking\Sleep;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Tracking\Sleep\SleepNightDataOutput;
use App\Domain\Factory\OutputFactory\Tracking\Sleep\SleepNightOutputFactory;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepNightProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Tracking\Sleep\GetSleepNightValidator;
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
        private GetSleepNightValidator $validator,
        private SleepNightProviderGateway $sleepNightProviderGateway,
        private SleepNightOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(UserDataModel $currentUser): SleepNightDataOutput
    {
        $this->validator->validate($currentUser);

        $today = $this->clock->today();
        $night = $this->sleepNightProviderGateway->findOneForOwnerAndDay($currentUser, $today);

        if (null === $night) {
            return $this->outputFactory->buildEmpty($today);
        }

        return $this->outputFactory->buildOne($night);
    }
}
