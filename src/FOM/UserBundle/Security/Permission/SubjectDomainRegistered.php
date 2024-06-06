<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubjectDomainRegistered extends AbstractSubjectDomain
{
    const SLUG = "registered";

    public function __construct(protected TranslatorInterface $translator, protected bool $isAssignable)
    {

    }

    function getIconClass(): string
    {
        return "fas fa-people-roof";
    }


    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function buildWhereClause(QueryBuilder $q, ?UserInterface $user): void
    {
        // registered user rules are valid for any registered user
        if ($user !== null) {
            $q->orWhere("p.subjectDomain = '" . self::SLUG . "'");
        }
    }

    function getTitle(?SubjectInterface $subject): string
    {
        return $this->translator->trans('fom.security.domain.registered');
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

    public function supports(mixed $subject, ?string $action = null): bool
    {
        return $subject === self::SLUG;
    }
}
