<?php

namespace Mapbender\CoreBundle\Entity;

use Mapbender\CoreBundle\Component\ApplicationInterface;

/**
 * Application entity.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 * @orm:Entity
 */
class Application implements ApplicationInterface {
	/** 
	 * @orm:Column(type="integer") 
	 * @orm:Id
	 */
	protected $id;

	/** @orm:Column(length=512) */
	protected $title;

	/** @orm:Column(length=512) */
	protected $description;

	/** @orm:Column(length=512) */
	protected $template;

	//TODO: Elements (WMC Extension?)
	//TODO: Layers (WMC Extension?)

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
	 * @return integer $id
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set title
	 *
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Get title
	 *
	 * @return string $title
	 */
	public function getTitle() {
		return $this-title;
	}

	/**
	 * Set description
	 *
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * Get description
	 *
	 * @return string $description
	 */
	public function getDescription() {
		return this->description;
	}

	/**
	 * Set template
	 *
	 * @param string $template
	 */
	public function setTemplate($template) {
		$this->template = $template;
	}

	/**
	 * Get template
	 *
	 * @return string $template
	 */
	public function getTemplate() {
		return $this->template;
	}
}

