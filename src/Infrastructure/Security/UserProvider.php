<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Gateway\Provider\UserProviderGateway;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use function sprintf;

/**
 * Loads the firewall's user from the account, by e-mail — the sign-in identifier.
 *
 * It re-reads the account on every request, so deactivating an account takes effect at once even
 * though its access token is still cryptographically valid.
 *
 * @implements UserProviderInterface<SecurityUser>
 */
final readonly class UserProvider implements UserProviderInterface
{
    public function __construct(private UserProviderGateway $userProviderGateway)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userProviderGateway->findOneByEmail($identifier);

        if (null === $user) {
            throw new UserNotFoundException(sprintf('No account for identifier "%s".', $identifier));
        }

        if (false === $user->isActive) {
            throw new CustomUserMessageAccountStatusException('account_deactivated');
        }

        return SecurityUser::fromDataModel($user);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (false === $user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class || true === is_subclass_of($class, SecurityUser::class);
    }
}
