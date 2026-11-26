<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Category\Admin;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\Input\Category\Admin\UpdateReferenceCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Category\CategoryIconRegistry;
use App\Domain\Validation\Constraint\Category\CategoryLabelAvailableConstraint;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\Category\Admin\UpdateReferenceCategoryValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateReferenceCategoryValidatorTest extends TestCase
{
    use CurrentUserTrait;

    private UpdateReferenceCategoryValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateReferenceCategoryValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsARename(): void
    {
        $this->validator->validate(
            $this->currentAdmin(),
            new UpdateReferenceCategoryDataInput('Maison', CategoryIconRegistry::HOME),
            $this->buildCategory(1),
            null,
        );

        $this->expectNotToPerformAssertions();
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            $this->validator->validate(
                $this->anyCurrentUser(),
                new UpdateReferenceCategoryDataInput('Maison', CategoryIconRegistry::HOME),
                $this->buildCategory(1),
                null,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateReferenceCategoryValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testACategoryMayKeepItsOwnLabel(): void
    {
        $category = $this->buildCategory(7);

        $this->validator->validate($this->currentAdmin(), new UpdateReferenceCategoryDataInput('Home', CategoryIconRegistry::HOME), $category, $category);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsALabelAnotherCommonCategoryHas(): void
    {
        try {
            $this->validator->validate(
                $this->currentAdmin(),
                new UpdateReferenceCategoryDataInput('Work', CategoryIconRegistry::HOME),
                $this->buildCategory(1),
                $this->buildCategory(2),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                CategoryLabelAvailableConstraint::LABEL_ALREADY_USED,
                $exception->violations['label'],
            );
        }
    }

    private function buildCategory(int $id): CategoryDataModel
    {
        $category = new CategoryDataModel();
        $category->id = $id;
        $category->label = 'Home';
        $category->owner = null;

        return $category;
    }
}
