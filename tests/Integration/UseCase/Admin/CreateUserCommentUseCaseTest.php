<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Admin\CreateUserCommentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\UserCommentProviderGateway;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Admin\CreateUserCommentUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateUserCommentUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private CreateUserCommentUseCase $useCase;
    private UserCommentProviderGateway $userCommentProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(CreateUserCommentUseCase::class);
        $this->userCommentProviderGateway = self::getContainer()->get(UserCommentProviderGateway::class);

        $this->loadFixtures(UserFixtures::class);
    }

    public function testItAddsANoteAttributedToItsAuthor(): void
    {
        $bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($bob->id);
        self::assertNotNull($admin->id);

        $output = $this->useCase->execute(
            $bob->id,
            $admin->id,
            new CreateUserCommentDataInput('Called support back.'),
        );

        self::assertSame('Called support back.', $output->body);
        self::assertSame($admin->id, $output->authorId);
        self::assertSame('admin', $output->authorUsername);
        self::assertNotNull($output->createdAt);

        // Re-read through the gateway: assert it landed on the right account.
        $comments = $this->userCommentProviderGateway->findAllForUser($bob);
        self::assertCount(1, $comments);
        self::assertSame('Called support back.', $comments[0]->body);
        self::assertSame($admin->id, $comments[0]->author->id);
    }

    public function testItRejectsABlankNote(): void
    {
        $bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($bob->id);
        self::assertNotNull($admin->id);

        try {
            $this->useCase->execute($bob->id, $admin->id, new CreateUserCommentDataInput(''));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('body', $exception->violations);
        }

        self::assertSame([], $this->userCommentProviderGateway->findAllForUser($bob));
    }

    public function testItFailsOnAnUnknownAccount(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($admin->id);

        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute(123456789, $admin->id, new CreateUserCommentDataInput('Called support back.'));
    }
}
