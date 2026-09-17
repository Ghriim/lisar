<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\Output\Task\CategoryDataOutput;
use App\Domain\Factory\OutputFactory\CategoryOutputFactory;
use App\Domain\Gateway\Provider\CategoryProviderGateway;
use App\UseCase\UseCaseInterface;

/**
 * The common categories, and only those: what people create for themselves is theirs, and the
 * back-office never sees it.
 */
final readonly class ListReferenceCategoriesUseCase implements UseCaseInterface
{
    public function __construct(
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<CategoryDataOutput>
     */
    public function execute(): array
    {
        return $this->outputFactory->buildMany($this->categoryProviderGateway->findAllReference());
    }
}
