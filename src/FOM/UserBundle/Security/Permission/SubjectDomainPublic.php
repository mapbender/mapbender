<?php

namespace FOM\UserBundle\Security\Permission;

use FOM\UserBundle\Entity\Permission;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubjectDomainPublic extends AbstractSubjectDomain
{
    const SLUG = "public";

    public function __construct(private TranslatorInterface $translator)
    {

    }

    function getIconClass(): string
    {
        return "fas fa-globe";
    }


    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function buildWhereClause(?UserInterface $user): WhereClauseComponent
    {
        return new WhereClauseComponent("p.subject_domain = '" . self::SLUG . "'");
    }

    function getTitle(Permission $subject): string
    {
        return $this->translator->trans('fom.security.domain.public');
    }
}
