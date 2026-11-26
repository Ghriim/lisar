<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Category\Admin;

use App\Domain\DTO\Input\DataInputInterface;
use App\Domain\Registry\Category\CategoryIconRegistry;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A category everyone sees and nobody but the back-office writes. Its counterpart, the one a
 * person creates for themselves, is CreateCategoryDataInput.
 */
final readonly class CreateReferenceCategoryDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'label_required')]
        #[Assert\Length(max: 32, maxMessage: 'label_too_long')]
        public string $label,

        // Required on a common category: it is the icon of everything filed in it.
        #[Assert\Choice(choices: CategoryIconRegistry::ALL, message: 'icon_unknown')]
        public string $icon,
    ) {
    }
}
