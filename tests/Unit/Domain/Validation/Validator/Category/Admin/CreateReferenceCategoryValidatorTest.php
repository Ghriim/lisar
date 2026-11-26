<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Category\Admin;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\Input\Category\Admin\CreateReferenceCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Category\CategoryIconRegistry;
use App\Domain\Validation\Constraint\Category\CategoryLabelAvailableConstraint;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\Category\Admin\CreateReferenceCategoryValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateReferenceCategoryValidatorTest extends TestCase
{
    use CurrentUserTrait;

    private CreateReferenceCategoryValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateReferenceCategoryValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsAFreeLabel(): void
    {
        $this->validator->validate($this->currentAdmin(), new CreateReferenceCategoryDataInput('Santé', CategoryIconRegistry::HOME), null);

        $this->expectNotToPerformAssertions();
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), new CreateReferenceCategoryDataInput('Santé', CategoryIconRegistry::HOME), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateReferenceCategoryValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsABlankLabel(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreateReferenceCategoryDataInput('', CategoryIconRegistry::HOME), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateReferenceCategoryValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('label_required', $exception->violations['label']);
        }
    }

    /** The icon is drawn for everything filed in the category: only a code the fronts know. */
    public function testItRejectsAnUnknownIcon(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreateReferenceCategoryDataInput('Santé', 'unicorn'), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['icon' => ['icon_unknown']], $exception->violations);
        }
    }

    public function testItRejectsALabelAnotherCommonCategoryHas(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreateReferenceCategoryDataInput('Home', CategoryIconRegistry::HOME), new CategoryDataModel());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                CategoryLabelAvailableConstraint::LABEL_ALREADY_USED,
                $exception->violations['label'],
            );
        }
    }
}
