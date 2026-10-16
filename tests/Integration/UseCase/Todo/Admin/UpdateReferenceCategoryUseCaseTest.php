<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\Admin\UpdateReferenceCategoryDataInput;
use App\Fixtures\Todo\CategoryFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\Admin\UpdateReferenceCategoryUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdateReferenceCategoryUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use ReferenceCategoryTestTrait;
    use SignedInUserTrait;

    private UpdateReferenceCategoryUseCase $update;

    protected function setUp(): void
    {
        parent::setUp();

        $this->update = self::getContainer()->get(UpdateReferenceCategoryUseCase::class);
    }

    public function testItRenamesACommonCategoryForEveryone(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        $home = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);

        self::assertSame('Maison', $this->update->execute(
            $this->currentAdmin(),
            $home->id ?? 0,
            new UpdateReferenceCategoryDataInput('Maison'),
        )->label);
        self::assertSame(['Maison', 'Work'], $this->labels());
    }

    public function testAPersonalCategoryIsNotPartOfThisSurface(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        $personal = $this->getReference(CategoryFixtures::ALICE_SIDE_PROJECT, CategoryDataModel::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->update->execute($this->currentAdmin(), $personal->id ?? 0, new UpdateReferenceCategoryDataInput('Volée'));
    }

    public function testRenamingACommonCategoryStaysWithinItsQueryBudget(): void
    {
        $this->loadFixtures(CategoryFixtures::class);
        $homeId = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentAdmin) => $this->update->execute($currentAdmin, $homeId, new UpdateReferenceCategoryDataInput('Maison')),
        );
    }
}
