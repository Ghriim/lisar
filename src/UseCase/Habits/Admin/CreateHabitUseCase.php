<?php

declare(strict_types=1);

namespace App\UseCase\Habits\Admin;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Habits\CreateHabitDataInput;
use App\Domain\DTO\Output\Habits\HabitAdminDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Habits\HabitAdminOutputFactory;
use App\Domain\Gateway\Persister\Habits\HabitPersisterGateway;
use App\Domain\Gateway\Provider\Category\CategoryProviderGateway;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Validation\Validator\Habits\CreateHabitValidator;
use App\UseCase\UseCaseInterface;

/** Adding a habit to the catalogue, offered to everyone to subscribe to from now on. */
final readonly class CreateHabitUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateHabitValidator $validator,
        private HabitPersisterGateway $habitPersisterGateway,
        private CategoryProviderGateway $categoryProviderGateway,
        private HabitAdminOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, CreateHabitDataInput $input): HabitAdminDataOutput
    {
        $category = null === $input->categoryId
            ? null
            : $this->categoryProviderGateway->findOneById($input->categoryId);

        $this->validator->validate($currentUser, $input, $category);

        $habit = new HabitDataModel();
        $habit->name = $input->name;
        $habit->sourceKind = $input->sourceKind;
        $habit->category = $category;

        // A manual habit keeps its tracker fields null; only a tracker habit names one and a mark.
        if (HabitSourceRegistry::TRACKER === $input->sourceKind) {
            $habit->trackerKind = $input->trackerKind;
            $habit->trackerThreshold = $input->trackerThreshold;
        }

        $this->habitPersisterGateway->create($habit);

        return $this->outputFactory->buildOne($habit);
    }
}
