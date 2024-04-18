<?php

namespace FOM\UserBundle\Security\Permission;

use FOM\UserBundle\Entity\Permission;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubjectDomainRegistered extends AbstractSubjectDomain
{
    const SLUG = "registered";

    public function __construct(private TranslatorInterface $translator)
    {

    }

    function getIconClass(): string
    {
        return "fas fa-person-circle-question";
    }


    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function buildWhereClause(?UserInterface $user): ?WhereClauseComponent
    {
        // registered user rules are valid for any registered user
        if ($user !== null) {
            return new WhereClauseComponent("p.subject_domain = '" . self::SLUG . "'");
        }
        return null;
    }

    function getTitle(Permission $subject): string
    {
        return $this->translator->trans('fom.security.domain.registered');
    }
}
