<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\User;

use App\Domain\DTO\Input\SensitiveDataInputInterface;
use App\Infrastructure\HttpKernel\Attribute\NotTrimmed;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The public sign-up payload. It carries a password, so it is never logged.
 */
final readonly class RegisterUserDataInput implements SensitiveDataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'username_required')]
        #[Assert\Length(min: 3, max: 32, minMessage: 'username_too_short', maxMessage: 'username_too_long')]
        // Kept URL-safe: the username will address a person in the future friends list.
        #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/', message: 'username_invalid_characters')]
        public string $username,

        #[Assert\NotBlank(message: 'email_required')]
        #[Assert\Email(message: 'email_invalid')]
        // Upper bound imposed by the column, not a business rule.
        #[Assert\Length(max: 180, maxMessage: 'email_too_long')]
        public string $email,

        #[NotTrimmed]
        #[Assert\NotBlank(message: 'password_required')]
        // Upper bound is the hasher's own limit.
        #[Assert\Length(min: 8, max: 4096, minMessage: 'password_too_short', maxMessage: 'password_too_long')]
        #[Assert\Regex(pattern: '/\p{Ll}/u', message: 'password_missing_lowercase')]
        #[Assert\Regex(pattern: '/\p{Lu}/u', message: 'password_missing_uppercase')]
        #[Assert\Regex(pattern: '/\p{Nd}/u', message: 'password_missing_digit')]
        // "Special" is anything that is neither a letter nor a digit, whitespace included.
        #[Assert\Regex(pattern: '/[^\p{L}\p{Nd}]/u', message: 'password_missing_special_character')]
        public string $password,
    ) {
    }
}
