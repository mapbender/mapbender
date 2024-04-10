<?php

namespace FOM\UserBundle\Security\Permission;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{

    public function __construct(
        /** @var AbstractAttributeDomain[] */
        private array $attributeDomains,
        /** @var AbstractSubjectDomain[] */
        private array $subjectDomains,
    )
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return false;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): int
    {
        return self::ACCESS_DENIED;
    }
}
