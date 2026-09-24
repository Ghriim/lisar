<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\DTO\Input\Habit\UpdateHabitDataInput;
use App\Domain\DTO\Output\Habit\HabitAdminDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\HabitAdminOutputFactory;
use App\Domain\Gateway\Persister\HabitPersisterGateway;
use App\Domain\Gateway\Provider\HabitProviderGateway;
use App\Domain\Registry\Habit\HabitSourceRegistry;
use App\Domain\Validation\Validator\Habit\UpdateHabitValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Reconfiguring a catalogue habit. Changing a tracker's mark changes what the next days need; it
 * does not rewrite the days already kept, whose outcome was frozen into their entry.
 */
final readonly class UpdateHabitUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateHabitValidator $validator,
        private HabitProviderGateway $habitProviderGateway,
        private HabitPersisterGateway $habitPersisterGateway,
        private HabitAdminOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id, UpdateHabitDataInput $input): HabitAdminDataOutput
    {
        $habit = $this->habitProviderGateway->findOneById($id);
        if (null === $habit) {
            throw new DataModelNotFoundException(HabitDataModel::class);
        }

        $this->validator->validate($input);

        $habit->name = $input->name;
        $habit->icon = $input->icon;
        $habit->sourceKind = $input->sourceKind;

        if (HabitSourceRegistry::TRACKER === $input->sourceKind) {
            $habit->trackerKind = $input->trackerKind;
            $habit->trackerThreshold = $input->trackerThreshold;
        } else {
            // Turned manual: it no longer watches anything.
            $habit->trackerKind = null;
            $habit->trackerThreshold = null;
        }

        $this->habitPersisterGateway->update($habit);

        return $this->outputFactory->buildOne($habit);
    }
}
