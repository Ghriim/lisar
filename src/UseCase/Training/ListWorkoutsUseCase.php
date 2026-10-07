<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\ListWorkoutsDataInput;
use App\Domain\DTO\Output\PaginatedListDataOutput;
use App\Domain\DTO\Output\Training\WorkoutSummaryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutSummaryOutputFactory;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Validation\Validator\Training\ListWorkoutsValidator;
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
