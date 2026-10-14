<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\CreateCategoryDataInput;
use App\Domain\DTO\Output\Todo\CategoryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Todo\CategoryOutputFactory;
use App\Domain\Gateway\Persister\Todo\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\Todo\CategoryProviderGateway;
use App\Domain\Validation\Validator\Todo\CreateCategoryValidator;
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
