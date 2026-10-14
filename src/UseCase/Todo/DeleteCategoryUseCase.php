<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Todo\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\Todo\CategoryProviderGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Validation\Validator\Todo\DeleteCategoryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class DeleteCategoryUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteCategoryValidator $validator,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryPersisterGateway $categoryPersisterGateway,
        private TaskProviderGateway $taskProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $categoryId): void
    {
        $category = $this->categoryProviderGateway->findOneById($categoryId);

        if (null === $category || false === $category->isUsableBy($currentUser)) {
            throw new DataModelNotFoundException(CategoryDataModel::class);
        }

        $this->validator->validate($currentUser, $category, $this->taskProviderGateway->countForCategory($category));

        $this->categoryPersisterGateway->delete($category);
    }
}
