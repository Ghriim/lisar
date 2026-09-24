<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\DTO\Input\Habit\CreateHabitDataInput;
use App\Domain\DTO\Output\Habit\HabitAdminDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\HabitAdminOutputFactory;
use App\Domain\Gateway\Persister\HabitPersisterGateway;
use App\Domain\Registry\Habit\HabitSourceRegistry;
use App\Domain\Validation\Validator\Habit\CreateHabitValidator;
use App\UseCase\UseCaseInterface;

/** Adding a habit to the catalogue, offered to everyone to subscribe to from now on. */
final readonly class CreateHabitUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateHabitValidator $validator,
        private HabitPersisterGateway $habitPersisterGateway,
        private HabitAdminOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(CreateHabitDataInput $input): HabitAdminDataOutput
    {
        $this->validator->validate($input);

        $habit = new HabitDataModel();
        $habit->name = $input->name;
        $habit->icon = $input->icon;
        $habit->sourceKind = $input->sourceKind;

        // A manual habit keeps its tracker fields null; only a tracker habit names one and a mark.
        if (HabitSourceRegistry::TRACKER === $input->sourceKind) {
            $habit->trackerKind = $input->trackerKind;
            $habit->trackerThreshold = $input->trackerThreshold;
        }

        $this->habitPersisterGateway->create($habit);

        return $this->outputFactory->buildOne($habit);
    }
}
