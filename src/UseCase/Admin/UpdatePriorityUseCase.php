<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\DTO\Input\Admin\UpdatePriorityDataInput;
use App\Domain\DTO\Output\Task\PriorityDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\PriorityOutputFactory;
use App\Domain\Gateway\Persister\PriorityPersisterGateway;
use App\Domain\Gateway\Provider\PriorityProviderGateway;
use App\Domain\Validation\Validator\Admin\UpdatePriorityValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class UpdatePriorityUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdatePriorityValidator $validator,
        private PriorityProviderGateway $priorityProviderGateway,
        private PriorityPersisterGateway $priorityPersisterGateway,
        private PriorityOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id, UpdatePriorityDataInput $input): PriorityDataOutput
    {
        $priority = $this->priorityProviderGateway->findOneById($id);
        if (null === $priority) {
            throw new DataModelNotFoundException(PriorityDataModel::class);
        }

        $others = [];
        $withSameLabel = null;
        foreach ($this->priorityProviderGateway->findAllOrderedByWeight() as $candidate) {
            if ($candidate->id !== $priority->id) {
                $others[] = $candidate;
            }
            if ($input->label === $candidate->label) {
                $withSameLabel = $candidate;
            }
        }

        $this->validator->validate($input, $priority, $withSameLabel);

        $priority->label = $input->label;
        $priority->weight = $input->weight;
        $priority->colour = $input->colour;

        // Becoming the default takes it from whoever had it: there is exactly one.
        if (true === $input->isDefault && false === $priority->isDefault) {
            $demoted = [];
            foreach ($others as $other) {
                if (true === $other->isDefault) {
                    $other->isDefault = false;
                    $demoted[] = $other;
                }
            }

            if ([] !== $demoted) {
                $this->priorityPersisterGateway->updateMany($demoted);
            }

            $priority->isDefault = true;
        }

        $this->priorityPersisterGateway->update($priority);

        return $this->outputFactory->buildOne($priority);
    }
}
