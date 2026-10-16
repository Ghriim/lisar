<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Hydration\Admin;

use App\Domain\DTO\DataModel\Tracking\HydrationPresetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationEntryDataInput;
use App\Fixtures\Tracking\Hydration\HydrationPresetFixtures;
use App\Fixtures\User\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Tracking\Hydration\Admin\DeleteHydrationPresetUseCase;
use App\UseCase\Tracking\Hydration\CreateHydrationEntryUseCase;
use App\UseCase\Tracking\Hydration\GetHydrationDayUseCase;
use App\UseCase\Tracking\Hydration\ListHydrationPresetsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Removing a drink shortcut: a button goes, nobody's past changes. */
final class DeleteHydrationPresetUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private ListHydrationPresetsUseCase $list;
    private DeleteHydrationPresetUseCase $delete;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListHydrationPresetsUseCase::class);
        $this->delete = self::getContainer()->get(DeleteHydrationPresetUseCase::class);
    }

    /**
     * Unlike a common category, a shortcut can always go: removing it removes a
     * button, and nobody's past changes.
     */
    public function testAShortcutCanBeDeletedEvenAfterBeingUsed(): void
    {
        $this->loadFixtures(HydrationPresetFixtures::class, UserFixtures::class);

        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $glass = $this->getReference(HydrationPresetFixtures::GLASS, HydrationPresetDataModel::class);

        self::getContainer()->get(CreateHydrationEntryUseCase::class)
            ->execute($this->currentUser($alice->id ?? 0), new CreateHydrationEntryDataInput($glass->volumeInMillilitres));

        $this->delete->execute($this->currentAdmin(), $glass->id ?? 0);

        self::assertCount(2, $this->list->execute($this->anyCurrentUser()));
        self::assertSame(
            250,
            self::getContainer()->get(GetHydrationDayUseCase::class)->execute($this->currentUser($alice->id ?? 0))->totalInMillilitres,
        );
    }

    public function testItFailsOnAnUnknownShortcut(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->delete->execute($this->currentAdmin(), 123456789);
    }

    public function testDeletingAShortcutStaysWithinItsQueryBudget(): void
    {
        $this->loadFixtures(HydrationPresetFixtures::class);
        $glassId = $this->getReference(HydrationPresetFixtures::GLASS, HydrationPresetDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->delete->execute($currentUser, $glassId),
        );
    }
}
