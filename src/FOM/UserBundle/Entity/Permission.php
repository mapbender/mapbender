<?php

namespace FOM\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOM\UserBundle\Security\Permission\SubjectDomainGroup;
use FOM\UserBundle\Security\Permission\SubjectDomainPublic;
use FOM\UserBundle\Security\Permission\SubjectDomainRegistered;
use FOM\UserBundle\Security\Permission\SubjectDomainUser;
use FOM\UserBundle\Security\Permission\SubjectInterface;
use FOM\UserBundle\Security\Permission\SubjectTrait;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\ReusableSourceInstanceAssignment;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a single permission
 */
#[ORM\Entity]
#[ORM\Table(name: 'fom_permission')]
class Permission implements SubjectInterface
{
    use SubjectTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * stores the type of subject this right is granted to.
     * default mapbender supports:
     * - public: Right granted to everyone, logged in or not, @see SubjectDomainPublic
     * - registered: Right granted to every logged in user, @see SubjectDomainRegistered
     * - group: Right granted to a specific group, @see SubjectDomainGroup
     * - user: Right granted to a specific user, @see SubjectDomainUser
     * might be extended for custom requirements
     */
    #[Assert\NotBlank]
    #[ORM\Column(name: 'subject_domain', type: 'string')]
    protected ?string $subjectDomain = null;

    /** References the user for @see SubjectDomainUser */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    protected ?User $user = null;

    /** References the group  for @see SubjectDomainGroup */
    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    protected ?Group $group = null;


    /**
     * Can store a subject for custom subjectDomains. When using this, make sure to implement a sensible
     * strategy to delete permission entries for deleted subjects
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $subject = null;

    /**  stores the resources domain of this right.
     * default mapbender supports
     * - installation: Installation-wide validity, like "create_application", @see ResourceDomainInstallation
     * - application: right is valid for a specific application, @see ResourceDomainApplication
     * - element: right is valid for a specific element, @see ResourceDomainElement
     * might be extended for custom requirements
     */
    #[Assert\NotBlank]
    #[ORM\Column(name: 'resource_domain', type: 'string')]
    protected ?string $resourceDomain = null;

    /** References the element for resource domain @see ResourceDomainElement */
    #[ORM\ManyToOne(targetEntity: Element::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    protected ?Element $element = null;

    /** References the application for resource domain @see ResourceDomainApplication */
    #[ORM\ManyToOne(targetEntity: Application::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    protected ?Application $application = null;

    /** References the source instance for resource domain @see ResourceDomainSourceInstance */
    #[ORM\ManyToOne(targetEntity: SourceInstance::class)]
    #[ORM\JoinColumn(name: "source_instance_id", nullable: true, onDelete: 'CASCADE')]
    protected ?SourceInstance $sourceInstance = null;

    /** References the reusable source instance for resource domain @see ResourceDomainSourceInstance */
    #[ORM\ManyToOne(targetEntity: ReusableSourceInstanceAssignment::class)]
    #[ORM\JoinColumn(name: "shared_instance_assignment_id", nullable: true, onDelete: 'CASCADE')]
    protected ?ReusableSourceInstanceAssignment $sharedInstanceAssignment = null;


    /**
     * Can store an attribute for custom resource domains. When using this, make sure to implement a sensible
     * strategy to delete permission entries for deleted attributes
     */
    #[ORM\Column(name: "resource_ref", type: 'string', nullable: true)]
    protected ?string $resource = null;

    /** Stores the action for the given resource domain, like "view" or "edit"  */
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string')]
    protected ?string $action = null;


    public function __construct(?string                           $subjectDomain = null,
                                ?User                             $user = null,
                                ?Group                            $group = null,
                                ?string                           $subject = null,
                                ?string                           $resourceDomain = null,
                                ?Element                          $element = null,
                                ?Application                      $application = null,
                                ?string                           $resource = null,
                                ?string                           $action = null,
                                ?SourceInstance                   $sourceInstance = null,
                                ?ReusableSourceInstanceAssignment $sharedInstanceAssignment = null,
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
        $this->sourceInstance = $sourceInstance;
        $this->sharedInstanceAssignment = $sharedInstanceAssignment;
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

    public function getSourceInstance(): ?SourceInstance
    {
        return $this->sourceInstance;
    }

    public function setSourceInstance(?SourceInstance $sourceInstance): void
    {
        $this->sourceInstance = $sourceInstance;
    }

    public function getSharedInstanceAssignment(): ?ReusableSourceInstanceAssignment
    {
        return $this->sharedInstanceAssignment;
    }

    public function setSharedInstanceAssignment(?ReusableSourceInstanceAssignment $sharedInstanceAssignment): void
    {
        $this->sharedInstanceAssignment = $sharedInstanceAssignment;
    }

}
