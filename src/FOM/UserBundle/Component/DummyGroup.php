<?php


namespace FOM\UserBundle\Component;


use Symfony\Component\Security\Core\Role\Role;

/**
 * Unpersisted display-only version of Group entity
 */
class DummyGroup extends Role
{
    /** @var string */
    protected $title;

    /**
     * @param string $role
     * @param string $title
     */
    public function __construct($role, $title)
    {
        parent::__construct($role);
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string|null
     * @deprecated dummy adapter method for compatibility with legacy templates
     */
    public function getAsRole()
    {
        return $this->getRole();
    }
}
