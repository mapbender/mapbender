<?php


namespace FOM\UserBundle\Component;


/**
 * Rendering facade for Group entities and other group identities
 */
class DummyGroup
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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getRole()
    {
        return $this->role;
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
