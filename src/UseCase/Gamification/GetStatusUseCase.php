<?php

declare(strict_types=1);

namespace App\UseCase\Gamification;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Gamification\StatusDataOutput;
use App\Domain\Factory\OutputFactory\Gamification\StatusOutputFactory;
use App\Domain\Gateway\Provider\Gamification\ExperienceGainProviderGateway;
use App\Domain\Validation\Validator\Gamification\GetStatusValidator;
use App\UseCase\UseCaseInterface;

/** The caller's status: each stat's level, and the global level worked out on all their experience. */
final readonly class GetStatusUseCase implements UseCaseInterface
{
    public function __construct(
        private GetStatusValidator $validator,
        private ExperienceGainProviderGateway $experienceGainProviderGateway,
        private StatusOutputFactory $outputFactory,
    ) {
    }

    public function execute(UserDataModel $currentUser): StatusDataOutput
    {
        $this->validator->validate($currentUser);

        return $this->outputFactory->buildOne($this->experienceGainProviderGateway->sumByStatForOwner($currentUser));
    }
}
