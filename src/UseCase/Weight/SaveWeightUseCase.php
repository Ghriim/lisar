<?php

declare(strict_types=1);

namespace App\UseCase\Weight;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\WeightEntryDataModel;
use App\Domain\DTO\Input\Weight\SaveWeightDataInput;
use App\Domain\DTO\Output\Weight\WeightDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\WeightOutputFactory;
use App\Domain\Gateway\Persister\WeightEntryPersisterGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Gateway\Provider\WeightEntryProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Weight\SaveWeightValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

use function round;

/**
 * Writing today's weight. One use case, not a create and an update, because from the person's
 * side there is one gesture: they step on the scale and record what it says. There is one weight
 * per day, so doing it twice is correcting the first — and a single idempotent write cannot be
 * raced into two rows the way a create-then-update pair can.
 *
 * The day is never named by the caller. Whatever day a long-open page thinks it is, this writes
 * to the one in progress.
 */
final readonly class SaveWeightUseCase implements UseCaseInterface
{
    public function __construct(
        private SaveWeightValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private WeightEntryProviderGateway $weightEntryProviderGateway,
        private WeightEntryPersisterGateway $weightEntryPersisterGateway,
        private WeightOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, SaveWeightDataInput $input): WeightDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $this->validator->validate($input);

        $today = $this->clock->today();
        $weightInKilograms = round($input->weightInKilograms, SaveWeightDataInput::DECIMALS);

        $entry = $this->weightEntryProviderGateway->findOneForOwnerAndDay($owner, $today);

        if (null === $entry) {
            $entry = new WeightEntryDataModel();
            $entry->owner = $owner;
            $entry->day = $today;
            // Set once, here: a correction is not a new measurement, so it must not move the
            // moment the person stepped on the scale.
            $entry->recordedAt = $this->clock->now();
            $entry->weightInKilograms = $weightInKilograms;

            $this->weightEntryPersisterGateway->create($entry);

            return $this->outputFactory->buildOne($entry);
        }

        $entry->weightInKilograms = $weightInKilograms;

        $this->weightEntryPersisterGateway->update($entry);

        return $this->outputFactory->buildOne($entry);
    }
}
