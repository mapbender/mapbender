<?php


namespace Mapbender\FrameworkBundle\Listener;


use Mapbender\CoreBundle\Entity\Application;

class ApplicationEngineListener
{
    protected $forceEngine;

    public function __construct($forceEngine)
    {
        $this->forceEngine = $forceEngine;
    }

    public function postLoad(Application $application)
    {
        if ($this->forceEngine) {
            $application->setMapEngineCode($this->forceEngine);
        }
        // Rewrite legacy explicit 'ol4' identifier to 'current'
        if ('ol4' === $application->getMapEngineCode()) {
            $application->setMapEngineCode(Application::MAP_ENGINE_CURRENT);
        }
    }
}
