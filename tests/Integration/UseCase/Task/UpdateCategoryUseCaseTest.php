<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\UpdateCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\CategoryProviderGateway;
use App\Domain\Validation\Constraint\Task\CategoryEditableConstraint;
use App\Domain\Validation\Constraint\Task\CategoryLabelAvailableConstraint;
use App\Fixtures\CategoryFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Task\UpdateCategoryUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdateCategoryUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private UpdateCategoryUseCase $useCase;
    private CategoryProviderGateway $categoryProviderGateway;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(UpdateCategoryUseCase::class);
        $this->categoryProviderGateway = self::getContainer()->get(CategoryProviderGateway::class);

        $this->loadFixtures(CategoryFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItRenamesTheAccountsOwnCategory(): void
    {
        $category = $this->getReference(CategoryFixtures::ALICE_SIDE_PROJECT, CategoryDataModel::class);

        $output = $this->useCase->execute(
            $this->aliceId(),
            $category->id ?? 0,
            new UpdateCategoryDataInput('Projet perso'),
        );

        self::assertSame('Projet perso', $output->label);
        self::assertTrue($output->isPersonal);

        $reread = $this->categoryProviderGateway->findOneById($category->id ?? 0);
        self::assertNotNull($reread);
        self::assertSame('Projet perso', $reread->label);
    }

    public function testACategoryMayKeepItsOwnLabel(): void
    {
        $category = $this->getReference(CategoryFixtures::ALICE_SIDE_PROJECT, CategoryDataModel::class);

        $output = $this->useCase->execute(
            $this->aliceId(),
            $category->id ?? 0,
            new UpdateCategoryDataInput('Side project'),
        );

        self::assertSame('Side project', $output->label);
    }

    public function testItRefusesToRenameAReferenceCategory(): void
    {
        $home = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);

        try {
            $this->useCase->execute($this->aliceId(), $home->id ?? 0, new UpdateCategoryDataInput('Maison'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(CategoryEditableConstraint::CATEGORY_NOT_EDITABLE, $exception->violations['id']);
        }
    }

    public function testItRefusesALabelAlreadyTaken(): void
    {
        $category = $this->getReference(CategoryFixtures::ALICE_SIDE_PROJECT, CategoryDataModel::class);

        try {
            $this->useCase->execute($this->aliceId(), $category->id ?? 0, new UpdateCategoryDataInput('Home'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                CategoryLabelAvailableConstraint::LABEL_ALREADY_USED,
                $exception->violations['label'],
            );
        }
    }

    public function testAnotherAccountsCategoryIsSimplyNotFound(): void
    {
        $category = $this->getReference(CategoryFixtures::ALICE_SIDE_PROJECT, CategoryDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute($admin->id ?? 0, $category->id ?? 0, new UpdateCategoryDataInput('À moi'));
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
