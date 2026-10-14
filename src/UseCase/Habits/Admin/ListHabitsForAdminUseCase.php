<?php

declare(strict_types=1);

namespace App\UseCase\Habits\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Habits\HabitAdminDataOutput;
use App\Domain\Factory\OutputFactory\Habits\HabitAdminOutputFactory;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Domain\Validation\Validator\Habits\ListHabitsForAdminValidator;
use App\UseCase\UseCaseInterface;

/** The whole catalogue for the back-office, active and retired alike, newest first. */
final readonly class ListHabitsForAdminUseCase implements UseCaseInterface
{
    public function __construct(
        private ListHabitsForAdminValidator $validator,
        private HabitProviderGateway $habitProviderGateway,
        private HabitAdminOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<HabitAdminDataOutput>
     */
    public function execute(UserDataModel $currentUser, ?bool $isActive = null): array
    {
        $this->validator->validate($currentUser);

        return $this->outputFactory->buildMany($this->habitProviderGateway->findAllForAdminList($isActive));
    }
}
