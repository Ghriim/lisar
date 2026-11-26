<?php

declare(strict_types=1);

namespace App\UseCase\Category;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Category\CreateCategoryDataInput;
use App\Domain\DTO\Output\Category\CategoryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Category\CategoryOutputFactory;
use App\Domain\Gateway\Persister\Category\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\Category\CategoryProviderGateway;
use App\Domain\Validation\Validator\Category\CreateCategoryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class CreateCategoryUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateCategoryValidator $validator,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryPersisterGateway $categoryPersisterGateway,
        private CategoryOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, CreateCategoryDataInput $input): CategoryDataOutput
    {
        $this->validator->validate(
            $currentUser,
            $input,
            $this->categoryProviderGateway->findOneByLabelForOwner($input->label, null),
            $this->categoryProviderGateway->findOneByLabelForOwner($input->label, $currentUser),
        );

        $category = new CategoryDataModel();
        $category->label = $input->label;
        $category->owner = $currentUser;

        $this->categoryPersisterGateway->create($category);

        return $this->outputFactory->buildOne($category);
    }
}
