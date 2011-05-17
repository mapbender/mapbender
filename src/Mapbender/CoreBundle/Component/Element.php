<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class Element implements ElementInterface {
	protected $id;
	protected $name;
	protected $configuration;
	protected $container;

	public function __construct($id, $name, array $configuration, ContainerInterface $container) {
		$this->name = $name;
		$this->container = $container;
		$this->id = $id;
		$this->configuration = $configuration;
	}

	public function get($what) {
		return $this->container->get($what);
	}

	public function getTitle() {
		return "Element";
	}
	
	public function getDescription() {
		throw new \Exception("The getDescription function of " . get_class($this) . " has to be overriden!");
	}

	public function getTags() {
		return array();
	}

	public function getAssets() {
		return array();
	}

	public function getParents() {
		return array();
	}

	public function isContainer() {
		return false;
	}

	public function getId() {
		return $this->id;
	}

	public function getConfiguration() {
		return array();
	}

	public function httpAction($action) {
		throw new NotFoundHttpException("No such action for this element");
	}

	public function	render(ElementInterface $parentElement = NULL, $block = 'content') {
		throw new \Exception("The render function of " . get_class($this) . " has to be overriden!");
	}

	public function __toString() {
		return $this->render();
	}
}

