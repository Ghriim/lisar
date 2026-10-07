<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\User\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\User\Admin\CreateUserCommentDataInput;
use App\Fixtures\User\UserCommentFixtures;
use App\Fixtures\User\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\User\Admin\CreateUserCommentUseCase;
use App\UseCase\User\Admin\ListUserCommentsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListUserCommentsUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private ListUserCommentsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(ListUserCommentsUseCase::class);

        // UserFixtures comes along on its own: it is a declared dependency of this one.
        $this->loadFixtures(UserCommentFixtures::class);
    }

    public function testItReturnsTheThreadOfTheAccount(): void
    {
        $bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
        self::assertNotNull($bob->id);

        $comments = $this->useCase->execute($bob->id);

        self::assertCount(1, $comments);
        self::assertSame('admin', $comments[0]->authorUsername);
    }

    public function testItReturnsNothingForAnAccountWithoutNotes(): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        self::assertNotNull($alice->id);

        self::assertSame([], $this->useCase->execute($alice->id));
    }

    public function testItReturnsTheNewestNoteFirst(): void
    {
        $bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($bob->id);
        self::assertNotNull($admin->id);

        self::getContainer()->get(CreateUserCommentUseCase::class)->execute(
            $bob->id,
            $admin->id,
            new CreateUserCommentDataInput('The latest word on this account.'),
        );

        $comments = $this->useCase->execute($bob->id);

        self::assertCount(2, $comments);
        self::assertSame('The latest word on this account.', $comments[0]->body);
    }

    public function testItFailsOnAnUnknownAccount(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute(123456789);
    }
}
