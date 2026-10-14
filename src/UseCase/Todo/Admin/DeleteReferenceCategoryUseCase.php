<?php

declare(strict_types=1);

namespace App\UseCase\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Todo\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\Todo\CategoryProviderGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Validation\Validator\Todo\Admin\DeleteReferenceCategoryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class DeleteReferenceCategoryUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteReferenceCategoryValidator $validator,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryPersisterGateway $categoryPersisterGateway,
        private TaskProviderGateway $taskProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $id): void
    {
        $category = $this->categoryProviderGateway->findOneById($id);

        if (null === $category || true === $category->isPersonal()) {
            throw new DataModelNotFoundException(CategoryDataModel::class);
        }

        // Tasks across every account may sit in a common category, so this count is the only
        // thing standing between a click here and someone else's task losing its category.
        $this->validator->validate($currentUser, $this->taskProviderGateway->countForCategory($category));

        $this->categoryPersisterGateway->delete($category);
    }
}
