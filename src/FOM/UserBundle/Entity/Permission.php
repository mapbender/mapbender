<?php

namespace FOM\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use FOM\UserBundle\Security\Permission\ResourceDomainElement;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
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
     * default mapbender supports:
     * - public: Right granted to everyone, logged in or not, @see SubjectDomainPublic
     * - registered: Right granted to every logged in user, @see SubjectDomainRegistered
     * - group: Right granted to a specific group, @see SubjectDomainGroup
     * - user: Right granted to a specific user, @see SubjectDomainUser
     * might be extended for custom requirements
     */
    protected ?string $subjectDomain = null;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * References the user for @see SubjectDomainUser
     */
    protected ?User $user = null;

    /**
     * @ORM\ManyToOne(targetEntity="Group")
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
     * @ORM\Column(type="string", name="resource_domain")
     * @Assert\NotBlank()
     * stores the attribute domain of this right.
     * default mapbender supports
     * - installation: Installation-wide validity, like "create_application", @see ResourceDomainInstallation
     * - application: right is valid for a specific application, @see ResourceDomainApplication
     * - element: right is valid for a specific element, @see ResourceDomainElement
     * might be extended for custom requirements
     */
    protected ?string $resourceDomain = null;

    /**
     * @ORM\ManyToOne(targetEntity="Mapbender\CoreBundle\Entity\Element")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * References the element for resource domain @see ResourceDomainElement
     */
    protected ?Element $element = null;

    /**
     * @ORM\ManyToOne(targetEntity="Mapbender\CoreBundle\Entity\Application")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * References the application for resource domain @see ResourceDomainApplication
     */
    protected ?Application $application = null;


    /**
     * @ORM\Column(type="string", nullable=true)
     * Can store an attribute for custom resource domains. When using this, make sure to implement a sensible
     * strategy to delete permission entries for deleted attributes
     */
    protected ?string $resource = null;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * Stores the action for the given resource domain, like "view" or "edit"
     */
    protected ?string $action = null;


    public function __construct(?string      $subjectDomain = null,
                                ?User        $user = null,
                                ?Group       $group = null,
                                ?string      $subject = null,
                                ?string      $resourceDomain = null,
                                ?Element     $element = null,
                                ?Application $application = null,
                                ?string      $resource = null,
                                ?string      $action = null,
    )
    {
        $this->subjectDomain = $subjectDomain;
        $this->user = $user;
        $this->group = $group;
        $this->subject = $subject;
        $this->resourceDomain = $resourceDomain;
        $this->element = $element;
        $this->application = $application;
        $this->resource = $resource;
        $this->action = $action;
    }


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

    public function getResourceDomain(): ?string
    {
        return $this->resourceDomain;
    }

    public function setResourceDomain(?string $resourceDomain): void
    {
        $this->resourceDomain = $resourceDomain;
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

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function setResource(?string $resource): void
    {
        $this->resource = $resource;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): void
    {
        $this->action = $action;
    }

}
