<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Category;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\Gateway\Persister\Category\CategoryPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<CategoryDataModel>
 */
final class CategoryPersister extends AbstractBaseMysqlPersister implements CategoryPersisterGateway
{
    public function create(CategoryDataModel $category): CategoryDataModel
    {
        return $this->persistCreate($category);
    }

    public function update(CategoryDataModel $category): CategoryDataModel
    {
        return $this->persistUpdate($category);
    }

    public function delete(CategoryDataModel $category): void
    {
        $this->persistDelete($category);
    }
}
