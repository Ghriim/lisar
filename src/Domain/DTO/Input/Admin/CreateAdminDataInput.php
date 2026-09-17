<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Admin;

use App\Domain\DTO\Input\SensitiveDataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creating an administrator. There is no endpoint for this on purpose: it only ever comes from
 * the console command, which is how the very first administrator comes to exist.
 */
final readonly class CreateAdminDataInput implements SensitiveDataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'username_required')]
        #[Assert\Length(min: 3, max: 32, minMessage: 'username_too_short', maxMessage: 'username_too_long')]
        #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/', message: 'username_invalid_characters')]
        public string $username,

        #[Assert\NotBlank(message: 'email_required')]
        #[Assert\Email(message: 'email_invalid')]
        #[Assert\Length(max: 180, maxMessage: 'email_too_long')]
        public string $email,

        // The same policy as a public sign-up: an administrator's password is not exempt.
        #[Assert\NotBlank(message: 'password_required')]
        #[Assert\Length(min: 8, max: 4096, minMessage: 'password_too_short', maxMessage: 'password_too_long')]
        #[Assert\Regex(pattern: '/\p{Ll}/u', message: 'password_missing_lowercase')]
        #[Assert\Regex(pattern: '/\p{Lu}/u', message: 'password_missing_uppercase')]
        #[Assert\Regex(pattern: '/\p{Nd}/u', message: 'password_missing_digit')]
        #[Assert\Regex(pattern: '/[^\p{L}\p{Nd}]/u', message: 'password_missing_special_character')]
        public string $password,
    ) {
    }
}
