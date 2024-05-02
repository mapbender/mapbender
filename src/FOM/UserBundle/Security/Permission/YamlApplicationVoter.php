<?php

namespace FOM\UserBundle\Security\Permission;


use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class YamlApplicationVoter extends Voter
{
    public const ROLE_PUBLIC = "public";
    public const ROLE_REGISTERED = "registered";
    public const GROUPS = "groups";
    public const USERS = "users";


    protected function supports(string $attribute, $subject): bool
    {
        return false;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return false;
    }
}
