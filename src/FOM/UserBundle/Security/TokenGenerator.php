<?php

namespace FOM\UserBundle\Security;

class TokenGenerator
{
    public static function generateSecureToken(): string
    {
        return bin2hex(random_bytes(20));
    }
}
