<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Admin\CreateReferenceCategoryDataInput;
use App\Domain\DTO\Input\Admin\UpdateReferenceCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Task\CategoryLabelAvailableConstraint;
use App\Domain\Validation\Constraint\Task\CategoryUnusedConstraint;
use App\Fixtures\CategoryFixtures;
use App\Fixtures\TaskFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Admin\CreateReferenceCategoryUseCase;
use App\UseCase\Admin\DeleteReferenceCategoryUseCase;
use App\UseCase\Admin\ListReferenceCategoriesUseCase;
use App\UseCase\Admin\UpdateReferenceCategoryUseCase;
use App\UseCase\Task\ListCategoriesUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReferenceCategoryBackOfficeTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private ListReferenceCategoriesUseCase $list;
    private CreateReferenceCategoryUseCase $create;
    private UpdateReferenceCategoryUseCase $update;
    private DeleteReferenceCategoryUseCase $delete;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListReferenceCategoriesUseCase::class);
        $this->create = self::getContainer()->get(CreateReferenceCategoryUseCase::class);
        $this->update = self::getContainer()->get(UpdateReferenceCategoryUseCase::class);
        $this->delete = self::getContainer()->get(DeleteReferenceCategoryUseCase::class);
    }

    /**
     * What a person creates for themselves is theirs: the back-office never sees it.
     */
    public function testItListsTheCommonCategoriesAndNoPersonalOne(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        self::assertSame(['Home', 'Work'], $this->labels());
    }

    public function testItCreatesACategoryEveryoneSees(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        $output = $this->create->execute(new CreateReferenceCategoryDataInput('Santé'));

        self::assertSame('Santé', $output->label);
        self::assertFalse($output->isPersonal);

        // It shows up in what an account may use, without that account doing anything.
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        self::assertContains('Santé', array_map(
            static fn ($category) => $category->label,
            self::getContainer()->get(ListCategoriesUseCase::class)->execute($alice->id ?? 0),
        ));
    }

    public function testItRefusesALabelAnotherCommonCategoryHas(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        try {
            $this->create->execute(new CreateReferenceCategoryDataInput('Home'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                CategoryLabelAvailableConstraint::LABEL_ALREADY_USED,
                $exception->violations['label'],
            );
        }
    }

    /**
     * A personal label is private, so it does not stand in the back-office's way. The website
     * shows the two sets apart, which is what keeps the duplicate readable.
     */
    public function testAPersonalLabelDoesNotBlockACommonOne(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        self::assertSame('Side project', $this->create->execute(
            new CreateReferenceCategoryDataInput('Side project'),
        )->label);
    }

    public function testItRenamesACommonCategoryForEveryone(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        $home = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);

        self::assertSame('Maison', $this->update->execute(
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

        $this->update->execute($personal->id ?? 0, new UpdateReferenceCategoryDataInput('Volée'));
    }

    public function testItDeletesAnEmptyCommonCategory(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        $work = $this->getReference(CategoryFixtures::WORK, CategoryDataModel::class);
        $this->delete->execute($work->id ?? 0);

        self::assertSame(['Home'], $this->labels());
    }

    /**
     * Tasks from any account may sit in a common category: this count is what stands between a
     * click here and someone else's task losing its category.
     */
    public function testItRefusesToDeleteACategoryHoldingSomeonesTasks(): void
    {
        $this->loadFixtures(TaskFixtures::class);

        $home = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);

        try {
            $this->delete->execute($home->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteReferenceCategoryUseCase::ERROR_CODE, $exception->errorCode);
            self::assertContains(CategoryUnusedConstraint::CATEGORY_IN_USE, $exception->violations['id']);
        }
    }

    public function testItFailsOnAnUnknownCategory(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->delete->execute(123456789);
    }

    /** @return list<string> */
    private function labels(): array
    {
        return array_map(static fn ($category) => $category->label, $this->list->execute());
    }
}
