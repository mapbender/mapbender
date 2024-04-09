<?php

namespace FOM\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a single permission
 * @ORM\Entity()
 * @ORM\Table(name="fom_permission")
 */
class Permission
{
    const SUBJECT_DOMAIN_PUBLIC = "public";
    const SUBJECT_DOMAIN_REGISTERED = "registered";
    const SUBJECT_DOMAIN_USER = "user";
    const SUBJECT_DOMAIN_GROUP = "group";

    const ATTRIBUTE_DOMAIN_INSTALLATION = "installation";
    const ATTRIBUTE_DOMAIN_APPLICATION = "application";
    const ATTRIBUTE_DOMAIN_ELEMENT = "element";


    const PERMISSION_CREATE_APPLICATIONS = "create_applications";
    const PERMISSION_VIEW_ALL_APPLICATIONS = "view_all_applications";
    const PERMISSION_EDIT_ALL_APPLICATIONS = "edit_all_applications";
    const PERMISSION_DELETE_ALL_APPLICATIONS = "delete_all_applications";
    const PERMISSION_OWN_ALL_APPLICATIONS = "own_all_applications";

    const PERMISSION_VIEW_SOURCES = "view_sources";
    const PERMISSION_CREATE_SOURCES = "create_sources";
    const PERMISSION_REFRESH_SOURCES = "refresh_sources";
    const PERMISSION_EDIT_FREE_INSTANCES = "edit_free_instances";
    const PERMISSION_DELETE_SOURCES = "delete_sources";

    const PERMISSION_MANAGE_PERMISSION = "manage_permissions";

    const PERMISSION_VIEW_USERS = "view_users";
    const PERMISSION_CREATE_USERS = "create_users";
    const PERMISSION_EDIT_USERS = "edit_users";
    const PERMISSION_DELETE_USERS = "delete_users";

    const PERMISSION_VIEW_GROUPS = "view_groups";
    const PERMISSION_CREATE_GROUPS = "create_groups";
    const PERMISSION_EDIT_GROUPS = "edit_groups";
    const PERMISSION_DELETE_GROUPS = "delete_groups";


    const PERMISSION_APPLICATION_VIEW = "view";
    const PERMISSION_APPLICATION_EDIT = "edit";
    const PERMISSION_APPLICATION_DELETE = "delete";
    const PERMISSION_APPLICATION_MANAGE_PERMISSIONS = "manage_permissions";

    const PERMISSION_ELEMENT_VIEW = "view";


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
     * - public: Right granted to everyone, logged in or not
     * - registered: Right granted to every logged in user
     * - group: Right granted to a specific group, @see Group)
     * - user: Right granted to a specific user, @see User
     * might be extended for custom requirements
     */
    protected ?string $subjectDomain = null;

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"ALL"})
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * References the user if subjectDomain is "user"
     */
    protected ?User $user = null;

    /**
     * @ORM\ManyToOne(targetEntity="Group", cascade={"ALL"})
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * References the group if subjectDomain is "group"
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
     * - installation: Installation-wide validity, like "create_application"
     * - application: right is valid for a specific application, @see Application
     * - element: right is valid for a specific element, @see Element
     * might be extended for custom requirements
     */
    protected ?string $attributeDomain = null;

    /**
     * @ORM\ManyToOne(targetEntity="Mapbender\CoreBundle\Entity\Element", cascade={"ALL"})
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * References the element if attributeDomain is "element"
     */
    protected ?Element $element = null;

    /**
     * @ORM\ManyToOne(targetEntity="Mapbender\CoreBundle\Entity\Application", cascade={"ALL"})
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * References the application if attributeDomain is "application"
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
