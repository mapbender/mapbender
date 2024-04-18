<?php

namespace FOM\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOM\UserBundle\Security\Permission\AttributeDomainApplication;
use FOM\UserBundle\Security\Permission\AttributeDomainElement;
use FOM\UserBundle\Security\Permission\AttributeDomainInstallation;
use FOM\UserBundle\Security\Permission\SubjectDomainGroup;
use FOM\UserBundle\Security\Permission\SubjectDomainPublic;
use FOM\UserBundle\Security\Permission\SubjectDomainRegistered;
use FOM\UserBundle\Security\Permission\SubjectDomainUser;
use FOM\UserBundle\Security\Permission\SubjectInterface;
use FOM\UserBundle\Security\Permission\SubjectTrait;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a single permission
 * @ORM\Entity()
 * @ORM\Table(name="fom_permission")
 */
class Permission implements SubjectInterface
{
    use SubjectTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(type="string", name="subject_domain")
     * @Assert\NotBlank()
     * stores the type of subject this right is granted to.
     * default mapbender supports (refer to SUBJECT_DOMAIN_* constants)
     * - public: Right granted to everyone, logged in or not, @see SubjectDomainPublic
     * - registered: Right granted to every logged in user, @see SubjectDomainRegistered
     * - group: Right granted to a specific group, @see SubjectDomainGroup
     * - user: Right granted to a specific user, @see SubjectDomainUser
     * might be extended for custom requirements
     */
    protected ?string $subjectDomain = null;

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"ALL"})
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * References the user for @see SubjectDomainUser
     */
    protected ?User $user = null;

    /**
     * @ORM\ManyToOne(targetEntity="Group", cascade={"ALL"})
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * References the group  for @see SubjectDomainGroup
     */
    protected ?Group $group = null;


    /**
     * @ORM\Column(type="string", nullable=true)
     * Can store a subject for custom subjectDomains. When using this, make sure to implement a sensible
     * strategy to delete permission entries for deleted subjects
     */
    protected ?string $subject = null;

    /**
     * @ORM\Column(type="string", name="attribute_domain")
     * @Assert\NotBlank()
     * stores the attribute domain of this right.
     * default mapbender supports
     * - installation: Installation-wide validity, like "create_application", @see AttributeDomainInstallation
     * - application: right is valid for a specific application, @see AttributeDomainApplication
     * - element: right is valid for a specific element, @see AttributeDomainElement
     * might be extended for custom requirements
     */
    protected ?string $attributeDomain = null;

    /**
     * @ORM\ManyToOne(targetEntity="Mapbender\CoreBundle\Entity\Element", cascade={"ALL"})
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * References the element for attributeDomain @see AttributeDomainElement
     */
    protected ?Element $element = null;

    /**
     * @ORM\ManyToOne(targetEntity="Mapbender\CoreBundle\Entity\Application", cascade={"ALL"})
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * References the application for attributeDomain @see AttributeDomainApplication
     */
    protected ?Application $application = null;


    /**
     * @ORM\Column(type="string", nullable=true)
     * Can store an attribute for custom attributeDomains. When using this, make sure to implement a sensible
     * strategy to delete permission entries for deleted attributes
     */
    protected ?string $attribute = null;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * Stores the actual permission for the given attribute domain, like "view" or "edit"
     */
    protected ?string $permission = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getSubjectDomain(): ?string
    {
        return $this->subjectDomain;
    }

    public function setSubjectDomain(?string $subjectDomain): void
    {
        $this->subjectDomain = $subjectDomain;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): void
    {
        $this->group = $group;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    public function getAttributeDomain(): ?string
    {
        return $this->attributeDomain;
    }

    public function setAttributeDomain(?string $attributeDomain): void
    {
        $this->attributeDomain = $attributeDomain;
    }

    public function getElement(): ?Element
    {
        return $this->element;
    }

    public function setElement(?Element $element): void
    {
        $this->element = $element;
    }

    public function getApplication(): ?Application
    {
        return $this->application;
    }

    public function setApplication(?Application $application): void
    {
        $this->application = $application;
    }

    public function getAttribute(): ?string
    {
        return $this->attribute;
    }

    public function setAttribute(?string $attribute): void
    {
        $this->attribute = $attribute;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function setPermission(?string $permission): void
    {
        $this->permission = $permission;
    }

}
