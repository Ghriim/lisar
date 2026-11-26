<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Todo;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A TODO's title, to create one or to rename it. Measured trimmed, as it is stored and compared.
 */
final readonly class SaveTodoDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'title_required', normalizer: 'trim')]
        #[Assert\Length(max: 125, maxMessage: 'title_too_long', normalizer: 'trim')]
        public string $title = '',
    ) {
    }
}
