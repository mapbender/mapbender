<?php

namespace FOM\UserBundle\Security\Permission;

use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\User;

interface SubjectInterface
{
    public function getSubjectDomain(): ?string;

    public function getUser(): ?User;

    public function getGroup(): ?Group;

    public function getSubject(): ?string;

    public function getSubjectJson(): string;
}
