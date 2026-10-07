<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\Output\Todo\PriorityDataOutput;
use App\Domain\Factory\OutputFactory\Todo\PriorityOutputFactory;
use App\Domain\Gateway\Provider\Todo\PriorityProviderGateway;
use App\UseCase\UseCaseInterface;

/**
 * The priorities a person can put on a task. The set is the same for everyone: it is managed in
 * the back-office and nowhere else.
 */
final readonly class ListPrioritiesUseCase implements UseCaseInterface
{
    public function __construct(
        private PriorityProviderGateway $priorityProviderGateway,
        private PriorityOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<PriorityDataOutput>
     */
    public function execute(): array
    {
        return $this->outputFactory->buildMany($this->priorityProviderGateway->findAllOrderedByWeight());
    }
}
