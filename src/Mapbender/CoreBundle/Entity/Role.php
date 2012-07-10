<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Role entity.
 *
 * @author Christian Wygoda
 * @ORM\Entity(repositoryClass="Mapbender\CoreBundle\Entity\Repository\RoleRepository")
 * @ORM\Table(name="mb_role")
 */
class Role implements RoleInterface {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * ORM\Column(type="integer")
     */
    protected $mpttLeft;

    /**
     * ORM\Column(type="integer")
     */
    protected $mpttRight;

    /**
     * @ORM\Column(type="string", unique=true)
     */
    protected $title;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $override;

    /**
     * @ORM\Column(type="text")
     */
    protected $description;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="roles",
     *     cascade={"persist", "remove"})
     */
    protected $users;

    public function __construct() {
        $this->users = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set MPTT left
     *
     * @param integer $left
     */
    public function setMpttLeft($mpttLeft) {
        $this->mpttLeft = $mpttLeft;
        return $this;
    }

    /**
     * Get MPTT left
     *
     * @return integer
     */
    public function getMpttLeft() {
        return $this->mpttLeft;
    }

    /**
     * Set MPTT right
     *
     * @param integer $right
     */
    public function setMpttRight($mpttRight) {
        $this->mpttRight = $mpttRight;
        return $this;
    }

    /**
     * Get MPTT right
     *
     * @return integer
     */
    public function getMpttRight() {
        return $this->mpttRight;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set the internal role override
     *
     * @param string $override
     */
    public function setOverride($override) {
        $this->override = $override;
        return $this;
    }

    /**
     * Get the internal role override
     */
    public function getOverride($override) {
        return $this->override;
    }

    /**
     * Set description
     *
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Add users
     *
     * @param User $users
     */
    public function addUsers(User $users) {
        $this->users[] = $users;
        return $this;
    }

    /**
     * Get users
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getUsers() {
        return $this->users;
    }

    /**
     * Returns the string representation needed for the RoleInterface to
     * work.
     *
     * @return string
     */
    public function getRole() {
        if($this->roleOverride !== null) {
            return $this->roleOverride;
        } else {
            return 'ROLE_MB3_' . $this->id;
        }
    }
}

