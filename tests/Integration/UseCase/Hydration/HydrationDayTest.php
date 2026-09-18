<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Hydration;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Hydration\CreateHydrationEntryDataInput;
use App\Domain\DTO\Input\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\HydrationDayProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Hydration\CreateHydrationEntryUseCase;
use App\UseCase\Hydration\DeleteHydrationEntryUseCase;
use App\UseCase\Hydration\GetHydrationDayUseCase;
use App\UseCase\Hydration\UpdateHydrationEntryUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The four ways a day is read and written, together: they share one rule — only the day in
 * progress exists — and one answer, the whole day.
 */
final class HydrationDayTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private GetHydrationDayUseCase $get;
    private CreateHydrationEntryUseCase $create;
    private UpdateHydrationEntryUseCase $update;
    private DeleteHydrationEntryUseCase $delete;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->get = self::getContainer()->get(GetHydrationDayUseCase::class);
        $this->create = self::getContainer()->get(CreateHydrationEntryUseCase::class);
        $this->update = self::getContainer()->get(UpdateHydrationEntryUseCase::class);
        $this->delete = self::getContainer()->get(DeleteHydrationEntryUseCase::class);

        $this->loadFixtures(UserFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    /**
     * Looking at the widget must not create anything: a day exists from the moment something is
     * logged on it.
     */
    public function testItReportsAnEmptyDayWithoutWritingOne(): void
    {
        $output = $this->get->execute($this->aliceId());

        self::assertSame(0, $output->totalInMillilitres);
        self::assertSame(1500, $output->goalInMillilitres);
        self::assertSame([], $output->entries);

        $clock = self::getContainer()->get(DayClock::class);
        $gateway = self::getContainer()->get(HydrationDayProviderGateway::class);
        self::assertNull($gateway->findOneForOwnerAndDay($this->alice, $clock->today()));
    }

    public function testItLogsAndTotals(): void
    {
        $this->create->execute($this->aliceId(), new CreateHydrationEntryDataInput(250));
        $output = $this->create->execute($this->aliceId(), new CreateHydrationEntryDataInput(500));

        self::assertSame(750, $output->totalInMillilitres);
        self::assertCount(2, $output->entries);

        // Re-read through the gateway: assert it was really persisted, on today's day.
        $clock = self::getContainer()->get(DayClock::class);
        $day = self::getContainer()->get(HydrationDayProviderGateway::class)
            ->findOneForOwnerAndDay($this->alice, $clock->today());
        self::assertNotNull($day);
        self::assertSame(750, $day->getTotalInMillilitres());
    }

    /**
     * The goal is written into the day the first time something is logged, so that raising it
     * later cannot turn a day that was reached into a day that was missed.
     */
    public function testTheDayKeepsTheGoalThatAppliedToIt(): void
    {
        $this->create->execute($this->aliceId(), new CreateHydrationEntryDataInput(250));

        $clock = self::getContainer()->get(DayClock::class);
        $day = self::getContainer()->get(HydrationDayProviderGateway::class)
            ->findOneForOwnerAndDay($this->alice, $clock->today());

        self::assertNotNull($day);
        self::assertSame(1500, $day->goalInMillilitres);
    }

    public function testItCorrectsAnEntry(): void
    {
        $created = $this->create->execute($this->aliceId(), new CreateHydrationEntryDataInput(250));
        $entryId = $created->entries[0]->id;

        $output = $this->update->execute($this->aliceId(), $entryId, new UpdateHydrationEntryDataInput(300));

        self::assertSame(300, $output->totalInMillilitres);
    }

    public function testItRemovesAnEntryAndKeepsTheDay(): void
    {
        $created = $this->create->execute($this->aliceId(), new CreateHydrationEntryDataInput(250));

        $output = $this->delete->execute($this->aliceId(), $created->entries[0]->id);

        self::assertSame(0, $output->totalInMillilitres);
        self::assertSame([], $output->entries);

        // The day itself stays: an empty day is a fact too.
        $clock = self::getContainer()->get(DayClock::class);
        $day = self::getContainer()->get(HydrationDayProviderGateway::class)
            ->findOneForOwnerAndDay($this->alice, $clock->today());
        self::assertNotNull($day);
    }

    public function testItRefusesAnImplausibleVolume(): void
    {
        try {
            $this->create->execute($this->aliceId(), new CreateHydrationEntryDataInput(9000));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('volumeInMillilitres', $exception->violations);
        }
    }

    public function testAnotherAccountsEntryIsSimplyNotFound(): void
    {
        $created = $this->create->execute($this->aliceId(), new CreateHydrationEntryDataInput(250));
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->delete->execute($admin->id ?? 0, $created->entries[0]->id);
    }

    public function testTwoAccountsKeepTheirOwnDay(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        $this->create->execute($this->aliceId(), new CreateHydrationEntryDataInput(250));

        self::assertSame(0, $this->get->execute($admin->id ?? 0)->totalInMillilitres);
        self::assertSame(250, $this->get->execute($this->aliceId())->totalInMillilitres);
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
