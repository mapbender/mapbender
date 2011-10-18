<?php

namespace Mapbender\CoreBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User entity.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 * @author apour
 * @ORM\Entity
 * @ORM\Table(name="`User`")
 */
class User implements UserInterface {
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @Assert\NotNull
	 * @ORM\Column(type="string", nullable="false",length=512)
	 */
	protected $username;
	
	/** @ORM\Column(length=512) */
	protected $password;

	/**
	 * @Assert\NotNull
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $email;
	
 /**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $firstName;
	
	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $lastName;

	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $displayName;

	/**
	 * Set id
	 *
	 * @param integer $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Get id
	 *
	 * @return integer $id
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set username
	 *
	 * @param string $username
	 */
	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * Get username
	 *
	 * @return string $username
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * Set password
	 *
	 * @param string $password
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * Get password
	 *
	 * @return string $password
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * Set email
	 *
	 * @param string $email
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * Get email
	 *
	 * @return string $email
	 */
	public function getEmail()
	{
		return $this->email;
	}
    /**
     * Set firstName
     *
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * Get firstName
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * Get lastName
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set displayName
     *
     * @param string $displayName
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    /**
     * Get displayName
     *
     * @return string 
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

	/**
	 * Set roles
	 *
	 * @param array $roles
	 */
	public function setRoles($roles) {
		//TODO: Store roles
	}

	/**
	 * Get roles
	 *
	 * @return array $roles
	 */
	public function getRoles() {
		//TODO: Retrieve roles
		return array('ROLE_USER');
	}

	/**
	 * Get password encoding salt
	 *
	 * @return string $salt
	 */
	public function getSalt() {
		//TODO: Make this configurable
		return '';
	}

	/**
	 * Remove sensitive data from the user
	 *
	 * @return void
	 */
	public function eraseCredentials() {
		$this->password = NULL;
	}

	/**
	 * Compare
	 *
	 * @return Boolean $equals
	 */
	public function equals(UserInterface $user) {
		if(get_class($this) === get_class($user) && $this->getUsername() === $user->getUsername()) {
			return true;
		} else {
			return false;
		}
	}
}

