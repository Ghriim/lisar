<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\MuscleDataOutput;
use App\Domain\Factory\OutputFactory\Training\MuscleOutputFactory;
use App\Domain\Gateway\Persister\Training\MusclePersisterGateway;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
use App\Domain\Validation\Validator\Training\ActivateMuscleValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Offering a retired muscle to new movements again — provided its group is active too.
 */
final readonly class ActivateMuscleUseCase implements UseCaseInterface
{
    public function __construct(
        private ActivateMuscleValidator $validator,
        private MuscleProviderGateway $muscleProviderGateway,
        private MusclePersisterGateway $musclePersisterGateway,
        private MuscleOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(UserDataModel $currentUser, int $id): MuscleDataOutput
    {
        $muscle = $this->muscleProviderGateway->findOneById($id);
        if (null === $muscle) {
            throw new DataModelNotFoundException(MuscleDataModel::class);
        }

        $this->validator->validate($currentUser);

        $muscle->isActive = true;

        $this->musclePersisterGateway->update($muscle);

        return $this->outputFactory->buildOne($muscle);
    }
}
