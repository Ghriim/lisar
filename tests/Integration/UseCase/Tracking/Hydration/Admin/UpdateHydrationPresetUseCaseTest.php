<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Hydration\Admin;

use App\Domain\DTO\DataModel\Tracking\HydrationPresetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationEntryDataInput;
use App\Domain\DTO\Input\Tracking\Hydration\UpdateHydrationPresetDataInput;
use App\Domain\Registry\Tracking\Hydration\HydrationIconRegistry;
use App\Fixtures\Tracking\Hydration\HydrationPresetFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Tracking\Hydration\Admin\UpdateHydrationPresetUseCase;
use App\UseCase\Tracking\Hydration\CreateHydrationEntryUseCase;
use App\UseCase\Tracking\Hydration\GetHydrationDayUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Correcting a drink shortcut: what the next tap logs, never what was logged before. */
final class UpdateHydrationPresetUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private UpdateHydrationPresetUseCase $update;

    protected function setUp(): void
    {
        parent::setUp();

        $this->update = self::getContainer()->get(UpdateHydrationPresetUseCase::class);
    }

    /**
     * Correcting a shortcut changes what the next tap logs, and nothing about what was logged
     * before: an entry copied the volume rather than pointing here.
     */
    public function testCorrectingAShortcutLeavesWhatWasLoggedAlone(): void
    {
        $this->loadFixtures(HydrationPresetFixtures::class, UserFixtures::class);

        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $glass = $this->getReference(HydrationPresetFixtures::GLASS, HydrationPresetDataModel::class);

        self::getContainer()->get(CreateHydrationEntryUseCase::class)
            ->execute($this->currentUser($alice->id ?? 0), new CreateHydrationEntryDataInput($glass->volumeInMillilitres));

        $this->update->execute(
            $this->currentAdmin(),
            $glass->id ?? 0,
            new UpdateHydrationPresetDataInput(HydrationIconRegistry::GLASS, 200),
        );

        self::assertSame(
            250,
            self::getContainer()->get(GetHydrationDayUseCase::class)->execute($this->currentUser($alice->id ?? 0))->totalInMillilitres,
        );
    }

    public function testCorrectingAShortcutStaysWithinItsQueryBudget(): void
    {
        $this->loadFixtures(HydrationPresetFixtures::class);
        $glassId = $this->getReference(HydrationPresetFixtures::GLASS, HydrationPresetDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->update->execute($currentUser, $glassId, new UpdateHydrationPresetDataInput(HydrationIconRegistry::GLASS, 200)),
        );
    }
}
