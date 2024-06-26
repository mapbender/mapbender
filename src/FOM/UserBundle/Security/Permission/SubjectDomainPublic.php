<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;
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

    public function buildWhereClause(QueryBuilder $q, ?UserInterface $user): void
    {
        $q->orWhere("p.subjectDomain = '" . self::SLUG . "'");
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

    public function getSubjectJson(): string
    {
        return json_encode([
            'domain' => self::SLUG,
            'user_id' => null,
            'group_id' => null,
            'subject' => null,
        ]);
    }

    public function supports(mixed $subject, ?string $action = null): bool
    {
        return $subject === self::SLUG;
    }
}
