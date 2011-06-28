<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User entity.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 * @ORM\Entity
 * @ORM\Table(name="Users")
 */
class User implements UserInterface {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 */
	protected $id;

	/** @ORM\Column(length=512) */
	protected $username;

	/** @ORM\Column(length=512) */
	protected $password;

	/** @ORM\Column(length=256) */
	protected $email;

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

