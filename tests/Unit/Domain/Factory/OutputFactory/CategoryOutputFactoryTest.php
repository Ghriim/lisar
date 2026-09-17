<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Factory\OutputFactory\CategoryOutputFactory;
use LogicException;
use PHPUnit\Framework\TestCase;

final class CategoryOutputFactoryTest extends TestCase
{
    private CategoryOutputFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new CategoryOutputFactory();
    }

    public function testItBuildsAReferenceCategory(): void
    {
        $output = $this->factory->buildOne($this->buildCategory('Home', null));

        self::assertSame('Home', $output->label);
        self::assertFalse($output->isPersonal);
    }

    public function testItBuildsAPersonalCategory(): void
    {
        $output = $this->factory->buildOne($this->buildCategory('Side project', new UserDataModel()));

        self::assertTrue($output->isPersonal);
    }

    public function testItRefusesToBuildFromAnUnsavedCategory(): void
    {
        $category = new CategoryDataModel();
        $category->label = 'Home';

        $this->expectException(LogicException::class);

        $this->factory->buildOne($category);
    }

    public function testItBuildsManyOutputs(): void
    {
        $outputs = $this->factory->buildMany([
            $this->buildCategory('Home', null),
            $this->buildCategory('Work', null, id: 2),
        ]);

        self::assertCount(2, $outputs);
        self::assertSame('Work', $outputs[1]->label);
    }

    private function buildCategory(string $label, ?UserDataModel $owner, int $id = 1): CategoryDataModel
    {
        $category = new CategoryDataModel();
        $category->id = $id;
        $category->label = $label;
        $category->owner = $owner;

        return $category;
    }
}
