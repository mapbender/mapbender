<?php

namespace FOM\UserBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class SspiUserToken extends AbstractToken {

    public function __construct($authenticated = false, $roles = array()) {
        parent::__construct($roles);
        $this->setAuthenticated($authenticated);
    }

    public function getCredentials() {
        return '';
    }

}
