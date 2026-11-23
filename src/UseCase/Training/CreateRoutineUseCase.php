<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateRoutineDataInput;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\RoutineOutputFactory;
use App\Domain\Gateway\Persister\Training\RoutinePersisterGateway;
use App\Domain\Validation\Validator\Training\CreateRoutineValidator;
use App\UseCase\UseCaseInterface;

/** A new routine of the caller's, empty, named: its blocks, movements and sets are added on its page. */
final readonly class CreateRoutineUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateRoutineValidator $validator,
        private RoutinePersisterGateway $routinePersisterGateway,
        private RoutineOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, CreateRoutineDataInput $input): RoutineDataOutput
    {
        $this->validator->validate($currentUser, $input);

        $routine = new RoutineDataModel();
        $routine->owner = $currentUser;
        $routine->name = $input->name;
        $this->routinePersisterGateway->create($routine);

        // Empty: there is no movement to read an estimated 1RM of.
        return $this->outputFactory->buildOne($routine, []);
    }
}
