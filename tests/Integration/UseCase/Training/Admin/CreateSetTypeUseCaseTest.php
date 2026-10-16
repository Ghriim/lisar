<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateSetTypeDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Registry\Training\SetTypeColourRegistry;
use App\Domain\Validation\Constraint\Training\SetTypeNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\CreateSetTypeValidator;
use App\Fixtures\Training\SetTypeFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateSetTypeUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Creating a set type, under a name no other set type has, possibly as the default. */
final class CreateSetTypeUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SetTypeTestTrait;
    use SignedInUserTrait;

    private CreateSetTypeUseCase $create;
    private SetTypeProviderGateway $setTypeProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->create = self::getContainer()->get(CreateSetTypeUseCase::class);
        $this->setTypeProviderGateway = self::getContainer()->get(SetTypeProviderGateway::class);

        $this->loadFixtures(SetTypeFixtures::class);
    }

    public function testItCreatesASetType(): void
    {
        $output = $this->create->execute($this->currentAdmin(), new CreateSetTypeDataInput('Rest-pause', SetTypeColourRegistry::TEAL));

        self::assertSame('Rest-pause', $output->name);
        self::assertSame(SetTypeColourRegistry::TEAL, $output->colour);
        self::assertTrue($output->isActive);
        self::assertFalse($output->isDefaultType);

        $setType = $this->setTypeProviderGateway->findOneById($output->id);
        self::assertNotNull($setType);
        self::assertSame(SetTypeColourRegistry::TEAL, $setType->colour);
    }

    /** "Dropset" and "dropset" are one name: the collation compares ignoring case. */
    public function testItRefusesANameAlreadyTakenIgnoringCase(): void
    {
        try {
            $this->create->execute($this->currentAdmin(), new CreateSetTypeDataInput('dropset', SetTypeColourRegistry::PINK));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(SetTypeNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    /** Exactly one default: a type created as the default takes it from the one that had it. */
    public function testASetTypeCreatedAsTheDefaultTakesIt(): void
    {
        $output = $this->create->execute($this->currentAdmin(), new CreateSetTypeDataInput('Rest-pause', SetTypeColourRegistry::TEAL, isDefaultType: true));

        self::assertTrue($output->isDefaultType);
        self::assertSame($output->id, $this->setTypeProviderGateway->findOneDefault()?->id);
        self::assertFalse($this->working()->isDefaultType);
    }

    public function testCreatingASetTypeStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->create->execute($currentUser, new CreateSetTypeDataInput('Rest-pause', SetTypeColourRegistry::TEAL)),
        );
    }
}
