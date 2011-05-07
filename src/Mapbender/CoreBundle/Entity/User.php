<?php

namespace Mapbender\CoreBundle\Entity;

/**
 * User entity.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 * @orm:Entity
 * @orm:Table(name="Users")
 */
class User {
	/** 
	 * @orm:Column(type="integer") 
	 * @orm:Id
	 */
	protected $id;

	/** @orm:Column(length=512) */
	protected $password;

	/** @orm:Column(length=256) */
	protected $first_name;

	/** @orm:Column(length=256) */
	protected $last_name;

	/** @orm:Column(length=256) */
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
	 * Set first_name
	 *
	 * @param string $firstName
	 */
	public function setFirstName($firstName)
	{
		$this->first_name = $firstName;
	}

	/**
	 * Get first_name
	 *
	 * @return string $firstName
	 */
	public function getFirstName()
	{
		return $this->first_name;
	}

	/**
	 * Set last_name
	 *
	 * @param string $lastName
	 */
	public function setLastName($lastName)
	{
		$this->last_name = $lastName;
	}

	/**
	 * Get last_name
	 *
	 * @return string $lastName
	 */
	public function getLastName()
	{
		return $this->last_name;
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
}

