<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin;

use App\Domain\DTO\DataModel\HydrationPresetDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Hydration\CreateHydrationEntryDataInput;
use App\Domain\DTO\Input\Hydration\CreateHydrationPresetDataInput;
use App\Domain\DTO\Input\Hydration\UpdateHydrationPresetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Hydration\HydrationIconRegistry;
use App\Fixtures\HydrationPresetFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Admin\CreateHydrationPresetUseCase;
use App\UseCase\Admin\DeleteHydrationPresetUseCase;
use App\UseCase\Admin\UpdateHydrationPresetUseCase;
use App\UseCase\Hydration\CreateHydrationEntryUseCase;
use App\UseCase\Hydration\GetHydrationDayUseCase;
use App\UseCase\Hydration\ListHydrationPresetsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class HydrationPresetBackOfficeTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private ListHydrationPresetsUseCase $list;
    private CreateHydrationPresetUseCase $create;
    private UpdateHydrationPresetUseCase $update;
    private DeleteHydrationPresetUseCase $delete;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListHydrationPresetsUseCase::class);
        $this->create = self::getContainer()->get(CreateHydrationPresetUseCase::class);
        $this->update = self::getContainer()->get(UpdateHydrationPresetUseCase::class);
        $this->delete = self::getContainer()->get(DeleteHydrationPresetUseCase::class);
    }

    public function testItListsTheShortcutsSmallestVolumeFirst(): void
    {
        $this->loadFixtures(HydrationPresetFixtures::class);

        self::assertSame([150, 250, 500], array_map(
            static fn ($preset) => $preset->volumeInMillilitres,
            $this->list->execute(),
        ));
    }

    public function testItCreatesAShortcut(): void
    {
        $output = $this->create->execute(
            new CreateHydrationPresetDataInput(HydrationIconRegistry::CARAFE, 1000),
        );

        self::assertSame(HydrationIconRegistry::CARAFE, $output->icon);
        self::assertSame(1000, $output->volumeInMillilitres);
        self::assertCount(1, $this->list->execute());
    }

    public function testItRefusesAnIconTheFrontEndsCouldNotDraw(): void
    {
        try {
            $this->create->execute(new CreateHydrationPresetDataInput('aquarium', 1000));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('icon', $exception->violations);
        }
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
            ->execute($alice->id ?? 0, new CreateHydrationEntryDataInput($glass->volumeInMillilitres));

        $this->update->execute(
            $glass->id ?? 0,
            new UpdateHydrationPresetDataInput(HydrationIconRegistry::GLASS, 200),
        );

        self::assertSame(
            250,
            self::getContainer()->get(GetHydrationDayUseCase::class)->execute($alice->id ?? 0)->totalInMillilitres,
        );
    }

    /**
     * Unlike a priority or a common category, a shortcut can always go: removing it removes a
     * button, and nobody's past changes.
     */
    public function testAShortcutCanBeDeletedEvenAfterBeingUsed(): void
    {
        $this->loadFixtures(HydrationPresetFixtures::class, UserFixtures::class);

        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $glass = $this->getReference(HydrationPresetFixtures::GLASS, HydrationPresetDataModel::class);

        self::getContainer()->get(CreateHydrationEntryUseCase::class)
            ->execute($alice->id ?? 0, new CreateHydrationEntryDataInput($glass->volumeInMillilitres));

        $this->delete->execute($glass->id ?? 0);

        self::assertCount(2, $this->list->execute());
        self::assertSame(
            250,
            self::getContainer()->get(GetHydrationDayUseCase::class)->execute($alice->id ?? 0)->totalInMillilitres,
        );
    }

    public function testItFailsOnAnUnknownShortcut(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->delete->execute(123456789);
    }
}
