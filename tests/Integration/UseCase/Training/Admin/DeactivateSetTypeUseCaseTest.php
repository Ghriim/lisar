<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Validation\Constraint\Training\SetTypeNotDefaultConstraint;
use App\Domain\Validation\Validator\Training\DeactivateSetTypeValidator;
use App\Fixtures\Training\SetTypeFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\DeactivateSetTypeUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Retiring a set type, never the default one. */
final class DeactivateSetTypeUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SetTypeTestTrait;
    use SignedInUserTrait;

    private DeactivateSetTypeUseCase $deactivate;
    private SetTypeProviderGateway $setTypeProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deactivate = self::getContainer()->get(DeactivateSetTypeUseCase::class);
        $this->setTypeProviderGateway = self::getContainer()->get(SetTypeProviderGateway::class);

        $this->loadFixtures(SetTypeFixtures::class);
    }

    public function testItRefusesToDeactivateTheDefault(): void
    {
        try {
            $this->deactivate->execute($this->currentAdmin(), $this->working()->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeactivateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([SetTypeNotDefaultConstraint::IS_THE_DEFAULT], $exception->violations['id']);
        }

        self::assertTrue($this->setTypeProviderGateway->findOneById($this->working()->id ?? 0)?->isActive);
    }

    public function testDeactivatingASetTypeStaysWithinItsQueryBudget(): void
    {
        $id = $this->dropset()->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->deactivate->execute($currentUser, $id),
        );
    }
}
