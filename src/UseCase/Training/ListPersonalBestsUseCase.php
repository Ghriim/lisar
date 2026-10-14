<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\PersonalBestBoardDataOutput;
use App\Domain\Factory\OutputFactory\Training\PersonalBestOutputFactory;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Validation\Validator\Training\ListPersonalBestsValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * An account's records: those of whole workouts first, then every movement on offer with its own,
 * or none yet — and a movement retired since, as long as it holds some. Each record comes with its
 * progression.
 */
final readonly class ListPersonalBestsUseCase implements UseCaseInterface
{
    public function __construct(
        private ListPersonalBestsValidator $validator,
        private PersonalBestProviderGateway $personalBestProviderGateway,
        private MovementProviderGateway $movementProviderGateway,
        private PersonalBestOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(UserDataModel $currentUser): PersonalBestBoardDataOutput
    {
        $this->validator->validate($currentUser);

        return $this->outputFactory->buildBoard(
            $this->personalBestProviderGateway->findAllForOwner($currentUser),
            $this->movementProviderGateway->findAllOffered(),
        );
    }
}
