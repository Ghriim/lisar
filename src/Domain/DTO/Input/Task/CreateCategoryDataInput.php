<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Task;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A category the person creates for themselves. The reference set is the back-office's business.
 */
final readonly class CreateCategoryDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'label_required')]
        // Upper bound imposed by the column, not a business rule.
        #[Assert\Length(max: 32, maxMessage: 'label_too_long')]
        public string $label,
    ) {
    }
}
