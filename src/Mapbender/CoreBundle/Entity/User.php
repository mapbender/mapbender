<?php

/**
 * TODO: Validation
 * TODO: Basic user data
 * TODO: User profiles
 */

namespace Mapbender\CoreBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User entity.
 *
 * This needs enhancement, email should probably required. And we need a way
 * to implements user profiles which can vary from installation to
 * installation.
 *
 * @author Christian Wygoda
 * @author apour
 * @ORM\Entity
 * @ORM\Table(name="mb_user")
 */
class User implements UserInterface {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=false, length=512, unique=true)
     * @Assert\NotBlank()
     * @Assert\MinLength(3)
     */
    protected $username;

    /**
     * @ORM\Column(type="string", nullable=false, length=512, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    protected $email;

    /**
     * @ORM\Column
     * @Assert\NotBlank()
     * @Assert\MinLength(8)
     */
    protected $password;

    /**
     * @ORM\Column
     */
    protected $salt;

    /**
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users")
     * @ORM\JoinTable(name="mb_users_roles")
     */
    protected $roles;

    public function __construct() {
        $this->roles = new ArrayCollection();
    }

    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id) {
        $this->id = $id;
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
     * Set username
     *
     * @param string $username
     */
    public function setUsername($username) {
        $this->username = $username;
        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Set email
     *
     * @param string $email
     */
    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = $password;
        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Set salt
     *
     * @param string $salt
     */
    public function setSalt($salt) {
        $this->salt = $salt;
    }

    /**
     * Get salt
     *
     * @param string
     */
    public function getSalt() {
        return $this->salt;
    }

    /**
     * Add roles
     *
     * @param Mapbender\CoreBundle\Entity\Role $roles
     */
    public function addRoles(\Mapbender\CoreBundle\Entity\Role $roles) {
        $this->roles[] = $roles;
        return $this;
    }

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles() {
        $roles = $this->roles->toArray();
        $roles[] = 'ROLE_MB3_USER';
        return $roles;
    }

    /**
     * Get role objects
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getRoleObjects() {
        return $this->roles;
    }

    /**
     * Erase sensitive data
     *
     * Removes password and salt from the user object
     */
    public function eraseCredentials() {
        $this->password = null;
        $this->salt = null;
    }

    /**
     * Compare users
     *
     * This user class is only compatible with itself and compares the
     * username property. If you'r needs differ, use a subclass.
     *
     * @param UserInterface $user The user to compare
     */
    public function equals(UserInterface $user) {
        return (get_class() === get_class($user)
            && $this->getUsername() === $user->getUsername());
    }
}

