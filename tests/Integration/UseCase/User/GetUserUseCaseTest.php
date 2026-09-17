<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\User;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\User\GetUserUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetUserUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private GetUserUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(GetUserUseCase::class);

        $this->loadFixtures(UserFixtures::class);
    }

    public function testItReturnsTheAccount(): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        self::assertNotNull($alice->id);

        $output = $this->useCase->execute($alice->id);

        self::assertSame($alice->id, $output->id);
        self::assertSame('alice', $output->username);
        self::assertSame('alice@lisar.test', $output->email);
        self::assertTrue($output->isActive);
        self::assertNotNull($output->createdAt);
    }

    public function testItFailsOnAnUnknownAccount(): void
    {
        try {
            $this->useCase->execute(123456789);
            self::fail('Expected DataModelNotFoundException');
        } catch (DataModelNotFoundException $exception) {
            self::assertSame(UserDataModel::class, $exception->dataModelClass);
        }
    }
}
