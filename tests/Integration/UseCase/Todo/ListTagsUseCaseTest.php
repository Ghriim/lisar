<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\CreateTaskDataInput;
use App\Fixtures\Todo\TaskFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\CreateTaskUseCase;
use App\UseCase\Todo\ListTagsUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListTagsUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use SignedInUserTrait;

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
        self::assertSame(['errand', 'urgent'], $this->useCase->execute($this->currentUser($this->aliceId())));
    }

    public function testANewTagShowsUpOnce(): void
    {
        self::getContainer()->get(CreateTaskUseCase::class)->execute(
            $this->currentUser($this->aliceId()),
            new CreateTaskDataInput('Courir', tags: ['sport', 'urgent']),
        );

        self::assertSame(['errand', 'sport', 'urgent'], $this->useCase->execute($this->currentUser($this->aliceId())));
    }

    public function testItNeverLeaksAnotherAccountsTags(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        self::assertSame([], $this->useCase->execute($this->currentUser($admin->id ?? 0)));
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
