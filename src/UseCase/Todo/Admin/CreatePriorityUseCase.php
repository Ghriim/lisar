<?php

declare(strict_types=1);

namespace App\UseCase\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\PriorityDataModel;
use App\Domain\DTO\Input\Todo\Admin\CreatePriorityDataInput;
use App\Domain\DTO\Output\Todo\PriorityDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Todo\PriorityOutputFactory;
use App\Domain\Gateway\Persister\Todo\PriorityPersisterGateway;
use App\Domain\Gateway\Provider\Todo\PriorityProviderGateway;
use App\Domain\Validation\Validator\Todo\Admin\CreatePriorityValidator;
use App\UseCase\UseCaseInterface;

final readonly class CreatePriorityUseCase implements UseCaseInterface
{
    public function __construct(
        private CreatePriorityValidator $validator,
        private PriorityProviderGateway $priorityProviderGateway,
        private PriorityPersisterGateway $priorityPersisterGateway,
        private PriorityOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(CreatePriorityDataInput $input): PriorityDataOutput
    {
        $existing = $this->priorityProviderGateway->findAllOrderedByWeight();

        $this->validator->validate($input, $this->findByLabel($existing, $input->label));

        $priority = new PriorityDataModel();
        $priority->label = $input->label;
        $priority->weight = $input->weight;
        $priority->colour = $input->colour;
        // The very first priority is the default whether or not anyone asked: a task created
        // without one has to get something.
        $priority->isDefault = $input->isDefault || [] === $existing;

        if (true === $priority->isDefault) {
            $this->takeTheDefaultFrom($existing);
        }

        $this->priorityPersisterGateway->create($priority);

        return $this->outputFactory->buildOne($priority);
    }

    /**
     * @param list<PriorityDataModel> $priorities
     */
    private function findByLabel(array $priorities, string $label): ?PriorityDataModel
    {
        foreach ($priorities as $priority) {
            if ($label === $priority->label) {
                return $priority;
            }
        }

        return null;
    }

    /**
     * @param list<PriorityDataModel> $priorities
     */
    private function takeTheDefaultFrom(array $priorities): void
    {
        $demoted = [];
        foreach ($priorities as $priority) {
            if (true === $priority->isDefault) {
                $priority->isDefault = false;
                $demoted[] = $priority;
            }
        }

        if ([] !== $demoted) {
            $this->priorityPersisterGateway->updateMany($demoted);
        }
    }
}
