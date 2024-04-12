<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{

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
        $attributeDomain = $this->getAttributeDomain($attribute, $subject);
        $attributeWhereClause = $attributeDomain->buildWhereClause($attribute, $subject);

        $subjectWhereComponents = [];
        $variables = $attributeWhereClause->variables;
        foreach ($this->subjectDomains as $subjectDomain) {
            $wrapper = $subjectDomain->buildWhereClause($token->getUser());
            if ($wrapper === null) continue;
            $subjectWhereComponents[] = '(' . $wrapper->whereClause . ')';
            $variables = array_merge($variables, $wrapper->variables);
        };

        $sql = 'SELECT COUNT(*) FROM fom_permission p WHERE (' . $attributeWhereClause->whereClause . ')';
        if (count($subjectWhereComponents) > 0) $sql .= ' AND (' . implode(' OR ', $subjectWhereComponents) . ")";
        $count = $this->doctrine->getConnection()->executeQuery($sql, $variables)->fetchOne();
        return $count > 0;
    }

    private function getAttributeDomain(string $attribute, mixed $subject): ?AbstractAttributeDomain
    {
        foreach ($this->attributeDomains as $attributeDomain) {
            if ($attributeDomain->supports($attribute, $subject)) return $attributeDomain;
        }
        return null;
    }
}
