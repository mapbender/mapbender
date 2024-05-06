<?php

namespace FOM\UserBundle\Security\Permission;

use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\User;

/**
 * An assignable subject represents a subject that can be added to the permission list
 * It is not yet connected to a resource
 */
class AssignableSubject implements SubjectInterface
{
    use SubjectTrait;

    public function __construct(
        private string  $subjectDomain,
        private string  $title,
        private string  $iconClass,
        private ?User   $user = null,
        private ?Group  $group = null,
        private ?string $subject = null,
    )
    {
    }

    public function getSubjectDomain(): string
    {
        return $this->subjectDomain;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getIconClass(): string
    {
        return $this->iconClass;
    }


}
