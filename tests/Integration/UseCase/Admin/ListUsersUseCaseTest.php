<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin;

use App\Domain\DTO\Input\Admin\ListUsersDataInput;
use App\Domain\Exception\ValidationException;
use App\Fixtures\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Admin\ListUsersUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListUsersUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private ListUsersUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(ListUsersUseCase::class);

        $this->loadFixtures(UserFixtures::class);
    }

    public function testItListsEveryAccountNewestFirst(): void
    {
        $output = $this->useCase->execute(new ListUsersDataInput());

        self::assertSame(3, $output->total);
        self::assertCount(3, $output->items);
        self::assertSame(['admin', 'bob', 'alice'], array_map(
            static fn ($user) => $user->username,
            $output->items,
        ));
    }

    public function testItSearchesTheUsernameAndTheEmail(): void
    {
        self::assertSame(1, $this->useCase->execute(new ListUsersDataInput(search: 'bob'))->total);
        self::assertSame(1, $this->useCase->execute(new ListUsersDataInput(search: 'alice@lisar'))->total);
        self::assertSame(3, $this->useCase->execute(new ListUsersDataInput(search: 'lisar.test'))->total);
        self::assertSame(0, $this->useCase->execute(new ListUsersDataInput(search: 'nobody'))->total);
    }

    public function testItFiltersOnTheStatus(): void
    {
        $deactivated = $this->useCase->execute(new ListUsersDataInput(isActive: false));

        self::assertSame(1, $deactivated->total);
        self::assertSame('bob', $deactivated->items[0]->username);

        self::assertSame(2, $this->useCase->execute(new ListUsersDataInput(isActive: true))->total);
    }

    public function testItPaginatesWhileReportingTheWholeCount(): void
    {
        $firstPage = $this->useCase->execute(new ListUsersDataInput(page: 1, perPage: 2));

        self::assertCount(2, $firstPage->items);
        self::assertSame(3, $firstPage->total);
        self::assertSame(1, $firstPage->page);
        self::assertSame(2, $firstPage->perPage);

        $secondPage = $this->useCase->execute(new ListUsersDataInput(page: 2, perPage: 2));

        self::assertCount(1, $secondPage->items);
        self::assertSame(3, $secondPage->total);
        self::assertSame('alice', $secondPage->items[0]->username);
    }

    public function testItReturnsAnEmptyPageBeyondTheLastOne(): void
    {
        $output = $this->useCase->execute(new ListUsersDataInput(page: 99));

        self::assertSame([], $output->items);
        self::assertSame(3, $output->total);
    }

    public function testItRejectsAnInvalidPageSize(): void
    {
        try {
            $this->useCase->execute(new ListUsersDataInput(perPage: 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('perPage', $exception->violations);
        }
    }
}
