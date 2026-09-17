<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Persister\CategoryPersisterGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class CategoryFixtures extends Fixture implements DependentFixtureInterface
{
    public const string HOME = 'category-home';
    public const string WORK = 'category-work';
    public const string ALICE_SIDE_PROJECT = 'category-alice-side-project';

    public function __construct(private readonly CategoryPersisterGateway $categoryPersisterGateway)
    {
    }

    /**
     * @return list<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        // Reference categories: no owner, everyone sees them.
        $this->addReference(self::HOME, $this->createCategory('Home', null));
        $this->addReference(self::WORK, $this->createCategory('Work', null));

        // And one personal category, to prove the two live side by side.
        $this->addReference(self::ALICE_SIDE_PROJECT, $this->createCategory(
            'Side project',
            $this->getReference(UserFixtures::ALICE, UserDataModel::class),
        ));
    }

    private function createCategory(string $label, ?UserDataModel $owner): CategoryDataModel
    {
        $category = new CategoryDataModel();
        $category->label = $label;
        $category->owner = $owner;

        return $this->categoryPersisterGateway->create($category);
    }
}
