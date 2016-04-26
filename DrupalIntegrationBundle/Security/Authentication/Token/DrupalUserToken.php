<?php

namespace Mapbender\DrupalIntegrationBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;


class DrupalUserToken extends AbstractToken
{
    public function __construct($drupalUser)
    {
        parent::__construct($drupalUser->getRoles());

        $authenticated = $drupalUser->getId() !== 0;
        $this->setAuthenticated($authenticated);
        $this->setUser($drupalUser);
    }

    public function getCredentials()
    {
        return '';
    }
}
