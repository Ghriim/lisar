<?php

declare(strict_types=1);

namespace App\UseCase\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\CreateCategoryDataInput;
use App\Domain\DTO\Output\Task\CategoryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\CategoryOutputFactory;
use App\Domain\Gateway\Persister\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\CategoryProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Validation\Validator\Task\CreateCategoryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class CreateCategoryUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateCategoryValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryPersisterGateway $categoryPersisterGateway,
        private CategoryOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, CreateCategoryDataInput $input): CategoryDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $this->validator->validate(
            $input,
            $this->categoryProviderGateway->findOneByLabelForOwner($input->label, null),
            $this->categoryProviderGateway->findOneByLabelForOwner($input->label, $owner),
        );

        $category = new CategoryDataModel();
        $category->label = $input->label;
        $category->owner = $owner;

        $this->categoryPersisterGateway->create($category);

        return $this->outputFactory->buildOne($category);
    }
}
