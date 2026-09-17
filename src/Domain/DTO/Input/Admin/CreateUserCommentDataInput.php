<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Admin;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateUserCommentDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'body_required')]
        #[Assert\Length(max: 2000, maxMessage: 'body_too_long')]
        public string $body,
    ) {
    }
}
