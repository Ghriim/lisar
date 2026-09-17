<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Session;

use App\Domain\DTO\Input\SensitiveDataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sign-in credentials. Never logged, and the password policy is not re-checked here: an account
 * created before a policy change must still be able to sign in.
 */
final readonly class CreateSessionDataInput implements SensitiveDataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'email_required')]
        #[Assert\Email(message: 'email_invalid')]
        public string $email,

        #[Assert\NotBlank(message: 'password_required')]
        public string $password,
    ) {
    }
}
