<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Session;

use App\Domain\DTO\Input\SensitiveDataInputInterface;
use App\Infrastructure\HttpKernel\Attribute\NotTrimmed;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sign-in credentials, taken exactly as they were typed — every field is #[NotTrimmed].
 *
 * Credentials are matched, not interpreted: silently editing what someone sent before comparing
 * it would mean granting a session to something they did not type. The password policy is not
 * re-checked here either, since an account created before a policy change must still get in.
 */
final readonly class LoginDataInput implements SensitiveDataInputInterface
{
    public function __construct(
        #[NotTrimmed]
        #[Assert\NotBlank(message: 'email_required')]
        #[Assert\Email(message: 'email_invalid')]
        public string $email,

        #[NotTrimmed]
        #[Assert\NotBlank(message: 'password_required')]
        public string $password,
    ) {
    }
}
