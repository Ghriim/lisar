<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Infrastructure\Security\SecurityUser;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/** What every controller shares: who is calling. */
abstract class AbstractBaseController extends AbstractController
{
    /**
     * The signed-in account, for the use case to take first: the data model the user provider
     * loaded to authenticate the request, so no query is spent on it. Only a route behind the
     * firewall asks: called from a public one, it is a mistake in the route, not in the request.
     */
    protected function getCurrentUser(): UserDataModel
    {
        $user = $this->getUser();
        if (false === $user instanceof SecurityUser) {
            throw new LogicException('No signed-in account: the route is not behind the firewall.');
        }

        return $user->user;
    }
}
