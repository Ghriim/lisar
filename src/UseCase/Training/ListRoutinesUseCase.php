<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\RoutineSummaryDataOutput;
use App\Domain\Factory\OutputFactory\Training\RoutineSummaryOutputFactory;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Validation\Validator\Training\ListRoutinesValidator;
use App\UseCase\UseCaseInterface;

/** The caller's routines, by name. Not paginated: a person keeps a handful. */
final readonly class ListRoutinesUseCase implements UseCaseInterface
{
    public function __construct(
        private ListRoutinesValidator $validator,
        private RoutineProviderGateway $routineProviderGateway,
        private RoutineSummaryOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<RoutineSummaryDataOutput>
     */
    public function execute(UserDataModel $currentUser): array
    {
        $this->validator->validate($currentUser);

        return $this->outputFactory->buildMany($this->routineProviderGateway->findAllForOwner($currentUser));
    }
}
