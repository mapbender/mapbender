<?php


namespace FOM\UserBundle\Component;


use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Unpersisted display-only version of Group entity
 */
class DummyGroup implements RoleInterface
{
    /** @var string */
    protected $role;
    /** @var string */
    protected $title;

    /**
     * @param string $role
     * @param string $title
     */
    public function __construct($role, $title)
    {
        $this->role = $role;
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getRole()
    {
        return $this->role;
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
