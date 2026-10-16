<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateMovementDataInput;
use App\Domain\DTO\Output\Training\MovementDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\MovementOutputFactory;
use App\Domain\Gateway\Persister\Training\MovementPersisterGateway;
use App\Domain\Gateway\Provider\Training\EquipmentProviderGateway;
use App\Domain\Gateway\Provider\Training\MovementFamilyProviderGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
use App\Domain\Validation\Validator\Training\UpdateMovementValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;
use Doctrine\Common\Collections\Collection;

/**
 * Reconfiguring a common movement. What it held and has been retired since may stay on it; what
 * it takes on has to be available.
 */
final readonly class UpdateMovementUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateMovementValidator $validator,
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
    public function execute(UserDataModel $currentUser, int $id, UpdateMovementDataInput $input): MovementDataOutput
    {
        $movement = $this->movementProviderGateway->findOneCommonById($id);
        if (null === $movement) {
            throw new DataModelNotFoundException(MovementDataModel::class);
        }

        // What the movement holds already came with it: only the rows it takes on are read.
        $movementFamily = $input->movementFamilyId === $movement->movementFamily->id
            ? $movement->movementFamily
            : $this->movementFamilyProviderGateway->findOneById($input->movementFamilyId);
        $muscles = $this->rowsAskedFor(
            [$movement->primaryMuscle, ...$movement->secondaryMuscles],
            [$input->primaryMuscleId, ...$input->getSecondaryMuscleIds()],
            $this->muscleProviderGateway->findByIds(...),
        );
        $primaryMuscle = $muscles[$input->primaryMuscleId] ?? null;
        $secondaryMuscles = $this->pick($muscles, $input->getSecondaryMuscleIds());
        $equipments = $this->pick(
            $this->rowsAskedFor($movement->equipments, $input->getEquipmentIds(), $this->equipmentProviderGateway->findByIds(...)),
            $input->getEquipmentIds(),
        );

        $this->validator->validate(
            $currentUser,
            $input,
            $movement,
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

        $movement->name = $input->name;
        $movement->description = $input->getDescription();
        $movement->videoUrl = $input->getVideoUrl();
        $movement->movementFamily = $movementFamily;
        $movement->primaryMuscle = $primaryMuscle;
        // Changed in place, not replaced: Doctrine then writes only the links that moved, where a
        // new collection deletes every link and inserts them all again.
        $this->keepOnly($movement->secondaryMuscles, $secondaryMuscles);
        $this->keepOnly($movement->equipments, $equipments);
        $movement->tracksReps = $input->tracksReps;
        $movement->tracksWeight = $input->tracksWeight;
        $movement->tracksDuration = $input->tracksDuration;
        $movement->tracksDistance = $input->tracksDistance;
        $movement->isUnilateral = $input->isUnilateral;

        $this->movementPersisterGateway->update($movement);

        return $this->outputFactory->buildOne($movement);
    }

    /**
     * The rows asked for, by id: those the movement holds are taken from it, the others read in
     * one query. An id nothing answers is left out, for the validator to refuse.
     *
     * @template T of MuscleDataModel|EquipmentDataModel
     *
     * @param iterable<T>                  $held
     * @param list<int>                    $ids
     * @param callable(list<int>): list<T> $read
     *
     * @return array<int, T>
     */
    private function rowsAskedFor(iterable $held, array $ids, callable $read): array
    {
        $rows = [];
        foreach ($held as $row) {
            $rows[(int) $row->id] = $row;
        }

        $missing = array_values(array_diff($ids, array_keys($rows)));
        if ([] !== $missing) {
            foreach ($read($missing) as $row) {
                $rows[(int) $row->id] = $row;
            }
        }

        return $rows;
    }

    /**
     * Those of `$ids` that `$rows` answers, in the order asked.
     *
     * @template T of MuscleDataModel|EquipmentDataModel
     *
     * @param array<int, T> $rows
     * @param list<int>     $ids
     *
     * @return list<T>
     */
    private function pick(array $rows, array $ids): array
    {
        $picked = [];
        foreach ($ids as $id) {
            if (true === isset($rows[$id])) {
                $picked[] = $rows[$id];
            }
        }

        return $picked;
    }

    /**
     * @template T of MuscleDataModel|EquipmentDataModel
     *
     * @param Collection<int, T> $collection
     * @param list<T>            $wanted
     */
    private function keepOnly(Collection $collection, array $wanted): void
    {
        foreach ($collection->toArray() as $row) {
            if (false === in_array($row, $wanted, true)) {
                $collection->removeElement($row);
            }
        }

        foreach ($wanted as $row) {
            if (false === $collection->contains($row)) {
                $collection->add($row);
            }
        }
    }
}
