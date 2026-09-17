<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\CreateCategoryDataInput;
use App\Domain\DTO\Input\Task\CreateTaskDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\CategoryProviderGateway;
use App\Domain\Validation\Constraint\Task\CategoryEditableConstraint;
use App\Domain\Validation\Constraint\Task\CategoryUnusedConstraint;
use App\Fixtures\CategoryFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Task\CreateCategoryUseCase;
use App\UseCase\Task\CreateTaskUseCase;
use App\UseCase\Task\DeleteCategoryUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteCategoryUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private DeleteCategoryUseCase $useCase;
    private CategoryProviderGateway $categoryProviderGateway;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(DeleteCategoryUseCase::class);
        $this->categoryProviderGateway = self::getContainer()->get(CategoryProviderGateway::class);

        $this->loadFixtures(CategoryFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItDeletesAnEmptyPersonalCategory(): void
    {
        $category = self::getContainer()->get(CreateCategoryUseCase::class)
            ->execute($this->aliceId(), new CreateCategoryDataInput('Sport'));

        $this->useCase->execute($this->aliceId(), $category->id);

        self::assertNull($this->categoryProviderGateway->findOneById($category->id));
    }

    public function testItRefusesACategoryATaskStillSitsIn(): void
    {
        $category = self::getContainer()->get(CreateCategoryUseCase::class)
            ->execute($this->aliceId(), new CreateCategoryDataInput('Sport'));

        self::getContainer()->get(CreateTaskUseCase::class)->execute(
            $this->aliceId(),
            new CreateTaskDataInput('Courir', categoryId: $category->id),
        );

        try {
            $this->useCase->execute($this->aliceId(), $category->id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteCategoryUseCase::ERROR_CODE, $exception->errorCode);
            self::assertContains(CategoryUnusedConstraint::CATEGORY_IN_USE, $exception->violations['id']);
        }

        self::assertNotNull($this->categoryProviderGateway->findOneById($category->id));
    }

    public function testItRefusesAReferenceCategory(): void
    {
        $home = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);

        try {
            $this->useCase->execute($this->aliceId(), $home->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(CategoryEditableConstraint::CATEGORY_NOT_EDITABLE, $exception->violations['id']);
        }
    }

    public function testAnotherAccountsCategoryIsSimplyNotFound(): void
    {
        $category = $this->getReference(CategoryFixtures::ALICE_SIDE_PROJECT, CategoryDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute($admin->id ?? 0, $category->id ?? 0);
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
