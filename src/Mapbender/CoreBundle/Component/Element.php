<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class Element implements ElementInterface {
	protected $id;
	protected $name;
	protected $configuration;
    protected $application;

	public function __construct($id, $name, array $configuration, $application) {
		$this->name = $name;
		$this->id = $id;
		$this->configuration = $configuration;
        $this->application = $application;
	}

	protected function get($what) {
		return $this->application->get($what);
    }

    protected function getParameter($key) {
        return $this->application->getParameter($key);
    }

	public function getTitle() {
		return "Element";
	}

    public function getName() {
        return $this->name;
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

	public final function getId() {
		return $this->id;
	}

	public function getConfiguration() {
		return array();
	}

	public function httpAction($action) {
		throw new NotFoundHttpException("No such action for this element");
	}

	public function	render() {
		throw new \Exception("The render function of " . get_class($this) . " has to be overriden!");
	}

	public function __toString() {
		return $this->render();
	}
}

