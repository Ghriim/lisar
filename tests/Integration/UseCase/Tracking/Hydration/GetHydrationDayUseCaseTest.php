<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Hydration;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationEntryDataInput;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDayProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Tracking\Hydration\CreateHydrationEntryUseCase;
use App\UseCase\Tracking\Hydration\GetHydrationDayUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Reading the day in progress, the whole day: its total, its goal and its drinks. Only the day in
 * progress exists, and only once something is logged on it.
 */
final class GetHydrationDayUseCaseTest extends KernelTestCase
{
    use HydrationTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private GetHydrationDayUseCase $get;
    private CreateHydrationEntryUseCase $create;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->get = self::getContainer()->get(GetHydrationDayUseCase::class);
        $this->create = self::getContainer()->get(CreateHydrationEntryUseCase::class);

        $this->loadFixtures(UserFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    /**
     * Looking at the widget must not create anything: a day exists from the moment something is
     * logged on it.
     */
    public function testItReportsAnEmptyDayWithoutWritingOne(): void
    {
        $output = $this->get->execute($this->currentUser($this->aliceId()));

        self::assertSame(0, $output->totalInMillilitres);
        self::assertSame(1500, $output->goalInMillilitres);
        self::assertSame([], $output->entries);

        $clock = self::getContainer()->get(DayClock::class);
        $gateway = self::getContainer()->get(HydrationDayProviderGateway::class);
        self::assertNull($gateway->findOneForOwnerAndDay($this->alice, $clock->today()));
    }

    public function testTwoAccountsKeepTheirOwnDay(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        $this->create->execute($this->currentUser($this->aliceId()), new CreateHydrationEntryDataInput(250));

        self::assertSame(0, $this->get->execute($this->currentUser($admin->id ?? 0))->totalInMillilitres);
        self::assertSame(250, $this->get->execute($this->currentUser($this->aliceId()))->totalInMillilitres);
    }

    public function testReadingTheDayStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();
        $this->create->execute($this->currentUser($aliceId), new CreateHydrationEntryDataInput(250));
        $this->create->execute($this->currentUser($aliceId), new CreateHydrationEntryDataInput(500));

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->get->execute($currentUser),
        );
    }
}
