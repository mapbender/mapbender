<?php

namespace Mapbender\CoreBundle\Component;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class Element {
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

    static public function getTitle() {
        throw new \Exception('Your Element must implement the getTitle '
            .'function!');
    }

    public function getName() {
        return $this->name;
    }

    static public function getDescription() {
        throw new \Exception('Your Element must implement the getDescription '
            .'function!');
    }

	public static function getTags() {
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

