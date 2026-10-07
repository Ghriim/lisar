<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\Input\Training\CreateMovementDataInput;
use App\Domain\DTO\Output\Training\MovementDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\MovementOutputFactory;
use App\Domain\Gateway\Persister\Training\MovementPersisterGateway;
use App\Domain\Gateway\Provider\Training\EquipmentProviderGateway;
use App\Domain\Gateway\Provider\Training\MovementFamilyProviderGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
use App\Domain\Validation\Validator\Training\CreateMovementValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Adding a common movement, offered to everyone at once.
 */
final readonly class CreateMovementUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateMovementValidator $validator,
        private MovementProviderGateway $movementProviderGateway,
        private MovementFamilyProviderGateway $movementFamilyProviderGateway,
        private MuscleProviderGateway $muscleProviderGateway,
        private EquipmentProviderGateway $equipmentProviderGateway,
        private MovementPersisterGateway $movementPersisterGateway,
        private MovementOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(CreateMovementDataInput $input): MovementDataOutput
    {
        $movementFamily = $this->movementFamilyProviderGateway->findOneById($input->movementFamilyId);
        $primaryMuscle = $this->muscleProviderGateway->findOneById($input->primaryMuscleId);
        $secondaryMuscles = $this->muscleProviderGateway->findByIds($input->getSecondaryMuscleIds());
        $equipments = $this->equipmentProviderGateway->findByIds($input->getEquipmentIds());

        $this->validator->validate(
            $input,
            $this->movementProviderGateway->findOneCommonByName($input->name),
            $movementFamily,
            $primaryMuscle,
            $secondaryMuscles,
            $equipments,
        );

        // Unreachable — the validator has just refused a missing family or muscle — but it types
        // what follows.
        if (null === $movementFamily) {
            throw new DataModelNotFoundException(MovementFamilyDataModel::class);
        }
        if (null === $primaryMuscle) {
            throw new DataModelNotFoundException(MuscleDataModel::class);
        }

        $movement = new MovementDataModel();
        // No owner: that is what makes it a common movement.
        $movement->owner = null;
        $movement->name = $input->name;
        $movement->description = $input->getDescription();
        $movement->videoUrl = $input->getVideoUrl();
        $movement->movementFamily = $movementFamily;
        $movement->primaryMuscle = $primaryMuscle;
        $movement->secondaryMuscles = new ArrayCollection($secondaryMuscles);
        $movement->equipments = new ArrayCollection($equipments);
        $movement->tracksReps = $input->tracksReps;
        $movement->tracksWeight = $input->tracksWeight;
        $movement->tracksDuration = $input->tracksDuration;
        $movement->tracksDistance = $input->tracksDistance;
        $movement->isUnilateral = $input->isUnilateral;

        $this->movementPersisterGateway->create($movement);

        return $this->outputFactory->buildOne($movement);
    }
}
