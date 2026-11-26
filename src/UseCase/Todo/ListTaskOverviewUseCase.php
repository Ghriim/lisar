<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Todo\TaskDataOutput;
use App\Domain\Factory\OutputFactory\Todo\TaskOutputFactory;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Todo\ListTaskOverviewValidator;
use App\UseCase\UseCaseInterface;

/**
 * What the account has on now, across its active TODOs: the tasks due today or late, and the
 * favourite ones whatever their date — open, or done today, kept so the day's work stays in sight
 * ticked. A favourite done before today is not one of them. Root tasks, with their subtasks
 * nested, sorted as a list is. What shows where is the caller's own business.
 */
final readonly class ListTaskOverviewUseCase implements UseCaseInterface
{
    public function __construct(
        private ListTaskOverviewValidator $validator,
        private TaskProviderGateway $taskProviderGateway,
        private TaskOutputFactory $outputFactory,
        private DayClock $dayClock,
    ) {
    }

    /**
     * @return list<TaskDataOutput>
     */
    public function execute(UserDataModel $currentUser): array
    {
        $this->validator->validate($currentUser);

        $today = $this->dayClock->today();

        return $this->outputFactory->buildMany($this->taskProviderGateway->findOverviewForOwner(
            $currentUser,
            $today,
            $this->dayClock->asStoredInstant($today),
            $this->dayClock->asStoredInstant($today->modify('+1 day')),
        ));
    }
}
