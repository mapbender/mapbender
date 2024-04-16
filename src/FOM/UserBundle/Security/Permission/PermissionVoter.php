<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Entity\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{
    private array $cache = [];

    public function __construct(
        /** @var AbstractAttributeDomain[] */
        private array                  $attributeDomains,
        /** @var AbstractSubjectDomain[] */
        private array                  $subjectDomains,
        private EntityManagerInterface $doctrine,
    )
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $this->getAttributeDomain($attribute, $subject) !== null;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $permissions = $this->getPermissionsForToken($token);
        if (empty($permissions)) return false;

        $attributeDomain = $this->getAttributeDomain($attribute, $subject);
        foreach ($permissions as $permission) {
            if ($attributeDomain->matchesPermission($permission, $attribute, $subject)) return true;
        }
        return false;
    }

    /**
     * @param TokenInterface $token
     * @return array{permission: string, attribute_domain: string, attribute: ?string, element_id: ?int, application_id: ?int}
     */
    protected function getPermissionsForToken(TokenInterface $token): array {
        $subjectWhereComponents = [];
        $variables = [];
        foreach ($this->subjectDomains as $subjectDomain) {
            $wrapper = $subjectDomain->buildWhereClause($token->getUser());
            if ($wrapper === null) continue;
            $subjectWhereComponents[] = '(' . $wrapper->whereClause . ')';
            $variables = array_merge($variables, $wrapper->variables);
        };

        $sql = 'SELECT p.permission, p.attribute_domain, p.attribute, p.element_id, p.application_id FROM fom_permission p';
        if (count($subjectWhereComponents) > 0) $sql .= ' WHERE (' . implode(' OR ', $subjectWhereComponents) . ")";
        return $this->doctrine->getConnection()->executeQuery($sql, $variables)->fetchAllAssociative();
    }

    protected function getAttributeDomain(string $attribute, mixed $subject): ?AbstractAttributeDomain
    {
        foreach ($this->attributeDomains as $attributeDomain) {
            if ($attributeDomain->supports($attribute, $subject)) return $attributeDomain;
        }
        return null;
    }
}
