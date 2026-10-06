<?php

declare(strict_types=1);

namespace App\UseCase\Workout;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Workout\ListWorkoutsDataInput;
use App\Domain\DTO\Output\PaginatedListDataOutput;
use App\Domain\DTO\Output\Workout\WorkoutSummaryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\WorkoutSummaryOutputFactory;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Gateway\Provider\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Workout\ListWorkoutsValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * The workout history: finished workouts only, the latest started first, a page at a time. The
 * one in progress is not history yet.
 */
final readonly class ListWorkoutsUseCase implements UseCaseInterface
{
    public function __construct(
        private ListWorkoutsValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutSummaryOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return PaginatedListDataOutput<WorkoutSummaryDataOutput>
     *
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, ListWorkoutsDataInput $input = new ListWorkoutsDataInput()): PaginatedListDataOutput
    {
        $this->validator->validate($input);

        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        return $this->outputFactory->buildPaginated(
            $this->workoutProviderGateway->findFinishedPageForOwner($owner, $input->getOffset(), $input->perPage),
            $this->workoutProviderGateway->countFinishedForOwner($owner),
            $input->page,
            $input->perPage,
        );
    }
}
