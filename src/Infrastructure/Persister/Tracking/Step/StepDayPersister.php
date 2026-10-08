<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Tracking\Step;

use App\Domain\DTO\DataModel\Tracking\StepDayDataModel;
use App\Domain\DTO\Event\Tracking\StepDayCreatedEvent;
use App\Domain\DTO\Event\Tracking\StepDayUpdatedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\Tracking\Step\StepDayPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * @extends AbstractBaseMysqlPersister<StepDayDataModel>
 */
final class StepDayPersister extends AbstractBaseMysqlPersister implements StepDayPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager, $clock);
    }

    public function create(StepDayDataModel $stepDay): StepDayDataModel
    {
        $this->inTransaction(function () use ($stepDay): void {
            $this->persistAndStampCreate($stepDay);
            $this->eventDispatcher->dispatch(new StepDayCreatedEvent($stepDay));
        });

        return $stepDay;
    }

    public function update(StepDayDataModel $stepDay): StepDayDataModel
    {
        $this->inTransaction(function () use ($stepDay): void {
            $this->persistAndStampUpdate($stepDay);
            $this->eventDispatcher->dispatch(new StepDayUpdatedEvent($stepDay));
        });

        return $stepDay;
    }
}
