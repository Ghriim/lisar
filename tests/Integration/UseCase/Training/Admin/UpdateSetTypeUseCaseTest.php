<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateSetTypeDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Registry\Training\SetTypeColourRegistry;
use App\Domain\Validation\Constraint\Training\DefaultSetTypeKeptConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\UpdateSetTypeValidator;
use App\Fixtures\Training\SetTypeFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\DeactivateSetTypeUseCase;
use App\UseCase\Training\Admin\UpdateSetTypeUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Changing a set type: its name, its colour, whether it is the default and whether it counts for personal bests. */
final class UpdateSetTypeUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SetTypeTestTrait;
    use SignedInUserTrait;

    private UpdateSetTypeUseCase $update;
    private DeactivateSetTypeUseCase $deactivate;
    private SetTypeProviderGateway $setTypeProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->update = self::getContainer()->get(UpdateSetTypeUseCase::class);
        $this->deactivate = self::getContainer()->get(DeactivateSetTypeUseCase::class);
        $this->setTypeProviderGateway = self::getContainer()->get(SetTypeProviderGateway::class);

        $this->loadFixtures(SetTypeFixtures::class);
    }

    public function testItUpdatesASetType(): void
    {
        $output = $this->update->execute($this->currentAdmin(), $this->dropset()->id ?? 0, new UpdateSetTypeDataInput('Drop set', SetTypeColourRegistry::PINK));

        self::assertSame('Drop set', $output->name);
        self::assertSame(SetTypeColourRegistry::PINK, $output->colour);

        $reread = $this->setTypeProviderGateway->findOneById($output->id);
        self::assertNotNull($reread);
        self::assertSame('Drop set', $reread->name);
        self::assertSame(SetTypeColourRegistry::PINK, $reread->colour);
    }

    /** A set type keeps its own name, and may change only its case. */
    public function testASetTypeMayKeepItsOwnName(): void
    {
        $output = $this->update->execute($this->currentAdmin(), $this->dropset()->id ?? 0, new UpdateSetTypeDataInput('DROPSET', SetTypeColourRegistry::PURPLE));

        self::assertSame('DROPSET', $output->name);
    }

    public function testItRefusesToRenameOntoAnotherSetType(): void
    {
        try {
            $this->update->execute($this->currentAdmin(), $this->dropset()->id ?? 0, new UpdateSetTypeDataInput('Échec', SetTypeColourRegistry::PURPLE));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(SetTypeNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testAnUpdateGivesTheDefaultToAnotherSetType(): void
    {
        $id = $this->dropset()->id ?? 0;

        $output = $this->update->execute($this->currentAdmin(), $id, new UpdateSetTypeDataInput('Dropset', SetTypeColourRegistry::PURPLE, isDefaultType: true));

        self::assertTrue($output->isDefaultType);
        self::assertSame($id, $this->setTypeProviderGateway->findOneDefault()?->id);
        self::assertFalse($this->setTypeProviderGateway->findOneById($this->working()->id ?? 0)?->isDefaultType);
    }

    /** The default is never unset, only given away: a set logged without a type has to get one. */
    public function testItRefusesToUnsetTheDefault(): void
    {
        try {
            $this->update->execute($this->currentAdmin(), $this->working()->id ?? 0, new UpdateSetTypeDataInput('Travail', SetTypeColourRegistry::BLUE));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([DefaultSetTypeKeptConstraint::DEFAULT_REQUIRED], $exception->violations['isDefaultType']);
        }

        self::assertTrue($this->setTypeProviderGateway->findOneById($this->working()->id ?? 0)?->isDefaultType);
    }

    public function testItRefusesToGiveTheDefaultToARetiredSetType(): void
    {
        $id = $this->dropset()->id ?? 0;
        $this->deactivate->execute($this->currentAdmin(), $id);

        try {
            $this->update->execute($this->currentAdmin(), $id, new UpdateSetTypeDataInput('Dropset', SetTypeColourRegistry::PURPLE, isDefaultType: true));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([DefaultSetTypeKeptConstraint::DEFAULT_INACTIVE], $exception->violations['isDefaultType']);
        }
    }

    public function testItFailsOnAnUnknownSetType(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->update->execute($this->currentAdmin(), 123456789, new UpdateSetTypeDataInput('Ghost', SetTypeColourRegistry::GREY));
    }

    public function testUpdatingASetTypeStaysWithinItsQueryBudget(): void
    {
        $id = $this->dropset()->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->update->execute($currentUser, $id, new UpdateSetTypeDataInput('Drop set', SetTypeColourRegistry::PINK)),
        );
    }
}
