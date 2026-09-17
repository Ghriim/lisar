<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\Input\Admin\CreateReferenceCategoryDataInput;
use App\Domain\DTO\Output\Task\CategoryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\CategoryOutputFactory;
use App\Domain\Gateway\Persister\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\CategoryProviderGateway;
use App\Domain\Validation\Validator\Admin\CreateReferenceCategoryValidator;
use App\UseCase\UseCaseInterface;

final readonly class CreateReferenceCategoryUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateReferenceCategoryValidator $validator,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryPersisterGateway $categoryPersisterGateway,
        private CategoryOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(CreateReferenceCategoryDataInput $input): CategoryDataOutput
    {
        $this->validator->validate(
            $input,
            $this->categoryProviderGateway->findOneByLabelForOwner($input->label, null),
        );

        $category = new CategoryDataModel();
        $category->label = $input->label;
        // No owner: that is what makes it a reference category.
        $category->owner = null;

        $this->categoryPersisterGateway->create($category);

        return $this->outputFactory->buildOne($category);
    }
}
