<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;

interface CategoryPersisterGateway
{
    public function create(CategoryDataModel $category): CategoryDataModel;

    public function update(CategoryDataModel $category): CategoryDataModel;

    public function delete(CategoryDataModel $category): void;
}
