<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\CategoryDataModel;

interface CategoryPersisterGateway
{
    public function create(CategoryDataModel $category): CategoryDataModel;

    public function update(CategoryDataModel $category): CategoryDataModel;

    public function delete(CategoryDataModel $category): void;
}
