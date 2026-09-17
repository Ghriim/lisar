<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Session\AccessTokenIssuerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AccessTokenIssuer implements AccessTokenIssuerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtTokenManager,
        #[Autowire('%access_token_ttl%')]
        private int $ttlInSeconds,
    ) {
    }

    public function issue(UserDataModel $user): string
    {
        return $this->jwtTokenManager->create(SecurityUser::fromDataModel($user));
    }

    public function getTtlInSeconds(): int
    {
        return $this->ttlInSeconds;
    }
}
