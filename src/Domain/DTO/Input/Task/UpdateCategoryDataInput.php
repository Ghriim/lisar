<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Task;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateCategoryDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'label_required')]
        #[Assert\Length(max: 32, maxMessage: 'label_too_long')]
        public string $label,
    ) {
    }
}
