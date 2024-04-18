<?php

namespace FOM\UserBundle\Security\Permission;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubjectDomainPublic extends AbstractSubjectDomain
{
    const SLUG = "public";

    public function __construct(private TranslatorInterface $translator, protected bool $isAssignable)
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

    function getTitle(?SubjectInterface $subject): string
    {
        return $this->translator->trans('fom.security.domain.public');
    }

    public function getAssignableSubjects(): array
    {
        if (!$this->isAssignable) return [];
        return [new AssignableSubject(
            self::SLUG,
            $this->getTitle(null),
            $this->getIconClass()
        )];
    }
}
