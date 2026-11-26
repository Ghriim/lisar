<?php

declare(strict_types=1);

namespace App\UseCase\Category\Admin;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Category\Admin\CreateReferenceCategoryDataInput;
use App\Domain\DTO\Output\Category\CategoryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Category\CategoryOutputFactory;
use App\Domain\Gateway\Persister\Category\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\Category\CategoryProviderGateway;
use App\Domain\Validation\Validator\Category\Admin\CreateReferenceCategoryValidator;
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
    public function execute(UserDataModel $currentUser, CreateReferenceCategoryDataInput $input): CategoryDataOutput
    {
        $this->validator->validate(
            $currentUser,
            $input,
            $this->categoryProviderGateway->findOneByLabelForOwner($input->label, null),
        );

        $category = new CategoryDataModel();
        $category->label = $input->label;
        $category->icon = $input->icon;
        // No owner: that is what makes it a reference category.
        $category->owner = null;

        $this->categoryPersisterGateway->create($category);

        return $this->outputFactory->buildOne($category);
    }
}
