<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Task;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\CreateTaskDataInput;
use App\Fixtures\TaskFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Task\CreateTaskUseCase;
use App\UseCase\Task\ListTagsUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListTagsUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private ListTagsUseCase $useCase;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(ListTagsUseCase::class);

        $this->loadFixtures(TaskFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItReturnsTheAccountsLabelsAlphabetically(): void
    {
        self::assertSame(['errand', 'urgent'], $this->useCase->execute($this->aliceId()));
    }

    public function testANewTagShowsUpOnce(): void
    {
        self::getContainer()->get(CreateTaskUseCase::class)->execute(
            $this->aliceId(),
            new CreateTaskDataInput('Courir', tags: ['sport', 'urgent']),
        );

        self::assertSame(['errand', 'sport', 'urgent'], $this->useCase->execute($this->aliceId()));
    }

    public function testItNeverLeaksAnotherAccountsTags(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        self::assertSame([], $this->useCase->execute($admin->id ?? 0));
    }

    public function testItFailsOnAnUnknownAccount(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute(123456789);
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
